import fs from 'node:fs'
import { journey, login, expect, tinker, STATE } from './lib.mjs'

await journey('A2-setup-competition', async actor => {
  const { page, step } = await actor('member2-admin')
  await login(page, 'member2@beerjudge.local')
  await step('create competition form', async () => {
    await page.goto('/admin/competitions/create')
    await page.waitForLoadState('networkidle')
    await page.locator('[id="form.name"]').fill('Journey Cup 2026')
    await page.locator('[id="form.year"]').fill('2026')
  })
  await step('create saves as draft', async () => {
    await page.getByRole('button', { name: /^Create$/ }).click()
    await page.waitForURL(/\/admin\/competitions\/\d+\/edit/)
    const id = page.url().match(/competitions\/(\d+)/)[1]
    fs.writeFileSync(STATE, JSON.stringify({ competitionId: Number(id) }))
    await expect(page.getByRole('button', { name: 'Move to Open' })).toBeVisible()
  })
  await step('styles tab: search and tick IPA styles', async () => {
    await page.getByRole('tab', { name: 'Styles' }).click()
    await page.getByPlaceholder('Search styles…').fill('IPA')
    await page.waitForTimeout(500)
    const boxes = page.locator('.fi-fo-checkbox-list input[type="checkbox"]:visible')
    const count = await boxes.count()
    if (count < 3) throw new Error('only ' + count + ' visible checkboxes after search')
    for (let i = 0; i < 3; i++) await boxes.nth(i).check()
  })
  await step('styles tab: add Stout via search', async () => {
    await page.getByPlaceholder('Search styles…').fill('Stout')
    await page.waitForTimeout(500)
    await page.locator('.fi-fo-checkbox-list input[type="checkbox"]:visible').first().check()
    await page.getByPlaceholder('Search styles…').fill('')
  })
  await step('save styles', async () => {
    await page.getByRole('button', { name: /^Save changes$/ }).click()
    await expect(page.getByText(/Saved/i).first()).toBeVisible()
    const n = tinker(`echo App\\Models\\Competition::find(${JSON.parse(fs.readFileSync(STATE)).competitionId})->styles()->count();`)
    if (Number(n) < 4) throw new Error('styles saved: ' + n)
  })
  await step('member entry link shown on entries tab', async () => {
    await page.getByRole('tab', { name: 'Entries' }).click()
    await expect(page.getByText(/\/entries\/create/).first()).toBeVisible()
  })
  await step('move to open rebuilds the header without a reload', async () => {
    await page.getByRole('button', { name: 'Move to Open' }).click()
    await expect(page.getByRole('button', { name: 'Move to Judging' })).toBeVisible()
    await expect(page.getByRole('button', { name: 'Move to Open' })).toHaveCount(0)
  })
  await step('competition list shows Open', async () => {
    await page.goto('/admin/competitions')
    await expect(page.getByRole('row', { name: /Journey Cup 2026/ })).toContainText('Open')
  })
})
