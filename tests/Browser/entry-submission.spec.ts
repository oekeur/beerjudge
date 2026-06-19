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

        // Submit the form
        await page.click('button[type="submit"]')

        // Flash success message must appear
        await expect(page.locator('p.text-green-700')).toBeVisible()

        // The submitted entry must appear in "Your entries" list
        await expect(page.locator('text=Test Lager')).toBeVisible()
    })

    test('submitting without selecting a style shows a validation error', async ({ page }) => {
        const { openCompetitionId } = loadFixtures()

        await login(page, BREWER_EMAIL)
        await page.goto(`/competitions/${openCompetitionId}/entries/create`)

        // Submit with the default empty option selected
        await page.click('button[type="submit"]')

        // Validation error must be displayed
        await expect(page.locator('p.text-red-600')).toBeVisible()
    })
})
