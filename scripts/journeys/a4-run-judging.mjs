import fs from 'node:fs'
import { journey, login, expect, tinker, STATE } from './lib.mjs'
const { competitionId } = JSON.parse(fs.readFileSync(STATE))

await journey('A4-run-judging', async actor => {
  const { page, step } = await actor('member2-admin')
  await login(page, 'member2@beerjudge.local')
  await page.goto(`/admin/competitions/${competitionId}/edit`)
  await page.waitForLoadState('networkidle')
  await step('move to judging asks for confirmation', async () => {
    await page.getByRole('button', { name: 'Move to Judging' }).click()
    await expect(page.getByRole('dialog')).toContainText('Lock Registration')
  })
  await step('confirm assigns bottle numbers', async () => {
    await page.getByRole('dialog').getByRole('button', { name: /Confirm/ }).click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)
    const b = tinker(`echo App\\Models\\Entry::where("competition_id", ${competitionId})->orderBy("bottle_number")->pluck("bottle_number")->implode(",");`)
    console.log('  bottle numbers:', b)
    if (!/^1,2,3,4,5,6$/.test(b)) throw new Error('bottle numbers ' + b)
  })
  await step('header after reload offers close and judging screen', async () => {
    await page.reload()
    await expect(page.getByRole('button', { name: 'Move to Closed' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Open Judging Screen' })).toBeVisible()
  })
  await step('entry page now refused to members (registration locked)', async () => {
    const r = await page.request.get(`/competitions/${competitionId}/entries/create`)
    if (r.status() !== 403) throw new Error('status ' + r.status())
  })
  await step('dashboard: assign member2 and member3 as judges', async () => {
    await page.getByRole('tab', { name: 'Dashboard' }).click()
    await page.waitForTimeout(500)
    for (const name of ['Member 2', 'Member 3']) {
      await page.getByRole('button', { name: /Select an option|Member \d/ }).first().click().catch(() => {})
      await page.keyboard.type(name.split(' ')[1], { delay: 50 })
      await page.getByRole('option', { name }).first().click()
      await page.keyboard.press('Escape')
    }
    await page.getByRole('button', { name: /^Save changes$/ }).click()
    await expect(page.getByText(/Saved/).first()).toBeVisible()
    const j = tinker(`echo App\\Models\\Competition::find(${competitionId})->judges()->pluck("name")->sort()->implode(",");`)
    console.log('  judges:', j)
    if (!j.includes('Member 2') || !j.includes('Member 3')) throw new Error('judges ' + j)
  })
  await step('dashboard shows judge join link and progress widget', async () => {
    await expect(page.getByRole('button', { name: 'Copy' }).first()).toBeVisible()
    await expect(page.getByText(/View all results/i).first()).toBeVisible()
    await page.waitForTimeout(6000)
    const tile = await page.getByText('Judges', { exact: true }).last().locator('..').innerText()
    console.log('  judges tile after poll:', tile.replace(/\s+/g, ' '))
    if (!/\b2\b/.test(tile)) throw new Error('judges tile: ' + tile)
  })
})
