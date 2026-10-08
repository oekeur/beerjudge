import { execFileSync } from 'child_process'
import * as fs from 'fs'
import * as path from 'path'
import { fileURLToPath } from 'url'
import { test, expect, type Page, type Route } from '@playwright/test'
import { login, loadFixtures, clearIndexedDB, JUDGE_EMAIL, PASSWORD } from './helpers'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.resolve(__dirname, '../..')

const tinker = (php: string) =>
    execFileSync('php', ['artisan', 'tinker', '--env=browser-test', `--execute=${php}`], { cwd: root }).toString().trim()

const deleteJudgeSheets = () =>
    tinker(`\\App\\Models\\Scoresheet::whereHas('judge', fn ($q) => $q->where('email', '${JUDGE_EMAIL}'))->delete();`)

const judgeSheets = (status: string) =>
    tinker(`echo \\App\\Models\\Scoresheet::where('status', '${status}')->whereHas('judge', fn ($q) => $q->where('email', '${JUDGE_EMAIL}'))->count();`)

// Other specs score the same entries; leave the judge without sheets before and after.
test.beforeEach(deleteJudgeSheets)
test.afterAll(deleteJudgeSheets)

const scores = [
    ['Aroma score, 0 to 12', '9'],
    ['Appearance score, 0 to 3', '2'],
    ['Flavour score, 0 to 20', '15'],
    ['Mouthfeel score, 0 to 5', '4'],
    ['Overall Impression score, 0 to 10', '8'],
]

test('a sheet submitted offline reads as waiting, then syncs on reconnect without a reload', async ({ page, context }) => {
    test.setTimeout(60_000)
    const { judgingCompetitionId } = loadFixtures()
    const pageErrors: string[] = []
    page.on('pageerror', (e) => pageErrors.push(e.message))

    await login(page)
    await clearIndexedDB(page)
    await page.goto(`/judging/${judgingCompetitionId}`)
    await page.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
    await page.waitForLoadState('networkidle')

    await page.getByTestId('entry-expand-button').first().click()
    await context.setOffline(true)
    for (const [label, value] of scores) {
        const group = page.locator(`[role="group"][aria-label="${label}"]:visible`).first()
        await group.waitFor({ state: 'visible', timeout: 10_000 })
        await group.locator('button').filter({ hasText: new RegExp(`^${value}$`) }).click()
    }
    await page.getByRole('button', { name: /▶/ }).locator('visible=true').first().click()

    const waiting = page.getByText('Waiting for connection').locator('visible=true')
    const retry = page.getByText('Retry').locator('visible=true')
    await expect(waiting.first()).toBeVisible()

    // The 5 s retry timer must not turn the badge into "Saving…" or "Retry" while offline.
    const saving = page.getByText('Saving…').locator('visible=true')
    for (let second = 0; second < 12; second++) {
        // An edit while offline is kept on the device and stays queued, without a "Saving…" flash.
        if (second === 3) {
            await page.locator('[role="group"][aria-label="Aroma score, 0 to 12"]:visible').first()
                .locator('button').filter({ hasText: /^5$/ }).click()
        }
        await expect(saving).toHaveCount(0)
        await page.waitForTimeout(1_000)
        await expect(waiting.first()).toBeVisible({ timeout: 500 })
        await expect(retry).toHaveCount(0)
    }

    await context.setOffline(false)
    await expect(page.getByTestId('scoresheet-submitted').locator('visible=true').first()).toBeVisible({ timeout: 10_000 })
    await expect(waiting).toHaveCount(0)
    expect(pageErrors).toEqual([])
})

