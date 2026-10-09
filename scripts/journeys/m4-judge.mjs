import fs from 'node:fs'
import { journey, login, expect, tinker, STATE } from './lib.mjs'
const { competitionId } = JSON.parse(fs.readFileSync(STATE))
const GROUPS_BY_LOCALE = {
  en: ['Aroma score, 0 to 12', 'Appearance score, 0 to 3', 'Flavour score, 0 to 20', 'Mouthfeel score, 0 to 5', 'Overall Impression score, 0 to 10'],
  nl: ['Aroma score, 0 tot 12', 'Uiterlijk score, 0 tot 3', 'Smaak score, 0 tot 20', 'Mondgevoel score, 0 tot 5', 'Algehele Indruk score, 0 tot 10'],
}
const NOTES_LABEL = { en: 'Aroma notes', nl: 'Aroma notities' }
const WAITING = { en: 'Waiting for connection', nl: 'Wacht op verbinding' }
const RETRY = { en: 'Retry', nl: 'Opnieuw' }
const sheets = who => tinker(`echo App\\Models\\Scoresheet::where("status","submitted")->whereHas("judge", fn($q)=>$q->where("email","${who}@beerjudge.local"))->whereHas("entry", fn($q)=>$q->where("competition_id",${competitionId}))->count();`)

async function openScreen(page) {
  await page.goto(`/judging/${competitionId}`)
  await page.waitForSelector('[data-testid="entry-expand-button"]', { state: 'visible' })
  await page.waitForLoadState('networkidle')
}
// opts.lang: 'nl' for the members that run with locale nl-NL (member3, member4); default 'en'
async function score(page, i, s, opts = {}) {
  const lang = opts.lang ?? 'en'
  const GROUPS = GROUPS_BY_LOCALE[lang]
  await page.getByTestId('entry-expand-button').nth(i).click()
  const aroma = page.locator(`[role="group"][aria-label="${GROUPS[0]}"]:visible`).first()
  await aroma.waitFor({ state: 'visible', timeout: 10000 })
  for (let g = 0; g < 5; g++) {
    await page.locator(`[role="group"][aria-label="${GROUPS[g]}"]:visible`).first().locator('button').filter({ hasText: new RegExp(`^${s[g]}$`) }).click()
  }
  if (opts.notes) await page.locator(`textarea[aria-label="${NOTES_LABEL[lang]}"]:visible`).first().fill(opts.notes)
  if (opts.descriptor) {
    await page.getByText(/Off-Flavou?r Descriptors/).locator('visible=true').first().click()
    await page.locator('input[type="checkbox"]:visible').first().check()
  }
  if (opts.noSubmit) return
  await page.waitForLoadState('networkidle')
  await page.getByRole('button', { name: /▶/ }).locator('visible=true').first().click()
  if (!opts.offline) await expect(page.getByTestId('scoresheet-submitted').locator('visible=true').first()).toBeVisible({ timeout: 15000 })
  await page.getByTestId('entry-expand-button').nth(i).click()
}

