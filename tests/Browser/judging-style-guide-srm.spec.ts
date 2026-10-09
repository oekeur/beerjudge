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
    await page.getByRole('button', { name: /View appearance guideline/ }).first().click()
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

    const ATTRIBUTION =
        'Based on Brewers Association 2026 Beer Style Guidelines with changes. Used with permission of Brewers Association.'

    test('a Brewers Association entry shows the attribution once at the top of the entry and in the style-info sheet, not in the guide sheet', async ({ page }) => {
        const errors: string[] = []
        await openAppearanceGuide(page, 'BA Brewer', errors)

        await expect(page.getByText('Clarity: Chill haze')).toBeVisible()
        await expect(page.getByTestId('style-guide-panel').getByText(ATTRIBUTION)).toHaveCount(0)
        await page.getByTestId('style-guide-panel').getByRole('button', { name: '×' }).click()
        await expect(page.getByTestId('style-guide-panel')).toBeHidden()

        const line = page.getByTestId('entry-attribution').locator('visible=true')
        await expect(line).toHaveText(ATTRIBUTION)
        await expect(page.getByText(ATTRIBUTION).locator('visible=true')).toHaveCount(1)
        const isFirstRow = await line.evaluate((el) => el.parentElement!.firstElementChild === el)
        expect(isFirstRow).toBe(true)
        const aromaBox = (await page.getByRole('button', { name: /View aroma guideline/ }).first().boundingBox())!
        expect((await line.boundingBox())!.y).toBeLessThan(aromaBox.y)

        await page.getByRole('button', { name: 'View style info for BA Test Bitter', exact: true }).click()
        await expect(page.getByTestId('style-info-attribution')).toHaveText(ATTRIBUTION)
        expect(errors).toEqual([])
    })

    test('a non-BA entry shows the attribution nowhere', async ({ page }) => {
        const errors: string[] = []
        await openAppearanceGuide(page, 'SRM Min Brewer', errors)

        await expect(page.getByText('SRM 20', { exact: true })).toBeVisible()
        await expect(page.getByText(ATTRIBUTION).locator('visible=true')).toHaveCount(0)
        await page.getByTestId('style-guide-panel').getByRole('button', { name: '×' }).click()
        await expect(page.getByTestId('style-guide-panel')).toBeHidden()
        await expect(page.getByTestId('entry-attribution').locator('visible=true')).toHaveCount(0)
        await page.getByRole('button', { name: /View style info for/ }).first().click()
        await expect(page.getByTestId('style-info-attribution')).toBeHidden()
        expect(errors).toEqual([])
    })

    test.describe('long Brewers Association text on a 390x844 phone', () => {
        test.use({ viewport: { width: 390, height: 844 } })

        // The Sentry feedback widget only loads with a DSN, which the browser-test env has not.
        // Stand in for its footprint: fixed, bottom-right, 1rem inset, 56px square.
        async function addFloatingButton(page: Page) {
            await page.evaluate(() => {
                const b = document.createElement('button')
                b.setAttribute('data-testid', 'floating-feedback-button')
                b.style.cssText = 'position:fixed;right:16px;bottom:16px;width:56px;height:56px;z-index:100000'
                document.body.appendChild(b)
            })
        }

        async function assertSheetBehaviour(page: Page, panelTestId: string, sheetTestId: string, attributionTestId: string | null, errors: string[]) {
            const viewport = page.viewportSize()!
            const scroll = page.getByTestId(sheetTestId)
            const attribution = attributionTestId ? page.getByTestId(attributionTestId) : null
            if (attribution) {
                await expect(attribution).toBeVisible()
            }
            await page.waitForTimeout(400) // enter transition

            const panel = page.getByTestId(panelTestId)
            const panelBox = (await panel.boundingBox())!
            expect(panelBox.y).toBeGreaterThan(50) // backdrop stays tappable above the panel
            expect(panelBox.y + panelBox.height).toBeLessThanOrEqual(viewport.height + 1)

            const close = panel.getByRole('button', { name: '×' })
            const closeBox = (await close.boundingBox())!
            expect(closeBox.y).toBeGreaterThanOrEqual(0)
            expect(closeBox.y + closeBox.height).toBeLessThanOrEqual(viewport.height)
            expect(closeBox.x + closeBox.width).toBeLessThanOrEqual(viewport.width)

            const dims = await scroll.evaluate((el) => ({ sh: el.scrollHeight, ch: el.clientHeight }))
            expect(dims.sh).toBeGreaterThan(dims.ch)

            await scroll.evaluate((el) => el.scrollTo(0, el.scrollHeight))
            expect(await scroll.evaluate((el) => el.scrollTop)).toBeGreaterThan(0)

            if (attribution) {
                const a = (await attribution.boundingBox())!
                const f = (await page.getByTestId('floating-feedback-button').boundingBox())!
                const intersects = a.x < f.x + f.width && a.x + a.width > f.x && a.y < f.y + f.height && a.y + a.height > f.y
                expect(intersects).toBe(false)
                expect(a.y + a.height).toBeLessThanOrEqual(viewport.height)
            }

            await close.click()
            await expect(panel).toBeHidden()
            expect(errors).toEqual([])
        }

        test('the guide sheet stays inside the viewport and scrolls its long text', async ({ page }) => {
            const errors: string[] = []
            await openAppearanceGuide(page, 'BA Brewer', errors)
            await addFloatingButton(page)
            await assertSheetBehaviour(page, 'style-guide-panel', 'style-guide-scroll', null, errors)
        })

        test('the style-info sheet stays inside the viewport and scrolls its long text', async ({ page }) => {
            const errors: string[] = []
            const { judgingCompetitionId } = loadFixtures()
            page.on('pageerror', (e) => errors.push(e.message))
            await login(page)
            await clearIndexedDB(page)
            await page.goto(`/judging/${judgingCompetitionId}`)
            await page.waitForLoadState('networkidle')
            await page.getByRole('button', { name: /BA Brewer/ }).first().click()
            await page.getByRole('button', { name: 'View style info for BA Test Bitter', exact: true }).click()
            await addFloatingButton(page)
            await assertSheetBehaviour(page, 'style-info-panel', 'style-info-scroll', 'style-info-attribution', errors)
        })
        async function assertOpensAtTop(page: Page, scrollTestId: string) {
            await page.waitForTimeout(400) // enter transition
            const scroll = page.getByTestId(scrollTestId)
            expect(await scroll.evaluate((el) => el.scrollTop)).toBe(0)
            const bodyTop = (await scroll.boundingBox())!.y
            const firstTop = (await scroll.locator('p').first().boundingBox())!.y
            expect(firstTop).toBeGreaterThanOrEqual(bodyTop)
        }

        async function scrollToBottomAndClose(page: Page, scrollTestId: string, panelTestId: string) {
            await page.waitForTimeout(400)
            const scroll = page.getByTestId(scrollTestId)
            await scroll.evaluate((el) => el.scrollTo(0, el.scrollHeight))
            expect(await scroll.evaluate((el) => el.scrollTop)).toBeGreaterThan(0)
            await page.getByTestId(panelTestId).getByRole('button', { name: '×' }).click()
            await expect(page.getByTestId(panelTestId)).toBeHidden()
        }

        test('the guide sheet reopens at the top after being scrolled, for another and the same section', async ({ page }) => {
            const errors: string[] = []
            await openAppearanceGuide(page, 'BA Brewer', errors)
            await scrollToBottomAndClose(page, 'style-guide-scroll', 'style-guide-panel')

            await page.getByRole('button', { name: /View aroma guideline/ }).first().click()
            await assertOpensAtTop(page, 'style-guide-scroll')
            await page.getByTestId('style-guide-panel').getByRole('button', { name: '×' }).click()

            await page.getByRole('button', { name: /View appearance guideline/ }).first().click()
            await assertOpensAtTop(page, 'style-guide-scroll')
            expect(errors).toEqual([])
        })

        test('the style-info sheet reopens at the top after being scrolled', async ({ page }) => {
            const errors: string[] = []
            const { judgingCompetitionId } = loadFixtures()
            page.on('pageerror', (e) => errors.push(e.message))
            await login(page)
            await clearIndexedDB(page)
            await page.goto(`/judging/${judgingCompetitionId}`)
            await page.waitForLoadState('networkidle')
            await page.getByRole('button', { name: /BA Brewer/ }).first().click()
            const open = () => page.getByRole('button', { name: 'View style info for BA Test Bitter', exact: true }).click()
            await open()
            await scrollToBottomAndClose(page, 'style-info-scroll', 'style-info-panel')
            await open()
            await assertOpensAtTop(page, 'style-info-scroll')
            expect(errors).toEqual([])
        })
        test('both sheets reopen at the top when the opener is clicked from script', async ({ page }) => {
            const errors: string[] = []
            await openAppearanceGuide(page, 'BA Brewer', errors)
            await scrollToBottomAndClose(page, 'style-guide-scroll', 'style-guide-panel')
            await page.getByRole('button', { name: /View appearance guideline/ }).first().evaluate((el) => (el as HTMLElement).click())
            await page.waitForTimeout(400)
            expect(await page.getByTestId('style-guide-scroll').evaluate((el) => el.scrollTop)).toBe(0)
            await page.getByTestId('style-guide-panel').getByRole('button', { name: '×' }).click()

            const info = page.getByRole('button', { name: 'View style info for BA Test Bitter', exact: true })
            await info.evaluate((el) => (el as HTMLElement).click())
            await scrollToBottomAndClose(page, 'style-info-scroll', 'style-info-panel')
            await info.evaluate((el) => (el as HTMLElement).click())
            await page.waitForTimeout(400)
            expect(await page.getByTestId('style-info-scroll').evaluate((el) => el.scrollTop)).toBe(0)
            expect(errors).toEqual([])
        })
    })
})
