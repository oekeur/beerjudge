import { test, expect } from '@playwright/test'
import { login, loadFixtures, clearIndexedDB } from './helpers'

/*
 * Needs Sentry to initialise so the floating feedback button exists; skips without a DSN.
 * See the recipe in admin-sentry.spec.ts (fake SENTRY_LARAVEL_DSN in .env.browser-test).
 */
test.use({ viewport: { width: 390, height: 844 } })

test('last judging entry scrolls clear of the Sentry feedback button', async ({ page }) => {
    await page.route('**/*.ingest.sentry.io/**', (route) => route.abort())
    const { judgingCompetitionId } = loadFixtures()

    await login(page)
    await clearIndexedDB(page)
    await page.goto(`/judging/${judgingCompetitionId}`)

    const dsn = await page.locator('meta[name="sentry-dsn"]').getAttribute('content')
    test.skip(!dsn, 'No Sentry DSN in the served app; the feedback button is not injected')

    await page.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })

    const feedbackButton = page.locator('#sentry-feedback').getByRole('button')
    await expect(feedbackButton).toBeVisible()

    // Five collapsed entries fit in 844px; expand the first so the page overflows and has an end to scroll to.
    await page.getByTestId('entry-expand-button').first().click()
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollHeight)).toBeGreaterThan(844)

    await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight))
    await page.waitForTimeout(300)

    const lastEntry = await page.getByTestId('entry-expand-button').last().boundingBox()
    const feedback = await feedbackButton.boundingBox()
    expect(lastEntry).not.toBeNull()
    expect(feedback).not.toBeNull()

    const intersects =
        lastEntry!.x < feedback!.x + feedback!.width &&
        feedback!.x < lastEntry!.x + lastEntry!.width &&
        lastEntry!.y < feedback!.y + feedback!.height &&
        feedback!.y < lastEntry!.y + lastEntry!.height
    expect(intersects, `entry ${JSON.stringify(lastEntry)} vs feedback ${JSON.stringify(feedback)}`).toBe(false)
})
