import { test, expect } from '@playwright/test'
import { login, BREWER_EMAIL, loadFixtures } from './helpers'

test.describe('entry submission', () => {
    test('member can submit an entry for an open competition', async ({ page }) => {
        const { openCompetitionId } = loadFixtures()

        await login(page, BREWER_EMAIL)
        await page.goto(`/competitions/${openCompetitionId}/entries/create`)

        // Style combobox must be visible and show options on focus
        const styleSearch = page.locator('#style_search')
        await expect(styleSearch).toBeVisible()
        await styleSearch.click()

        // Wait for dropdown options to appear, then select the first one
        const firstOption = page.getByTestId('style-option').first()
        await expect(firstOption).toBeVisible()
        await firstOption.click()

        await page.fill('#brewer_notes', 'Brewed with love for browser testing.')

        // Submit the entry form specifically — the page also has a logout button[type="submit"] in the nav
        await page.getByRole('button', { name: /Submit entry/ }).click()
        await page.waitForLoadState('networkidle')

        // Flash success message must appear
        await expect(page.getByTestId('flash-success')).toBeVisible()

        // The submitted entry must appear in "Your entries" list
        await expect(page.getByRole('listitem').filter({ hasText: 'Test Lager' }).first()).toBeVisible()
    })

    test('submitting without selecting a style shows a validation error', async ({ page }) => {
        const { openCompetitionId } = loadFixtures()

        await login(page, BREWER_EMAIL)
        await page.goto(`/competitions/${openCompetitionId}/entries/create`)

        // Disable HTML5 native validation on all forms so the form can submit with an empty style_id.
        // The combobox's visible input (#style_search) is `required` but has no `name`, so it isn't submitted.
        // document.querySelector('form') would hit the logout form in x-app-nav first,
        // leaving the entry form's native validation active.
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
