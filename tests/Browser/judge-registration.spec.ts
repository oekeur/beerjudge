import { test, expect } from '@playwright/test'
import { login, loadFixtures, clearIndexedDB, NEWCOMER_EMAIL } from './helpers'

test.describe('judge participation', () => {
    test('member outside the judge list sees the participate prompt instead of entries, then participates', async ({ page }) => {
        const { judgingCompetitionId } = loadFixtures()

        await login(page, NEWCOMER_EMAIL)
        await clearIndexedDB(page)
        await page.goto(`/judging/${judgingCompetitionId}`)

        const prompt = page.getByTestId('participate-prompt')
        await expect(prompt).toBeVisible()
        await expect(page.getByRole('main')).toHaveCount(0)
        const participateBtn = page.getByRole('button', { name: 'Participate', exact: true })
        await expect(participateBtn).toBeVisible()

        await participateBtn.click()

        await expect(page.getByTestId('participate-prompt')).toHaveCount(0)
        await expect(page.getByRole('main')).toBeVisible()
        await expect(page.getByRole('button', { name: 'Stop participating' })).toBeVisible()
    })

    test('participating judge can stop participating', async ({ page }) => {
        const { judgingCompetitionId } = loadFixtures()

        await login(page, NEWCOMER_EMAIL)
        await clearIndexedDB(page)

        // Participate via the query param shortcut
        await page.goto(`/judging/${judgingCompetitionId}?register=1`)
        await page.waitForSelector('main', { state: 'visible' })

        // Navigate to the clean URL so that the leave form's back() redirect
        // returns here (no ?register=1) rather than re-registering the judge
        await page.goto(`/judging/${judgingCompetitionId}`)
        await page.waitForSelector('main', { state: 'visible' })

        const leaveBtn = page.getByRole('button', { name: 'Stop participating' })
        await expect(leaveBtn).toBeVisible()
        await leaveBtn.click()

        await expect(page.getByRole('button', { name: 'Participate', exact: true })).toBeVisible()
        await expect(page.getByRole('main')).toHaveCount(0)
    })
})
