import fs from 'node:fs'
import { journey, login, expect, tinker, STATE } from './lib.mjs'
const { competitionId } = JSON.parse(fs.readFileSync(STATE))

async function pick(page, nth, typed, text) {
  await page.getByRole('button', { name: 'Select an option' }).nth(nth).click()
  if (typed) await page.keyboard.type(typed, { delay: 50 })
  await page.getByRole('option', { name: new RegExp(text) }).first().click()
  await page.waitForTimeout(500)
}

await journey('A3-manage-entries', async actor => {
  const { page, step } = await actor('member2-admin')
  await login(page, 'member2@beerjudge.local')
  await step('entries tab lists member entries', async () => {
    await page.goto(`/admin/competitions/${competitionId}/edit?tab=entries%3A%3Adata%3A%3Atab`)
    await page.waitForLoadState('networkidle')
    await expect(page.locator('[id$=".abv"]')).toHaveCount(5)
  })
  await step('add entry on behalf of member7', async () => {
    await page.getByRole('button', { name: 'Add to entries' }).click()
    await page.waitForTimeout(800)
    const before = await page.getByRole('button', { name: 'Select an option' }).count()
    await pick(page, before - 2, 'Member', 'Member 7|Member Seven')
    await pick(page, 0, null, 'Stout|IPA')
    await page.locator('[id$=".abv"]').last().fill('8.1')
  })
  await step('save entries', async () => {
    await page.getByRole('button', { name: /^Save changes$/ }).click()
    await expect(page.getByText(/Saved/).first()).toBeVisible()
    const n = tinker(`echo App\\Models\\Entry::where("competition_id", ${competitionId})->count();`)
    if (n !== '6') throw new Error('entries in db: ' + n)
  })
  await step('sort by ABV rewrites tasting order', async () => {
    await page.getByRole('button', { name: /Sort by ABV/ }).click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(800)
    const order = tinker(`echo App\\Models\\Entry::where("competition_id", ${competitionId})->orderBy("sort_order")->pluck("abv")->implode(",");`)
    console.log('  abv by sort_order:', order)
    const nums = order.split(',').map(Number)
    if (nums.some((v, i) => i && v < nums[i - 1])) throw new Error('not ascending: ' + order)
  })
  await step('probe: Sort by ABV with an unsaved new row', async () => {
    await page.getByRole('tab', { name: 'Entries' }).click()
    await page.getByRole('button', { name: 'Add to entries' }).click()
    await page.waitForTimeout(800)
    await page.locator('[id$=".abv"]').last().fill('3.3')
    await page.getByRole('button', { name: /Sort by ABV/ }).click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(800)
    globalThis.activeTab = await page.locator('.fi-tabs-item.fi-active').first().innerText()
    console.log('  tab after sort:', globalThis.activeTab, '| entries in db:', tinker(`echo App\\Models\\Entry::where("competition_id", ${competitionId})->count();`))
  })
})
