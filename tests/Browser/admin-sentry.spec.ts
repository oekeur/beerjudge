import { execFileSync } from 'child_process'
import * as path from 'path'
import { fileURLToPath } from 'url'
import { test, expect, type Page } from '@playwright/test'
import { PASSWORD } from './helpers'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.resolve(__dirname, '../..')
const email = 'sentry-admin@browser-test.local'

test.beforeAll(() => {
    const php = `\\App\\Models\\Member::updateOrCreate(['email' => '${email}'], ['name' => 'Sentry admin', 'password' => \\Illuminate\\Support\\Facades\\Hash::make('${PASSWORD}'), 'is_admin' => true, 'is_active' => true, 'locale' => 'nl']);`
    execFileSync('php', ['artisan', 'tinker', '--env=browser-test', `--execute=${php}`], { cwd: root })
})

/*
 * The first test needs no DSN and runs in CI. The second needs Sentry to initialise, so it skips
 * unless the served app has a DSN. To run it locally, add a well-formed fake DSN to the gitignored
 * .env.browser-test (no network is needed; ingest requests are aborted below):
 *
 *     SENTRY_LARAVEL_DSN=https://abc123@o0.ingest.sentry.io/1
 *
 * then restart `APP_ENV=browser-test php artisan serve` and run this spec. Do not add the DSN to
 * .env.browser-test.example: every other spec would then try to reach ingest.sentry.io from CI.
 */

async function openAdminCompetitions(page: Page) {
    await page.route('**/*.ingest.sentry.io/**', (route) => route.abort())
    await page.goto('/login')
    await page.fill('#email', email)
    await page.fill('#password', PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL((url) => url.pathname !== '/login')
    await page.goto('/admin/competitions')
}

test('admin panel loads the self-hosted Sentry bundle, not the CDN', async ({ page }) => {
    const requested: string[] = []
    const pageErrors: string[] = []
    page.on('request', (request) => requested.push(request.url()))
    page.on('pageerror', (error) => pageErrors.push(error.message))

    await openAdminCompetitions(page)

    expect(await page.evaluate(() => typeof (window as any).Sentry)).toBe('object')
    expect(requested.some((url) => url.includes('browser.sentry-cdn.com'))).toBe(false)
    expect(requested.some((url) => url.endsWith('/vendor/error-report/bundle.js'))).toBe(true)
    expect(pageErrors).toEqual([])
})

test('admin panel shows the Dutch feedback button', async ({ page }) => {
    await openAdminCompetitions(page)

    const dsn = await page.locator('meta[name="sentry-dsn"]').getAttribute('content')
    test.skip(!dsn, 'needs SENTRY_LARAVEL_DSN in .env.browser-test')

    await expect(page.getByRole('button', { name: 'Meld een bug' })).toBeVisible()
})
