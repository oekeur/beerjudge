import { test, expect } from '@playwright/test'
import { login, loadFixtures } from './helpers'

test.describe('judge registration', () => {
    test('unregistered judge sees the register banner and can register', async ({ page }) => {
        const { judgingCompetitionId } = loadFixtures()

        await login(page)
        await page.goto(`/judging/${judgingCompetitionId}`)

        // Unregistered judge sees the banner with a "Register as judge" button
        const registerBtn = page.getByRole('button', { name: 'Register as judge' })
        await expect(registerBtn).toBeVisible()

        // Clicking submits the form and reloads the page
        await registerBtn.click()

        // After registering, the banner is gone and "Leave as judge" is visible
        await expect(page.getByRole('button', { name: 'Register as judge' })).not.toBeVisible()
        await expect(page.getByRole('button', { name: 'Leave as judge' })).toBeVisible()
    })

    test('registered judge can unregister', async ({ page }) => {
        const { judgingCompetitionId } = loadFixtures()

        await login(page)

        // Register first via the query param shortcut
        await page.goto(`/judging/${judgingCompetitionId}?register=1`)
        await page.waitForSelector('main', { state: 'visible' })

        // Leave as judge
        const leaveBtn = page.getByRole('button', { name: 'Leave as judge' })
        await expect(leaveBtn).toBeVisible()
        await leaveBtn.click()

        // After leaving, the register banner is shown again
        await expect(page.getByRole('button', { name: 'Register as judge' })).toBeVisible()
    })
})
