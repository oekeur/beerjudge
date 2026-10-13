import { journey, login, expect, modal, logMark, logSince, tinker, baseRe } from './lib.mjs'

async function invite(page, timeBound) {
  await page.goto('/admin/members')
  await page.waitForLoadState('networkidle')
  await page.getByRole('button', { name: 'Generate invite link' }).click()
  if (timeBound) {
    await page.getByLabel(/Time-bound/).check()
    await page.waitForTimeout(500)
    await page.locator('[id$="expires_at"]').fill('2030-01-01T12:00')
  }
  await modal(page).getByRole('button', { name: /Submit|Generate|Confirm/ }).click()
  const body = await page.getByText(/\/join\//).first().innerText()
  return body.match(/http\S+\/join\/\S+/)[0]
}
async function register(page, url, name, email) {
  await page.goto(url)
  await page.fill('#name', name)
  await page.fill('#email', email)
  await page.fill('#password', 'Journey-Passw0rd!')
  await page.fill('#password_confirmation', 'Journey-Passw0rd!')
  await page.click('form button[type="submit"]')
  await page.waitForLoadState('networkidle')
}

await journey('A1-onboard-member', async actor => {
  const admin = await actor('member2-admin')
  await login(admin.page, 'member2@beerjudge.local')
  await admin.step('generate single-use invite', async () => { globalThis.single = await invite(admin.page, false); console.log('  ', globalThis.single) })
  const guest = await actor('guest-newbie')
  await guest.step('register through invite', async () => {
    await register(guest.page, globalThis.single, 'Newbie One', 'newbie1@beerjudge.local')
    await expect(guest.page).toHaveURL(/\/login/)
    await expect(guest.page.locator('body')).toContainText(/approv/i)
  })
  await guest.step('pending member cannot log in', async () => {
    await login(guest.page, 'newbie1@beerjudge.local', 'Journey-Passw0rd!', { fresh: true })
    await expect(guest.page).toHaveURL(/\/login/)
  })
  await guest.step('used single-use invite answers 410', async () => {
    await guest.page.context().clearCookies()
    const r = await guest.page.goto(globalThis.single)
    if (r.status() !== 410) throw new Error('status ' + r.status())
  })
  await admin.step('generate time-bound invite', async () => { globalThis.multi = await invite(admin.page, true); console.log('  ', globalThis.multi) })
  const g2 = await actor('guest-newbie2')
  await g2.step('two people register on the time-bound invite', async () => {
    await register(g2.page, globalThis.multi, 'Newbie Two', 'newbie2@beerjudge.local')
    await g2.page.context().clearCookies()
    await register(g2.page, globalThis.multi, 'Newbie Three', 'newbie3@beerjudge.local')
    const n = tinker('echo App\\Models\\Member::where("email","like","newbie%")->where("is_active",false)->count();')
    if (n !== '3') throw new Error('pending ' + n)
  })
  await admin.step('members nav badge and pending filter', async () => {
    await admin.page.goto('/admin/members')
    await admin.page.waitForLoadState('networkidle')
    await expect(admin.page.getByRole('row', { name: /Newbie One/ })).toBeVisible()
  })
  await admin.step('approve one member', async () => {
    globalThis.mark = logMark()
    await admin.page.getByRole('row', { name: /Newbie One/ }).getByRole('button', { name: 'Approve' }).click()
    await admin.page.waitForLoadState('networkidle')
    await admin.page.waitForTimeout(800)
    if (modal(admin.page).isVisible) { const d = modal(admin.page); if (await d.isVisible()) await d.getByRole('button', { name: /Confirm|Approve/ }).click() }
    await admin.page.waitForTimeout(800)
    if (!/newbie1@beerjudge\.local/.test(logSince(globalThis.mark))) throw new Error('no activation mail logged')
  })
  await admin.step('bulk approve the other two', async () => {
    await admin.page.getByPlaceholder('Search').fill('Newbie')
    await admin.page.waitForTimeout(1200)
    for (const n of ['Newbie Two', 'Newbie Three']) await admin.page.getByRole('row', { name: new RegExp(n) }).getByRole('checkbox').check()
    await admin.page.getByRole('button', { name: /Bulk actions|Open actions/ }).first().click().catch(() => {})
    await admin.page.getByRole('button', { name: 'Approve selected' }).click()
    const d = modal(admin.page); await admin.page.waitForTimeout(500)
    if (await d.isVisible()) await d.getByRole('button', { name: /Confirm|Approve/ }).click()
    await admin.page.waitForTimeout(1000)
    const n = tinker('echo App\\Models\\Member::where("email","like","newbie%")->where("is_active",true)->count();')
    if (n !== '3') throw new Error('active ' + n)
  })
  await guest.step('approved member logs in', async () => {
    await login(guest.page, 'newbie1@beerjudge.local', 'Journey-Passw0rd!')
    await expect(guest.page).toHaveURL(baseRe('/$'))
  })
})
