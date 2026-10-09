import { execFileSync } from 'child_process'
import * as fs from 'fs'
import * as path from 'path'
import { fileURLToPath } from 'url'
import { test, expect, type Page } from '@playwright/test'
import { loadFixtures, PASSWORD } from './helpers'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.resolve(__dirname, '../..')
const shotDir = path.join(root, 'storage', 'logs', 'nav-phone-width')

const members = {
    en: { email: 'nav-en@browser-test.local', locale: 'en', admin: false },
    nl: { email: 'nav-nl@browser-test.local', locale: 'nl', admin: false },
    admin: { email: 'nav-admin@browser-test.local', locale: 'nl', admin: true },
}

test.beforeAll(() => {
    fs.mkdirSync(shotDir, { recursive: true })
    for (const [key, m] of Object.entries(members)) {
        const php = `\\App\\Models\\Member::updateOrCreate(['email' => '${m.email}'], ['name' => 'Nav ${key}', 'password' => \\Illuminate\\Support\\Facades\\Hash::make('${PASSWORD}'), 'is_admin' => ${m.admin}, 'is_active' => true, 'locale' => '${m.locale}']);`
        execFileSync('php', ['artisan', 'tinker', '--env=browser-test', `--execute=${php}`], { cwd: root })
    }
})

async function login(page: Page, email: string) {
    await page.goto('/login')
    await page.fill('#email', email)
    await page.fill('#password', PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL((url) => url.pathname !== '/login')
}

async function expectSingleLine(page: Page, selector: string) {
    const m = await page.locator(selector).first().evaluate((el) => {
        const r = el.getBoundingClientRect()
        return {
            height: r.height,
            top: r.top,
            width: r.width,
            lineHeight: parseFloat(getComputedStyle(el).lineHeight),
        }
    })
    expect(m.height).toBeLessThanOrEqual(m.lineHeight * 1.5)
    return m
}

const cases = [
    { width: 360, key: 'en' },
    { width: 390, key: 'en' },
    { width: 360, key: 'nl' },
    { width: 390, key: 'nl' },
    { width: 360, key: 'admin' },
] as const

for (const { width, key } of cases) {
    test(`nav stays on one line at ${width}px (${key})`, async ({ page }) => {
        const { openCompetitionId } = loadFixtures()
        await page.setViewportSize({ width, height: 800 })
        const errors: string[] = []
        page.on('pageerror', (e) => errors.push(e.message))
        await login(page, members[key].email)

        const pages = [
            { name: 'home', url: '/' },
            { name: 'profile', url: '/profile' },
            { name: 'entry', url: `/competitions/${openCompetitionId}/entries/create` },
        ]
        for (const p of pages) {
            await page.goto(p.url)
            const nav = page.locator('nav').first()
            await expect(nav.getByRole('button', { name: /log ?out|uitloggen/i })).toBeVisible()
            const brand = await expectSingleLine(page, 'nav > div > a:first-child')
            const brandText = await page.locator('nav > div > a:first-child').evaluate((el) => `${el.textContent?.trim()} | scrollWidth ${el.scrollWidth} clientWidth ${el.clientWidth}`)
            console.log(`BRAND ${width} ${key} ${p.name}: ${brandText}`)
            const logout = await expectSingleLine(page, 'nav form button[type="submit"]')
            expect(brand.width).toBeGreaterThanOrEqual(40)
            expect(Math.abs(brand.top - logout.top)).toBeLessThanOrEqual(4)
            const overflow = await page.evaluate(
                () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
            )
            expect(overflow).toBe(false)
            await nav.screenshot({ path: path.join(shotDir, `${width}-${key}-${p.name}-nav.png`) })
            await page.screenshot({ path: path.join(shotDir, `${width}-${key}-${p.name}.png`) })
        }
        expect(errors).toEqual([])
    })
}