test('the submitted badge waits for the submit itself, and a slow draft save cannot overwrite it', async ({ page }) => {
    test.setTimeout(60_000)
    const { judgingCompetitionId } = loadFixtures()

    // Draft saves reach the server 800 ms late, so they would land after the submit if requests overlapped.
    await page.route('**/scoresheets', async (route) => {
        const body = route.request().postDataJSON()
        if (body?.status === 'draft') await new Promise((resolve) => setTimeout(resolve, 800))
        await route.continue()
    })

    await login(page)
    await clearIndexedDB(page)
    await page.goto(`/judging/${judgingCompetitionId}`)
    await page.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
    await page.getByTestId('entry-expand-button').first().click()

    for (const [label, value] of scores) {
        await page.locator(`[role="group"][aria-label="${label}"]:visible`).first()
            .locator('button').filter({ hasText: new RegExp(`^${value}$`) }).click()
    }
    await page.getByRole('button', { name: /▶/ }).locator('visible=true').first().click()
    await expect(page.getByTestId('scoresheet-submitted').locator('visible=true').first()).toBeVisible({ timeout: 15_000 })

    expect(judgeSheets('submitted')).toBe('1')
    await page.waitForTimeout(2_000)
    expect(judgeSheets('submitted')).toBe('1')
})

async function openFirstEntry(page: Page): Promise<number> {
    const { judgingCompetitionId } = loadFixtures()
    await login(page)
    await clearIndexedDB(page)
    await page.goto(`/judging/${judgingCompetitionId}`)
    await page.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
    await page.waitForLoadState('networkidle')
    await page.getByTestId('entry-expand-button').first().click()
    return judgingCompetitionId
}

async function scoreAndSubmit(page: Page): Promise<void> {
    for (const [label, value] of scores) {
        await page.locator(`[role="group"][aria-label="${label}"]:visible`).first()
            .locator('button').filter({ hasText: new RegExp(`^${value}$`) }).click()
    }
    await page.getByRole('button', { name: /▶/ }).locator('visible=true').first().click()
}

test('a first save that never gets an answer does not block the submit', async ({ page }) => {
    test.setTimeout(90_000)
    let first = true
    await page.route('**/scoresheets', async (route: Route) => {
        if (first) {
            first = false
            return // never answered: connected, but the traffic is dropped
        }
        await route.continue()
    })
    await openFirstEntry(page)
    await scoreAndSubmit(page)

    await expect(page.getByTestId('scoresheet-submitted').locator('visible=true').first()).toBeVisible({ timeout: 45_000 })
    expect(judgeSheets('submitted')).toBe('1')
})

test('a reload while the submit is in flight keeps the submit', async ({ page }) => {
    test.setTimeout(90_000)
    let competitionId = 0
    let fired = false
    await page.route('**/scoresheets', async (route: Route) => {
        const body = route.request().postDataJSON()
        if (body?.status === 'draft') {
            await new Promise((resolve) => setTimeout(resolve, 1_500))
            return route.continue()
        }
        if (fired) return route.continue()
        // The first submit is cut off by a reload before the server sees it.
        fired = true
        page.goto(`/judging/${competitionId}`).catch(() => {})
        await new Promise((resolve) => setTimeout(resolve, 300))
        await route.abort().catch(() => {})
    })
    competitionId = await openFirstEntry(page)
    await scoreAndSubmit(page).catch(() => {})

    // The draft response must not have marked the sheet synced in IndexedDB: the reloaded page
    // requeues it and sends the submit.
    await expect.poll(() => judgeSheets('submitted'), { timeout: 30_000 }).toBe('1')
})

test('an aborted draft that reaches the server late cannot overwrite the submit', async ({ page }) => {
    test.setTimeout(90_000)
    let first = true
    await page.route('**/scoresheets', async (route: Route) => {
        if (first) {
            first = false
            // Slow uplink: the client gives up after 15 s, but the bytes reach the server at 25 s.
            await new Promise((resolve) => setTimeout(resolve, 25_000))
            await route.fetch().catch(() => {})
            await route.abort().catch(() => {})
            return
        }
        await route.continue()
    })
    await openFirstEntry(page)
    await scoreAndSubmit(page)

    await expect(page.getByTestId('scoresheet-submitted').locator('visible=true').first()).toBeVisible({ timeout: 30_000 })
    // Wait until the late draft has been delivered, then check the server still has the submit.
    await page.waitForTimeout(12_000)
    expect(judgeSheets('submitted')).toBe('1')
    expect(judgeSheets('draft')).toBe('0')
})

