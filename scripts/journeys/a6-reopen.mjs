import fs from 'node:fs'
import { journey, login, expect, modal, logMark, logSince, tinker, STATE } from './lib.mjs'
const { competitionId } = JSON.parse(fs.readFileSync(STATE))

await journey('A6-reopen-and-reclose', async actor => {
  const { page, step } = await actor('member2-admin')
  const member = await actor('member5')
  await login(page, 'member2@beerjudge.local')
  await login(member.page, 'member5@beerjudge.local')
  await step('reopen judging with confirmation', async () => {
    globalThis.mark = logMark()
    await page.goto(`/admin/competitions/${competitionId}/edit`)
    await page.waitForLoadState('networkidle')
    await page.getByRole('button', { name: 'Reopen judging' }).click()
    await expect(modal(page)).toContainText('no new results email')
    await modal(page).getByRole('button', { name: /Confirm/ }).click()
    await page.waitForLoadState('networkidle')
    await expect(page.getByRole('button', { name: 'Move to Closed' })).toBeVisible()
  })
  await member.step('member results hidden again while reopened', async () => {
    const r = await member.page.goto(`/competitions/${competitionId}/results`)
    console.log('  member results status while reopened:', r.status())
  })
  await step('close again sends no second results mail', async () => {
    await page.getByRole('button', { name: 'Move to Closed' }).click()
    await modal(page).getByRole('button', { name: /Confirm/ }).click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1500)
    const mails = (logSince(globalThis.mark).match(/To: /g) ?? []).length
    if (mails) throw new Error(mails + ' mails sent')
    if (tinker(`echo App\\Models\\Competition::find(${competitionId})->status->value;`) !== 'closed') throw new Error('not closed')
  })
})
