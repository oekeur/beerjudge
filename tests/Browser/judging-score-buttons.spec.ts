import { test, expect } from '@playwright/test'
import { login, loadFixtures } from './helpers'

test.describe('judging score buttons', () => {
    test('score button selection is reflected via aria-pressed', async ({ page }) => {
        const { judgingCompetitionId } = loadFixtures()

        await login(page)
        await page.goto(`/judging/${judgingCompetitionId}`)

        // Wait for Alpine to render entry cards via x-for
        await page.waitForSelector('main button.w-full.text-left', { state: 'visible' })

        // Expand the first entry's scoresheet
        await page.locator('main button.w-full.text-left').first().click()

        const aromaGroup = page.locator('[role="group"][aria-label="Aroma score, 0 to 12"]').first()
        await aromaGroup.waitFor({ state: 'visible' })

        const btn5 = aromaGroup.locator('button').filter({ hasText: /^5$/ })
        const btn8 = aromaGroup.locator('button').filter({ hasText: /^8$/ })

        // Click 5 — must become pressed
        await btn5.click()
        await expect(btn5).toHaveAttribute('aria-pressed', 'true')
        await expect(btn8).toHaveAttribute('aria-pressed', 'false')

        // Click 8 — previous selection must deselect
        await btn8.click()
        await expect(btn8).toHaveAttribute('aria-pressed', 'true')
        await expect(btn5).toHaveAttribute('aria-pressed', 'false')
    })

    test('all sections can be scored and the running total updates', async ({ page }) => {
        const { judgingCompetitionId } = loadFixtures()

        await login(page)
        await page.goto(`/judging/${judgingCompetitionId}`)
        await page.waitForSelector('main button.w-full.text-left', { state: 'visible' })
        await page.locator('main button.w-full.text-left').first().click()

        const aromaGroup = page.locator('[role="group"][aria-label="Aroma score, 0 to 12"]').first()
        await aromaGroup.waitFor({ state: 'visible' })

        // Score each section: 8 + 3 + 14 + 4 + 9 = 38
        await aromaGroup.locator('button').filter({ hasText: /^8$/ }).click()
        await page.locator('[role="group"][aria-label="Appearance score, 0 to 3"]').first()
            .locator('button').filter({ hasText: /^3$/ }).click()
        await page.locator('[role="group"][aria-label="Flavour score, 0 to 20"]').first()
            .locator('button').filter({ hasText: /^14$/ }).click()
        await page.locator('[role="group"][aria-label="Mouthfeel score, 0 to 5"]').first()
            .locator('button').filter({ hasText: /^4$/ }).click()
        await page.locator('[role="group"][aria-label="Overall Impression score, 0 to 10"]').first()
            .locator('button').filter({ hasText: /^9$/ }).click()

        // Total display in the footer of the editable scoresheet
        const scoresheetFooter = page.locator('div.border-t.border-gray-100.pt-3').first()
        await expect(scoresheetFooter.locator('span').filter({ hasText: /^38$/ })).toBeVisible()
    })

    test('scoresheet can be submitted and shows read-only view', async ({ page }) => {
        const { judgingCompetitionId } = loadFixtures()

        await login(page)
        await page.goto(`/judging/${judgingCompetitionId}`)
        await page.waitForSelector('main button.w-full.text-left', { state: 'visible' })
        await page.locator('main button.w-full.text-left').first().click()

        const aromaGroup = page.locator('[role="group"][aria-label="Aroma score, 0 to 12"]').first()
        await aromaGroup.waitFor({ state: 'visible' })

        // Score minimally to be able to submit
        await aromaGroup.locator('button').filter({ hasText: /^8$/ }).click()
        await page.locator('[role="group"][aria-label="Appearance score, 0 to 3"]').first()
            .locator('button').filter({ hasText: /^2$/ }).click()
        await page.locator('[role="group"][aria-label="Flavour score, 0 to 20"]').first()
            .locator('button').filter({ hasText: /^12$/ }).click()
        await page.locator('[role="group"][aria-label="Mouthfeel score, 0 to 5"]').first()
            .locator('button').filter({ hasText: /^3$/ }).click()
        await page.locator('[role="group"][aria-label="Overall Impression score, 0 to 10"]').first()
            .locator('button').filter({ hasText: /^7$/ }).click()

        // Set up the response listener BEFORE clicking to avoid missing a fast response.
        // Score-button saves also POST to /scoresheets (draft status); we filter on
        // the submit response specifically by waiting for the read-only badge that
        // Alpine only renders once syncStatus becomes 'synced' after the server confirms.
        await page.locator('button').filter({ hasText: /Submit/ }).first().click()

        // Read-only view must appear with "Submitted ✓" badge
        // (x-if condition: status==='submitted' && syncStatus==='synced' — only true after server 200)
        await expect(page.locator('span.text-green-600').filter({ hasText: /Submitted/ }).first()).toBeVisible({ timeout: 15_000 })

        // "Reopen to edit" must be visible
        await expect(page.getByRole('button', { name: /Reopen/ })).toBeVisible()
    })
})
