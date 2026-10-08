import { journey, login, expect, tinker } from './lib.mjs'

await journey('A7-styles', async actor => {
  const { page, step } = await actor('member2-admin')
  await login(page, 'member2@beerjudge.local')
  await step('create custom style', async () => {
    await page.goto('/admin/styles/create')
    await page.waitForLoadState('networkidle')
    await page.locator('[id="form.code"]').fill('X1')
    await page.locator('[id="form.category_name"]').fill('Club Specials').catch(() => {})
    await page.locator('[id="form.name"]').fill('Journey Gruit')
    await page.getByRole('button', { name: /^Create$/ }).click()
    await page.waitForURL(/\/admin\/styles\/\d+\/edit/)
    const src = tinker('echo App\\Models\\Style::where("name","Journey Gruit")->value("source");')
    if (src !== 'custom') throw new Error('source ' + src)
  })
  await step('style list filtered to custom', async () => {
    await page.goto('/admin/styles')
    await page.waitForLoadState('networkidle')
    await page.getByPlaceholder('Search').fill('Gruit')
    await page.waitForTimeout(1200)
    await expect(page.getByRole('row', { name: /Journey Gruit/ })).toBeVisible()
  })
})
