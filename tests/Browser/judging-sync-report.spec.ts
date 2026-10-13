import { execFileSync } from 'child_process'
import * as path from 'path'
import { fileURLToPath } from 'url'
import { test, expect } from '@playwright/test'
import { login, loadFixtures, clearIndexedDB, JUDGE_EMAIL } from './helpers'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..')

// Other specs leave submitted sheets behind; a submitted entry opens as a read-only summary.
test.beforeEach(() => {
    execFileSync('php', ['artisan', 'tinker', '--env=browser-test',
        `--execute=\\App\\Models\\Scoresheet::whereHas('judge', fn ($q) => $q->where('email', '${JUDGE_EMAIL}'))->delete();`,
    ], { cwd: root })
})

// A well-formed fake DSN injected into the judging page only, so Sentry initialises there and
// its ingest requests can be caught below. No network is needed; nothing reaches sentry.io.
const FAKE_DSN = 'https://abc123@o0.ingest.sentry.io/1'

test('a rejected save is reported to Sentry once, with the server reason', async ({ page }) => {
    test.setTimeout(60_000)
    const { judgingCompetitionId } = loadFixtures()
    const envelopes: string[] = []

    await page.route('**/*.ingest.sentry.io/**', async (route) => {
        envelopes.push(route.request().postData() ?? '')
        await route.fulfill({ status: 200, body: '{}' })
    })
    await page.route(`**/judging/${judgingCompetitionId}`, async (route) => {
        const response = await route.fetch()
        const html = (await response.text()).replace(
            '<meta name="sentry-dsn" content="">',
            `<meta name="sentry-dsn" content="${FAKE_DSN}">`,
        )
        await route.fulfill({ response, body: html })
    })
    await page.route('**/scoresheets', (route) =>
        route.fulfill({
            status: 422,
            contentType: 'application/json',
            body: JSON.stringify({ message: 'Competition is not in judging phase.' }),
        }),
    )

    await login(page)
    await clearIndexedDB(page)
    await page.goto(`/judging/${judgingCompetitionId}`)
    await page.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
    await page.waitForLoadState('networkidle')
    await page.getByTestId('entry-expand-button').first().click()
    await page.locator('[role="group"][aria-label="Aroma score, 0 to 12"]:visible').first()
        .locator('button').filter({ hasText: /^9$/ }).click()

    const reports = () => envelopes.filter((body) => body.includes('Scoresheet save rejected (422)'))
    await expect.poll(() => reports().length, { timeout: 15_000 }).toBe(1)
    expect(reports()[0]).toContain('Competition is not in judging phase.')

    // The sheet keeps retrying; the report must not repeat.
    await page.locator('[role="group"][aria-label="Appearance score, 0 to 3"]:visible').first()
        .locator('button').filter({ hasText: /^2$/ }).click()
    await page.waitForTimeout(7_000)
    expect(reports()).toHaveLength(1)
})