const clickScore = (page: Page, label: string, value: string) =>
    page.locator(`[role="group"][aria-label="${label}"]:visible`).first()
        .locator('button').filter({ hasText: new RegExp(`^${value}$`) }).click()

const judgeSheetJson = () =>
    tinker(`echo json_encode(\\App\\Models\\Scoresheet::whereHas('judge', fn ($q) => $q->where('email', '${JUDGE_EMAIL}'))->get(['status', 'aroma', 'appearance', 'flavour', 'mouthfeel', 'overall']));`)

test('a submit that the server ignores because another device saved newer adopts the server sheet', async ({ browser }) => {
    test.setTimeout(90_000)
    const tabB = await (await browser.newContext()).newPage()
    const tabA = await (await browser.newContext()).newPage()

    await openFirstEntry(tabB)
    for (const [label, value] of scores) {
        await clickScore(tabB, label, value)
        await tabB.waitForTimeout(300)
    }
    await expect.poll(() => judgeSheets('draft'), { timeout: 10_000 }).toBe('1')

    await openFirstEntry(tabA)
    await clickScore(tabA, 'Aroma score, 0 to 12', '10')
    await expect.poll(() => judgeSheetJson(), { timeout: 10_000 }).toContain('"aroma":10')
    const serverBefore = judgeSheetJson()

    await tabB.getByRole('button', { name: /▶/ }).locator('visible=true').first().click()

    const header = tabB.getByTestId('entry-expand-button').first()
    await expect(tabB.getByTestId('entry-updated-notice').first()).toBeVisible()
    await expect(tabB.getByTestId('entry-updated-notice').first()).toHaveText('Updated from another device')
    await expect(header.getByText('Saved ✓').locator('visible=true')).toHaveCount(1)
    await expect(header.getByText('Submitted ✓').locator('visible=true')).toHaveCount(0)
    await expect(tabB.locator('[role="group"][aria-label="Aroma score, 0 to 12"]:visible').first()
        .locator('button').filter({ hasText: /^10$/ })).toHaveAttribute('aria-pressed', 'true')
    expect(judgeSheetJson()).toBe(serverBefore)
    expect(judgeSheets('submitted')).toBe('0')
})

test('an edit that the server ignores because another device saved first shows the server scores', async ({ browser }) => {
    test.setTimeout(90_000)
    const tabB = await (await browser.newContext()).newPage()
    const tabA = await (await browser.newContext()).newPage()

    await openFirstEntry(tabB) // loads first: empty sheet, revision 0
    await openFirstEntry(tabA)
    for (const [label, value] of scores) {
        await clickScore(tabA, label, value)
        await tabA.waitForTimeout(300)
    }
    await expect.poll(() => judgeSheetJson(), { timeout: 10_000 }).toContain('"overall":8')
    const serverBefore = judgeSheetJson()

    await clickScore(tabB, 'Aroma score, 0 to 12', '5')

    const header = tabB.getByTestId('entry-expand-button').first()
    await expect(tabB.getByTestId('entry-updated-notice').first()).toBeVisible()
    await expect(header).toContainText('38 / 50')
    await expect(tabB.locator('[role="group"][aria-label="Aroma score, 0 to 12"]:visible').first()
        .locator('button').filter({ hasText: /^9$/ })).toHaveAttribute('aria-pressed', 'true')
    expect(judgeSheetJson()).toBe(serverBefore)

    // The next local edit starts from the server revision and applies.
    await clickScore(tabB, 'Aroma score, 0 to 12', '7')
    await expect.poll(() => judgeSheetJson(), { timeout: 10_000 }).toContain('"aroma":7')
    await expect(tabB.getByTestId('entry-updated-notice').first()).toBeHidden()
})

// The server applies the first POST, but the browser never sees the answer (later POSTs pass through).
async function loseFirstResponse(page: Page): Promise<void> {
    let count = 0
    await page.route('**/scoresheets', async (route: Route) => {
        count++
        if (count === 1) {
            await route.fetch()
            await route.abort('connectionreset')
            return
        }
        await route.continue()
    })
}

