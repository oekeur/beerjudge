import { journey, login, logMark, logSince, expect, PASSWORD } from './lib.mjs'

await journey('M1-sign-in', async actor => {
  const { page, step } = await actor('member3')
  await step('guest redirected to login', async () => {
    await page.goto('/')
    await expect(page).toHaveURL(/\/login$/)
  })
  await step('wrong password rejected', async () => {
    await login(page, 'member3@beerjudge.local', 'nope', { fresh: true })
    await expect(page).toHaveURL(/\/login$/)
    await expect(page.locator('body')).toContainText(/credentials|onjuist|incorrect|do not match/i)
  })
  await step('member password login lands on home', async () => {
    await login(page, 'member3@beerjudge.local', undefined, { fresh: true })
    await expect(page).toHaveURL(/localhost:8010\/$/)
  })
  await step('logout', async () => {
    await page.getByRole('button', { name: /log ?out|uitloggen|afmelden/i }).click()
    await expect(page).toHaveURL(/\/login$/)
  })
  await step('magic link requested', async () => {
    const mark = logMark()
    await page.goto('/magic-link')
    await page.fill('#email', 'member5@beerjudge.local')
    await page.click('form button[type="submit"]')
    await page.waitForLoadState('networkidle')
    const mail = logSince(mark)
    const m = mail.match(/http:\/\/localhost:8010\/magic-link\/[A-Za-z0-9]+/)
    if (!m) throw new Error('no magic link in log: ' + mail.slice(0, 300))
    globalThis.magic = m[0]
  })
  await step('magic link shows confirm page (GET does not log in)', async () => {
    await page.goto(globalThis.magic)
    await expect(page.getByTestId('magic-link-login-button')).toBeVisible()
  })
  await step('magic link confirm logs in', async () => {
    await page.getByTestId('magic-link-login-button').click()
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/localhost:8010\/$/)
  })
  await step('used magic link no longer works', async () => {
    await page.context().clearCookies()
    await page.goto(globalThis.magic)
    const btn = page.getByTestId('magic-link-login-button')
    if (await btn.isVisible()) { await btn.click(); await page.waitForLoadState('networkidle') }
    await expect(page).not.toHaveURL(/localhost:8010\/$/)
  })
  await step('password reset requested', async () => {
    const mark = logMark()
    await page.goto('/password/reset')
    await page.fill('#email', 'member8@beerjudge.local')
    await page.click('form button[type="submit"]')
    await page.waitForLoadState('networkidle')
    const m = logSince(mark).match(/http:\/\/localhost:8010\/password\/reset\/[^\s"<)\]]+/)
    if (!m) throw new Error('no reset link in log')
    globalThis.reset = m[0].replace(/&amp;/g, '&')
  })
  await step('password reset completes and logs in', async () => {
    await page.goto(globalThis.reset)
    await page.fill('#password', 'NewPassw0rd!2026')
    await page.fill('#password_confirmation', 'NewPassw0rd!2026')
    await page.click('form button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/localhost:8010\/$/)
  })
  const admin = await actor('member2-admin')
  await admin.step('admin login redirects to /admin', async () => {
    await login(admin.page, 'member2@beerjudge.local', undefined, { fresh: true })
    await expect(admin.page).toHaveURL(/\/admin/)
  })
})