await journey('M4-judge', async actor => {
  // member7: brewer who joins through the judge link
  const m7 = await actor('member7')
  await login(m7.page, 'member7@beerjudge.local')
  await m7.step('join as judge via ?register=1 link', async () => {
    await m7.page.goto(`/judging/${competitionId}?register=1`)
    await m7.page.waitForLoadState('networkidle')
    await expect(m7.page.getByRole('button', { name: 'Leave as judge' })).toBeVisible()
  })
  await m7.step('own entry hidden from the list', async () => {
    await openScreen(m7.page)
    await expect(m7.page.getByTestId('entry-expand-button')).toHaveCount(5)
    for (const t of await m7.page.getByTestId('entry-expand-button').allInnerTexts()) if (t.includes('Member Seven')) throw new Error('own entry listed')
  })
  await m7.step('style guide sheet opens from a section', async () => {
    await m7.page.getByTestId('entry-expand-button').first().click()
    await m7.page.locator('button[aria-label^="View BJCP aroma guideline"]:visible').first().click()
    await m7.page.waitForTimeout(600)
    const n = await m7.page.getByText(/^aroma$/i).locator('visible=true').count()
    if (n < 2) throw new Error('guide panel not shown (' + n + ' aroma labels visible)')
  })
  await m7.step('close style guide', async () => {
    await m7.page.keyboard.press('Escape')
    await m7.page.waitForTimeout(400)
    await m7.page.getByTestId('entry-expand-button').first().click()
  })
  await m7.step('score, note, descriptor and submit entry 1', async () => {
    await score(m7.page, 0, [10, 3, 18, 4, 9], { notes: 'Bright citrus, clean.', descriptor: true })
  })
  await m7.step('reopen submitted sheet and resave', async () => {
    await m7.page.getByTestId('entry-expand-button').nth(0).click()
    await m7.page.getByRole('button', { name: /Reopen to edit/ }).locator('visible=true').first().click()
    await m7.page.locator(`[role="group"][aria-label="${GROUPS_BY_LOCALE.en[2]}"]:visible`).first().locator('button').filter({ hasText: /^15$/ }).click()
    await m7.page.waitForLoadState('networkidle')
    await m7.page.getByRole('button', { name: /▶/ }).locator('visible=true').first().click()
    await expect(m7.page.getByTestId('scoresheet-submitted').locator('visible=true').first()).toBeVisible({ timeout: 15000 })
    const f = tinker(`echo App\\Models\\Scoresheet::whereHas("judge", fn($q)=>$q->where("email","member7@beerjudge.local"))->value("flavour");`)
    if (f !== '15') throw new Error('flavour in db ' + f)
    await m7.page.getByTestId('entry-expand-button').nth(0).click()
  })
  await m7.step('score entries 2 and 3', async () => {
    await score(m7.page, 1, [7, 2, 12, 3, 6])
    await score(m7.page, 2, [8, 2, 13, 3, 7])
    const n = sheets('member7'); if (n !== '3') throw new Error('submitted ' + n)
  })
  await m7.step('ranked sort view', async () => {
    await m7.page.getByRole('button', { name: 'My ranking' }).click()
    await m7.page.waitForTimeout(400)
  })

  // member2: admin judge, scores everything, extreme scores on entry 1 to trigger variance
  const m2 = await actor('member2-admin')
  await login(m2.page, 'member2@beerjudge.local')
  await m2.step('admin sees all 6 entries and the progress panel', async () => {
    await openScreen(m2.page)
    await expect(m2.page.getByTestId('entry-expand-button')).toHaveCount(6)
  })
  await m2.step('admin scores all six entries', async () => {
    const plans = [[3, 1, 5, 1, 2], [9, 3, 16, 4, 8], [6, 2, 10, 3, 5], [11, 3, 18, 5, 9], [5, 2, 9, 2, 4], [8, 2, 14, 3, 7]]
    for (let i = 0; i < 6; i++) await score(m2.page, i, plans[i])
    const n = sheets('member2'); if (n !== '6') throw new Error('submitted ' + n)
  })

  // member3: Dutch, admin-assigned, on a phone, loses connection mid-session
  const m3 = await actor('member3-mobile-nl', { viewport: { width: 390, height: 844 }, locale: 'nl-NL' })
  await login(m3.page, 'member3@beerjudge.local')
  await m3.step('Dutch judging screen on mobile, own entry hidden', async () => {
    await openScreen(m3.page)
    await expect(m3.page.getByTestId('entry-expand-button')).toHaveCount(5)
  })
  await m3.step('score entry 1 online', async () => { await score(m3.page, 0, [9, 2, 15, 4, 8], { lang: 'nl' }) })
  await m3.step('go offline, score and submit entry 2: badge reads as waiting', async () => {
    await m3.context.setOffline(true)
    await score(m3.page, 1, [6, 2, 11, 3, 6], { offline: true, lang: 'nl' })
    await expect(m3.page.getByText(WAITING.nl).locator('visible=true').first()).toBeVisible()
    await m3.page.waitForTimeout(7000)
    await expect(m3.page.getByText(WAITING.nl).locator('visible=true').first()).toBeVisible()
    await expect(m3.page.getByText(RETRY.nl).locator('visible=true')).toHaveCount(0)
    const n = sheets('member3'); if (n !== '1') throw new Error('submitted in db while offline: ' + n)
  })
  await m3.step('back online without reloading, queued sheet syncs', async () => {
    await m3.context.setOffline(false)
    await expect(m3.page.getByText(WAITING.nl).locator('visible=true')).toHaveCount(0, { timeout: 10000 })
    const n = sheets('member3'); if (n !== '2') throw new Error('submitted ' + n)
  })

  // member4: Dutch, registers via the banner then leaves again
  const m4 = await actor('member4-nl', { locale: 'nl-NL' })
  await login(m4.page, 'member4@beerjudge.local')
  await m4.step('register via banner', async () => {
    await openScreen(m4.page)
    await m4.page.getByRole('button', { name: 'Registreer als jurylid' }).click()
    await m4.page.waitForLoadState('networkidle')
    await expect(m4.page.getByRole('button', { name: 'Afmelden als jurylid' })).toBeVisible()
  })
  await m4.step('leave as judge', async () => {
    await m4.page.getByRole('button', { name: 'Afmelden als jurylid' }).click()
    await m4.page.waitForLoadState('networkidle')
    await expect(m4.page.getByRole('button', { name: 'Registreer als jurylid' })).toBeVisible()
  })
  await m4.step('results not yet visible to members', async () => {
    const r = await m4.page.goto(`/competitions/${competitionId}/results`)
    if (r.status() !== 403) throw new Error('status ' + r.status())
  })
})
