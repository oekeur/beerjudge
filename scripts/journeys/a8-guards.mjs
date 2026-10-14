import { journey, login, expect, modal, tinker } from './lib.mjs'
const id = email => tinker(`echo App\\Models\\Member::where("email","${email}")->value("id");`)

await journey('A8-admin-guards', async actor => {
  const { page, step } = await actor('member2-admin')
  await login(page, 'member2@beerjudge.local')
  const m1 = id('member1@beerjudge.local'), m2 = id('member2@beerjudge.local')
  async function setToggle(memberId, field, on) {
    await page.goto(`/admin/members/${memberId}/edit`)
    await page.waitForLoadState('networkidle')
    const t = page.locator(`[id="form.${field}"]`)
    const cur = (await t.getAttribute('aria-checked')) === 'true' || await t.isChecked().catch(() => false)
    if (cur !== on) await t.click()
    await page.getByRole('button', { name: /^Save changes$/ }).click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(800)
  }
  await step('demote member1 (another admin remains)', async () => {
    await setToggle(m1, 'is_admin', false)
    if (tinker('echo (int) App\\Models\\Member::where("email","member1@beerjudge.local")->value("is_admin");') !== '0') throw new Error('not demoted')
  })
  await step('demoting the last active admin is refused', async () => {
    await setToggle(m2, 'is_admin', false)
    if (tinker('echo (int) App\\Models\\Member::where("email","member2@beerjudge.local")->value("is_admin");') !== '1') throw new Error('last admin demoted')
  })
  await step('deleting the last active admin is refused', async () => {
    await page.goto(`/admin/members/${m2}/edit`)
    await page.waitForLoadState('networkidle')
    const del = page.getByRole('button', { name: 'Delete' })
    if (await del.isVisible()) {
      await del.click()
      const d = modal(page); if (await d.isVisible()) await d.getByRole('button', { name: /Confirm|Delete/ }).click()
      await page.waitForTimeout(1000)
    }
    if (tinker('echo App\\Models\\Member::where("email","member2@beerjudge.local")->count();') !== '1') throw new Error('deleted')
  })
  await step('restore member1 as admin, then deactivate', async () => {
    await setToggle(m1, 'is_admin', true)
    await setToggle(m1, 'is_active', false)
    if (tinker('echo (int) App\\Models\\Member::where("email","member1@beerjudge.local")->value("is_active");') !== '0') throw new Error('still active')
  })
  const m1a = await actor('member1-inactive-admin')
  await m1a.step('inactive admin cannot reach /admin', async () => {
    await login(m1a.page, 'member1@beerjudge.local', undefined, { fresh: true })
    await m1a.page.goto('/admin')
    await m1a.page.waitForLoadState('networkidle')
    await expect(m1a.page).toHaveURL(/login/)
  })
  await step('reactivate member1', async () => {
    await setToggle(m1, 'is_active', true)
  })
})