const aromaNotes = () =>
    tinker(`echo \\App\\Models\\Scoresheet::whereHas('judge', fn ($q) => $q->where('email', '${JUDGE_EMAIL}'))->value('aroma_notes');`)

test('a retry of a save the server already applied shows no notice and leaves the notes field alone', async ({ page }) => {
    test.setTimeout(60_000)
    await openFirstEntry(page)
    await clickScore(page, 'Aroma score, 0 to 12', '7')
    await expect.poll(() => judgeSheets('draft'), { timeout: 10_000 }).toBe('1')
    await loseFirstResponse(page)

    // The server trims the stored notes; the retry must not read that as a change from another device.
    const notes = page.locator('textarea:visible').first()
    await notes.fill('Fruity, ')
    await page.waitForTimeout(8_000)

    await expect(page.getByTestId('entry-updated-notice').first()).toBeHidden()
    expect(await notes.inputValue()).toBe('Fruity, ')
})

test('a retry that is ignored while the judge keeps typing cannot overwrite the typed notes', async ({ page }) => {
    test.setTimeout(90_000)
    await openFirstEntry(page)
    await clickScore(page, 'Aroma score, 0 to 12', '7')
    await expect.poll(() => judgeSheets('draft'), { timeout: 10_000 }).toBe('1')
    await loseFirstResponse(page)

    const notes = page.locator('textarea:visible').first()
    await notes.fill('Fruity')
    await page.waitForTimeout(800) // the first save goes out and lands; its answer is lost, the retry follows in 5 s
    const typed = ' and a clean malt backbone with some light biscuit notes and a dry finish that lingers'
    await notes.pressSequentially(typed, { delay: 90 }) // about 7.5 s of typing, across the retry
    await page.waitForTimeout(2_000)

    expect(await notes.inputValue()).toBe('Fruity' + typed)
    await expect.poll(() => aromaNotes(), { timeout: 10_000 }).toBe('Fruity' + typed)
})

const sheetRows = () =>
    tinker(`echo json_encode(\\App\\Models\\Scoresheet::whereHas('judge', fn ($q) => $q->where('email', '${JUDGE_EMAIL}'))->get(['aroma', 'appearance', 'flavour', 'mouthfeel', 'overall']));`)

async function scoreAll(page: Page): Promise<void> {
    for (const [label, value] of scores) {
        await clickScore(page, label, value)
        await page.waitForTimeout(300)
    }
}

test('after adopting the other device\'s sheet a reload keeps it and a quick edit changes only that score', async ({ browser }) => {
    test.setTimeout(90_000)
    const tabB = await (await browser.newContext()).newPage()
    const tabA = await (await browser.newContext()).newPage()
    await openFirstEntry(tabB) // both tabs start with an empty sheet and their own uuid
    await openFirstEntry(tabA)
    await scoreAll(tabA)
    await expect.poll(() => sheetRows(), { timeout: 10_000 }).toContain('"overall":8')

    await clickScore(tabB, 'Aroma score, 0 to 12', '5') // ignored: tab B adopts tab A's sheet
    await expect(tabB.getByTestId('entry-updated-notice').first()).toBeVisible()

    await tabB.reload()
    await tabB.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
    await tabB.getByTestId('entry-expand-button').first().click()
    await clickScore(tabB, 'Overall Impression score, 0 to 10', '3')
    await expect.poll(() => sheetRows(), { timeout: 15_000 }).toContain('"overall":3')

    const row = JSON.parse(sheetRows())[0]
    expect([row.aroma, row.appearance, row.flavour, row.mouthfeel, row.overall]).toEqual([9, 2, 15, 4, 3])
    await expect(tabB.getByTestId('entry-updated-notice').first()).toBeHidden()
})

test('a note ending in an invisible character shows no notice after a retry', async ({ page }) => {
    test.setTimeout(60_000)
    await openFirstEntry(page)
    await clickScore(page, 'Aroma score, 0 to 12', '7')
    await expect.poll(() => judgeSheets('draft'), { timeout: 10_000 }).toBe('1')
    await loseFirstResponse(page)

    await page.locator('textarea:visible').first().fill('Fruity\u200B')
    await page.locator('textarea:visible').first().blur()
    await page.waitForTimeout(8_000)

    await expect(page.getByTestId('entry-updated-notice').first()).toBeHidden()
})

