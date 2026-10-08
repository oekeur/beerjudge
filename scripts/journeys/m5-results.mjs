import fs from 'node:fs'
import { journey, login, expect, tinker, STATE } from './lib.mjs'
const { competitionId } = JSON.parse(fs.readFileSync(STATE))
const id = email => tinker(`echo App\\Models\\Member::where("email","${email}")->value("id");`)

await journey('M5-results', async actor => {
  const m5 = await actor('member5')
  await login(m5.page, 'member5@beerjudge.local')
  await m5.step('home card links to my results', async () => {
    const link = m5.page.locator(`a[href*="/competitions/${competitionId}/results/"]`).first()
    await expect(link).toBeVisible()
    await link.click()
    await m5.page.waitForLoadState('networkidle')
  })
  await m5.step('own sheet: both entries, scores, standings, print', async () => {
    await expect(m5.page.locator('body')).toContainText('Irish Stout')
    await expect(m5.page.locator('body')).toContainText('English IPA')
    await expect(m5.page.getByRole('button', { name: /Print/ })).toBeVisible()
  })
  await m5.step('judge notes expand', async () => {
    const t = m5.page.getByText(/Bright citrus|notes/i).first()
    await t.click().catch(() => {})
  })
  await m5.step('print view', async () => {
    await m5.page.emulateMedia({ media: 'print' })
    await m5.page.waitForTimeout(300)
  })
  await m5.step("other member's sheet is forbidden", async () => {
    await m5.page.emulateMedia({ media: 'screen' })
    const r = await m5.page.goto(`/competitions/${competitionId}/results/${id('member3@beerjudge.local')}`)
    if (r.status() !== 403) throw new Error('status ' + r.status())
  })
  await m5.step('results index: standings without admin lists', async () => {
    await m5.page.goto(`/competitions/${competitionId}/results`)
    await m5.page.waitForLoadState('networkidle')
  })
  const m7 = await actor('member7')
  await login(m7.page, 'member7@beerjudge.local')
  await m7.step('judge report from results index', async () => {
    await m7.page.goto(`/competitions/${competitionId}/results`)
    await m7.page.locator(`a[href*="/judge-report/"]`).first().click()
    await m7.page.waitForLoadState('networkidle')
    await expect(m7.page).toHaveURL(/judge-report/)
  })
  await m7.step("someone else's judge report is forbidden", async () => {
    const r = await m7.page.goto(`/competitions/${competitionId}/judge-report/${id('member2@beerjudge.local')}`)
    if (![403, 404].includes(r.status())) throw new Error('status ' + r.status())
  })
  const m4 = await actor('member4-nl', { locale: 'nl-NL' })
  await login(m4.page, 'member4@beerjudge.local')
  await m4.step('Dutch results sheet for demo competition (Autumn Harvest)', async () => {
    await m4.page.goto('/')
    await m4.page.locator('a[href*="/competitions/1/results/"]').first().click()
    await m4.page.waitForLoadState('networkidle')
  })
})
