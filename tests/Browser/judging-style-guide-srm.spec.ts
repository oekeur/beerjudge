import { test, expect, Page } from '@playwright/test'
import { login, loadFixtures, clearIndexedDB } from './helpers'

async function openAppearanceGuide(page: Page, brewerName: string, errors: string[]) {
    const { judgingCompetitionId } = loadFixtures()
    page.on('pageerror', (e) => errors.push(e.message))

    await login(page)
    await clearIndexedDB(page)
    await page.goto(`/judging/${judgingCompetitionId}`)
    await page.waitForLoadState('networkidle')

    await page.getByRole('button', { name: new RegExp(brewerName) }).first().click()
    await page.getByRole('button', { name: /View BJCP appearance guideline/ }).first().click()
}

test.describe('judging style guide SRM', () => {
    test('a style with only srm_max shows one glass labelled with that value', async ({ page }) => {
        const errors: string[] = []
        await openAppearanceGuide(page, 'SRM Max Brewer', errors)

        await expect(page.getByText('SRM 40', { exact: true })).toBeVisible()
        // srm 40 is a clamp edge: display range collapses to 0, glass must sit at 50%, not NaN%
        const left = await page.getByTestId('srm-max-glass').evaluate((el) => (el as HTMLElement).style.left)
        expect(left).toBe('50%')
        expect(errors).toEqual([])
    })

    test('a style with only srm_min shows no max glass', async ({ page }) => {
        const errors: string[] = []
        await openAppearanceGuide(page, 'SRM Min Brewer', errors)

        await expect(page.getByText('SRM 20', { exact: true })).toBeVisible()
        await expect(page.getByTestId('srm-max-glass')).toBeHidden()
        expect(errors).toEqual([])
    })

    test('a style with min equal to max at the clamp edge washes both halves equally', async ({ page }) => {
        const errors: string[] = []
        await openAppearanceGuide(page, 'SRM Edge Brewer', errors)

        // the hidden max glass keeps its label in the DOM, so count only visible ones
        await expect(page.getByText('SRM 40', { exact: true }).locator('visible=true')).toHaveCount(1)
        const leftWidth = await page.getByTestId('srm-left-mask').evaluate((el) => el.getBoundingClientRect().width)
        const rightWidth = await page.getByTestId('srm-right-mask').evaluate((el) => el.getBoundingClientRect().width)
        expect(leftWidth).toBeGreaterThan(0)
        expect(rightWidth).toBeCloseTo(leftWidth, 0)
        expect(errors).toEqual([])
    })
})
