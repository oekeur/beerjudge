import { test, expect } from '@playwright/test'
import { login, BREWER_EMAIL, loadFixtures } from './helpers'

test.describe('entry submission', () => {
    test('member can submit an entry for an open competition', async ({ page }) => {
        const { openCompetitionId } = loadFixtures()

        await login(page, BREWER_EMAIL)
        await page.goto(`/competitions/${openCompetitionId}/entries/create`)

        // Style dropdown must have at least one option to select
        const styleSelect = page.locator('#style_id')
        await expect(styleSelect).toBeVisible()
        const optionCount = await styleSelect.locator('option[value]:not([value=""])').count()
        expect(optionCount).toBeGreaterThan(0)

        // Select the first real style option and add optional notes
        const firstStyleValue = await styleSelect
            .locator('option[value]:not([value=""])')
            .first()
            .getAttribute('value')
        await styleSelect.selectOption(firstStyleValue!)
        await page.fill('#brewer_notes', 'Brewed with love for browser testing.')

        // Submit the entry form specifically — the page also has a logout button[type="submit"] in the nav
        await page.getByRole('button', { name: /Submit entry/ }).click()
        await page.waitForLoadState('networkidle')

        // Flash success message must appear
        await expect(page.getByTestId('flash-success')).toBeVisible()

        // The submitted entry must appear in "Your entries" list
        // Use getByRole('listitem') to avoid strict-mode violation: the style
        // dropdown <option> also contains "Test Lager" and would match text=Test Lager
        await expect(page.getByRole('listitem').filter({ hasText: 'Test Lager' }).first()).toBeVisible()
    })

    test('submitting without selecting a style shows a validation error', async ({ page }) => {
        const { openCompetitionId } = loadFixtures()

        await login(page, BREWER_EMAIL)
        await page.goto(`/competitions/${openCompetitionId}/entries/create`)

        // Disable HTML5 native validation on all forms so the empty select value reaches
        // the server. document.querySelector('form') would hit the logout form in x-app-nav
        // first, leaving the entry form's native validation active.
        await page.evaluate(() => {
            document.querySelectorAll('form').forEach(f => { (f as HTMLFormElement).noValidate = true })
        })

        // Submit the entry form specifically — the page also has a logout button[type="submit"] in the nav
        await page.getByRole('button', { name: /Submit entry/ }).click()
        await page.waitForLoadState('networkidle')

        // Validation error must be displayed
        await expect(page.getByTestId('form-error')).toBeVisible()
    })
})