test('focus in another entry\'s notes does not make an ignored save overwrite the other device', async ({ browser }) => {
    test.setTimeout(90_000)
    const tabB = await (await browser.newContext()).newPage()
    const tabA = await (await browser.newContext()).newPage()
    await openFirstEntry(tabB)
    await clickScore(tabB, 'Aroma score, 0 to 12', '6')
    await expect.poll(() => judgeSheets('draft'), { timeout: 10_000 }).toBe('1')
    await openFirstEntry(tabA)

    await tabB.route('**/scoresheets', (route) => route.abort('connectionreset'))
    await clickScore(tabB, 'Aroma score, 0 to 12', '5') // queued on tab B
    await tabB.waitForTimeout(500)
    await clickScore(tabA, 'Aroma score, 0 to 12', '9') // lands on the server from tab A
    await expect.poll(() => sheetRows(), { timeout: 10_000 }).toContain('"aroma":9')
    await tabB.unroute('**/scoresheets')

    await tabB.getByTestId('entry-expand-button').nth(1).click()
    await expect(tabB.locator('textarea:visible')).toHaveCount(10) // both cards open, five notes fields each
    await tabB.locator('textarea:visible').last().focus()
    await tabB.waitForTimeout(7_000) // the queued save is retried and ignored while another entry's notes are focused

    expect(JSON.parse(sheetRows())[0].aroma).toBe(9)
})

const idbRows = (page: Page) => page.evaluate(() => new Promise<unknown[]>((resolve) => {
    const open = indexedDB.open('beerjudge')
    open.onsuccess = () => {
        const all = open.result.transaction('scoresheets', 'readonly').objectStore('scoresheets').getAll()
        all.onsuccess = () => resolve(all.result.map((r: any) => [r.client_uuid, r.revision, r.syncStatus, r.aroma]))
    }
}))

async function openSecondTab(page: Page, competitionId: number): Promise<Page> {
    const second = await page.context().newPage()
    await second.goto(`/judging/${competitionId}`)
    return second
}

test('a second tab of the same browser shows the banner and neither saves nor writes IndexedDB', async ({ page }) => {
    test.setTimeout(60_000)
    const competitionId = await openFirstEntry(page)
    await clickScore(page, 'Aroma score, 0 to 12', '7')
    await expect.poll(() => judgeSheets('draft'), { timeout: 10_000 }).toBe('1')

    const second = await openSecondTab(page, competitionId)
    const posts: string[] = []
    second.on('request', (request) => { if (request.method() === 'POST' && request.url().endsWith('/scoresheets')) posts.push(request.url()) })
    await expect(second.getByTestId('other-tab-banner')).toBeVisible()
    await expect(second.getByText('Judging is open in another tab.')).toBeVisible()
    await expect(second.getByRole('button', { name: 'Use this tab' })).toBeVisible()
    await expect(second.getByTestId('entry-expand-button').locator('visible=true')).toHaveCount(0)
    await expect(page.getByTestId('other-tab-banner')).toBeHidden()

    const rowsBefore = JSON.stringify(await idbRows(second))
    await second.waitForTimeout(7_000)
    expect(JSON.stringify(await idbRows(second))).toBe(rowsBefore)
    expect(posts).toEqual([])
})

test('"Use this tab" takes over, and the previous tab shows the banner and stops writing', async ({ page }) => {
    test.setTimeout(60_000)
    const competitionId = await openFirstEntry(page)
    const second = await openSecondTab(page, competitionId)
    await expect(second.getByTestId('other-tab-banner')).toBeVisible()

    await second.getByRole('button', { name: 'Use this tab' }).click()
    await expect(second.getByTestId('other-tab-banner')).toBeHidden()
    await expect(page.getByTestId('other-tab-banner')).toBeVisible()

    await second.getByTestId('entry-expand-button').first().click()
    await clickScore(second, 'Aroma score, 0 to 12', '9')
    await expect.poll(() => sheetRows(), { timeout: 10_000 }).toContain('"aroma":9')
})

