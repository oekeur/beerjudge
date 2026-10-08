import { chromium, expect } from '@playwright/test'
import fs from 'node:fs'
import path from 'node:path'
import { execFileSync } from 'node:child_process'

export { expect }
const HERE = path.dirname(new URL(import.meta.url).pathname)
const REPO = path.resolve(HERE, '../..')
export const BASE = process.env.JOURNEY_URL ?? 'http://localhost:8010'
export const PASSWORD = 'password'
/** Recordings, screenshots, cached sessions and state; storage/logs is gitignored. */
export const OUT = path.join(REPO, 'storage/logs/journeys')
/** Competition created by A2 and reused by the later journeys. */
export const STATE = path.join(OUT, 'state.json')
const LOG = path.join(REPO, 'storage/logs/laravel.log')

export async function journey(id, fn) {
  const dir = path.join(OUT, id)
  fs.rmSync(dir, { recursive: true, force: true })
  fs.mkdirSync(dir, { recursive: true })
  const browser = await chromium.launch({ headless: true })
  const results = []
  const contexts = []
  let n = 0
  const actor = async (label, opts = {}) => {
    const context = await browser.newContext({
      baseURL: BASE,
      recordVideo: { dir: path.join(dir, 'video-' + label), size: opts.viewport ?? { width: 1280, height: 800 } },
      viewport: opts.viewport ?? { width: 1280, height: 800 },
      locale: opts.locale ?? 'en-GB',
      permissions: ['clipboard-read', 'clipboard-write'],
    })
    contexts.push(context)
    const page = await context.newPage()
    page.on('console', m => { if (m.type() === 'error') results.push({ actor: label, console: m.text() }) })
    page.on('pageerror', e => results.push({ actor: label, pageerror: e.message }))
    page.on('response', r => { if (r.status() >= 500) results.push({ actor: label, http5xx: r.status() + ' ' + r.url() }) })
    page.on('requestfailed', r => results.push({ actor: label, requestfailed: r.url() + ' ' + (r.failure()?.errorText ?? '') }))
    const step = async (name, f) => {
      n++
      const shot = path.join(dir, String(n).padStart(2, '0') + '-' + label + '-' + name.replace(/[^a-z0-9]+/gi, '-').slice(0, 50) + '.png')
      try {
        await f()
        await page.screenshot({ path: shot, fullPage: true })
        results.push({ step: n, actor: label, name, ok: true, shot })
        console.log('PASS', n, label, name)
      } catch (e) {
        await page.screenshot({ path: shot, fullPage: true }).catch(() => {})
        results.push({ step: n, actor: label, name, ok: false, error: String(e.message).split('\n').slice(0, 4).join(' | '), url: page.url(), shot })
        console.log('FAIL', n, label, name, '::', String(e.message).split('\n')[0], '@', page.url())
      }
    }
    return { page, context, step }
  }
  try { await fn(actor) } catch (e) { results.push({ fatal: String(e.stack) }); console.log('FATAL', e.message) }
  for (const c of contexts) await c.close()
  await browser.close()
  fs.writeFileSync(path.join(dir, 'results.json'), JSON.stringify(results, null, 2))
  const extra = results.filter(r => !('step' in r))
  if (extra.length) console.log('SIGNALS', JSON.stringify(extra, null, 1))
}

const AUTH = path.join(OUT, '.auth')
export const throttled = []

/**
 * Logs in, reusing a cached session cookie when it is still valid. POST /login is
 * throttled at 6 per minute per email and IP (30 per minute per IP), so a 429 waits out the window and retries.
 */
export async function login(page, email, password = PASSWORD, { fresh = false } = {}) {
  fs.mkdirSync(AUTH, { recursive: true })
  const cache = path.join(AUTH, email + '.json')
  if (!fresh && fs.existsSync(cache)) {
    await page.context().addCookies(JSON.parse(fs.readFileSync(cache)))
    await page.goto('/')
    if (!/\/login/.test(page.url())) return
    await page.context().clearCookies()
  }
  for (let attempt = 0; attempt < 3; attempt++) {
    await page.goto('/login')
    await page.fill('#email', email)
    await page.fill('#password', password)
    const [resp] = await Promise.all([
      page.waitForResponse(r => r.url().endsWith('/login') && r.request().method() === 'POST'),
      page.click('form button[type="submit"]'),
    ])
    await page.waitForLoadState('networkidle')
    if (resp.status() !== 429) break
    throttled.push(email)
    console.log('  429 on login for', email, '- waiting 61s')
    await page.waitForTimeout(61000)
  }
  if (!/\/login/.test(page.url())) fs.writeFileSync(cache, JSON.stringify(await page.context().cookies()))
}

export async function logout(page) {
  await page.context().clearCookies()
}

/** Byte offset of the Laravel log, so mail written after it can be read. */
export function logMark() { try { return fs.statSync(LOG).size } catch { return 0 } }
export function logSince(mark) { try { return fs.readFileSync(LOG).subarray(mark).toString() } catch { return '' } }

export function tinker(code) {
  const env = { ...process.env }
  return execFileSync('bash', ['-c', `. "${HERE}/env.sh" && cd "${REPO}" && php artisan tinker --execute "$0"`, code], { env }).toString().trim()
}