test('taking over from a tab with a queued unsynced edit sends that edit', async ({ page }) => {
    test.setTimeout(60_000)
    const competitionId = await openFirstEntry(page)
    // Only the first tab's requests fail, and they keep failing: the edit can only reach the server from the second tab.
    await page.route('**/scoresheets', (route) => route.abort('connectionreset'))
    await clickScore(page, 'Aroma score, 0 to 12', '11')
    await expect(page.getByText('Waiting for connection').locator('visible=true').first()).toBeVisible()
    expect(judgeSheets('draft')).toBe('0')

    const second = await openSecondTab(page, competitionId)
    const posts: string[] = []
    second.on('request', (request) => { if (request.method() === 'POST' && request.url().endsWith('/scoresheets')) posts.push(request.postData() ?? '') })
    await expect(second.getByTestId('other-tab-banner')).toBeVisible()
    await second.getByRole('button', { name: 'Use this tab' }).click()

    await expect.poll(() => sheetRows(), { timeout: 15_000 }).toContain('"aroma":11')
    await expect.poll(() => posts.length, { timeout: 5_000 }).toBeGreaterThan(0)
    await expect(page.getByTestId('other-tab-banner')).toBeVisible()
})

test('an inactive second tab cannot delete the active tab\'s queued note', async ({ page }) => {
    test.setTimeout(60_000)
    const competitionId = await openFirstEntry(page)
    await page.route('**/scoresheets', (route) => route.abort('connectionreset'))
    await page.locator('textarea:visible').first().fill('Queued note')
    await expect(page.getByText('Waiting for connection').locator('visible=true').first()).toBeVisible()

    const second = await openSecondTab(page, competitionId)
    await expect(second.getByTestId('other-tab-banner')).toBeVisible()
    // The inactive tab stays open for a while and tries to save: it must not touch the store.
    await second.waitForTimeout(7_000)
    await second.evaluate(() => {
        const app = (document.body as any)._x_dataStack[0]
        return app.saveSheet(app.entries[0].id)
    })
    await second.close()
    await page.unroute('**/scoresheets')

    await page.reload()
    await page.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
    await page.getByTestId('entry-expand-button').first().click()
    await expect(page.locator('textarea:visible').first()).toHaveValue('Queued note')
    await expect.poll(() => aromaNotes(), { timeout: 15_000 }).toBe('Queued note')
})

test('one-tab banner screenshots at phone width (en and nl)', async ({ browser }) => {
    test.setTimeout(90_000)
    const { judgingCompetitionId } = loadFixtures()
    const root = path.resolve(__dirname, '../..')
    const dir = path.join(root, 'storage', 'logs', 'one-tab')
    fs.mkdirSync(dir, { recursive: true })
    const nlEmail = 'one-tab-nl@browser-test.local'
    tinker(`\\App\\Models\\Member::updateOrCreate(['email' => '${nlEmail}'], ['name' => 'One Tab', 'password' => \\Illuminate\\Support\\Facades\\Hash::make('${PASSWORD}'), 'is_admin' => false, 'is_active' => true, 'locale' => 'nl']);`)

    for (const [locale, email] of [['en', JUDGE_EMAIL], ['nl', nlEmail]] as const) {
        const context = await browser.newContext({ viewport: { width: 390, height: 844 } })
        const first = await context.newPage()
        await login(first, email)
        await first.goto(`/judging/${judgingCompetitionId}?register=1`)
        await first.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
        const second = await context.newPage()
        await second.goto(`/judging/${judgingCompetitionId}`)
        await expect(second.getByTestId('other-tab-banner')).toBeVisible()
        await second.screenshot({ path: path.join(dir, `banner-${locale}-390.png`) })
        if (locale === 'nl') {
            await expect(second.getByText('Het jureren is open in een ander tabblad.')).toBeVisible()
            await expect(second.getByRole('button', { name: 'Gebruik dit tabblad' })).toBeVisible()
        }
        await context.close()
    }
})

const appState = (page: Page) => page.evaluate(() => {
    const app = (document.body as any)._x_dataStack[0]
    return { inactive: app.inactive, sync: app.sheets[app.entries[0].id].syncStatus, queue: app.syncQueue.length }
})

test('a tab that lost the lock mid-save shows the sheet as synced after it takes the lock back', async ({ browser }) => {
    test.setTimeout(60_000)
    const context = await browser.newContext()
    const tabA = await context.newPage()
    const competitionId = await openFirstEntry(tabA)
    // Tab A's save is held for 3 s, so the steal happens while it is in flight.
    await tabA.route('**/scoresheets', async (route) => {
        await new Promise((resolve) => setTimeout(resolve, 3_000))
        await route.continue()
    })
    await clickScore(tabA, 'Aroma score, 0 to 12', '8')
    await tabA.waitForTimeout(300)
    const tabB = await openSecondTab(tabA, competitionId)
    await tabB.getByRole('button', { name: 'Use this tab' }).click()
    await tabB.waitForTimeout(4_000)
    await tabA.unroute('**/scoresheets')

    await tabA.bringToFront()
    await tabA.getByRole('button', { name: 'Use this tab' }).click()
    await expect.poll(async () => (await appState(tabA)).sync, { timeout: 10_000 }).toBe('synced')
    await expect(tabA.getByText('Saving…').locator('visible=true')).toHaveCount(0)
})

test('a queued sheet that the other tab synced shows as synced after taking the lock back', async ({ browser }) => {
    test.setTimeout(60_000)
    const context = await browser.newContext()
    const tabA = await context.newPage()
    const competitionId = await openFirstEntry(tabA)
    await context.route('**/scoresheets', (route) => route.abort('connectionreset'))
    await clickScore(tabA, 'Aroma score, 0 to 12', '7')
    await expect(tabA.getByText('Waiting for connection').locator('visible=true').first()).toBeVisible()
    const tabB = await openSecondTab(tabA, competitionId)
    await context.unroute('**/scoresheets')
    await tabB.getByRole('button', { name: 'Use this tab' }).click()
    await expect.poll(() => sheetRows(), { timeout: 15_000 }).toContain('"aroma":7')

    await tabA.bringToFront()
    await tabA.getByRole('button', { name: 'Use this tab' }).click()
    await expect.poll(async () => (await appState(tabA)).sync, { timeout: 10_000 }).toBe('synced')
    await expect(tabA.getByText('Waiting for connection').locator('visible=true')).toHaveCount(0)
    expect((await appState(tabA)).queue).toBe(0)
})

test('losing the lock while activating leaves no online listener or timer on the inactive tab', async ({ browser }) => {
    test.setTimeout(90_000)
    const context = await browser.newContext()
    await context.addInitScript(() => {
        const live = new Set<unknown>();
        (window as any).__online = live
        const add = window.addEventListener.bind(window)
        const remove = window.removeEventListener.bind(window)
        window.addEventListener = ((type: string, listener: any, options?: any) => { if (type === 'online') live.add(listener); return add(type, listener, options) }) as any
        window.removeEventListener = ((type: string, listener: any, options?: any) => { if (type === 'online') live.delete(listener); return remove(type, listener, options) }) as any
    })
    const tabA = await context.newPage()
    const competitionId = await openFirstEntry(tabA)
    const tabB = await openSecondTab(tabA, competitionId)
    await expect(tabB.getByTestId('other-tab-banner')).toBeVisible()

    for (let round = 0; round < 20; round++) {
        // B takes over, and A steals back while B is still inside activate(); the delay sweeps the interleavings.
        await tabB.evaluate(() => { (document.body as any)._x_dataStack[0].takeOver() })
        await tabA.evaluate((delay) => new Promise<void>((resolve) => setTimeout(() => { (document.body as any)._x_dataStack[0].takeOver(); resolve() }, delay)), round % 6)
        await tabA.waitForTimeout(250)
        for (const tab of [tabA, tabB]) {
            const state = await tab.evaluate(() => ({ inactive: (document.body as any)._x_dataStack[0].inactive, online: (window as any).__online.size }))
            if (state.inactive) expect(state.online).toBe(0)
        }
    }
})
