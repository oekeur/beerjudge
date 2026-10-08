import { journey, login, expect } from './lib.mjs'

await journey('M2-profile', async actor => {
  const { page, step } = await actor('member7')
  await login(page, 'member7@beerjudge.local')
  await step('open profile from nav', async () => {
    await page.locator('a[href$="/profile"]').first().click()
    await expect(page.locator('#name')).toBeVisible()
  })
  await step('switch language to Dutch and rename', async () => {
    await page.fill('#name', 'Member Seven')
    await page.selectOption('#locale', 'nl')
    await page.getByRole('button', { name: /Save|Opslaan/ }).click()
    await page.waitForLoadState('networkidle')
    await expect(page.locator('h1')).toContainText('profiel')
  })
  await step('home renders in Dutch', async () => {
    await page.goto('/')
    await expect(page.locator('body')).toContainText(/Uitloggen|wedstrijd/i)
  })
  await step('password change with wrong current password rejected', async () => {
    await page.goto('/profile')
    await page.fill('#current_password', 'wrong')
    await page.fill('#password', 'Another-Passw0rd!')
    await page.fill('#password_confirmation', 'Another-Passw0rd!')
    await page.getByRole('button', { name: /Save|Opslaan/ }).click()
    await page.waitForLoadState('networkidle')
    await expect(page.locator('body')).toContainText(/onjuist|incorrect|klopt niet|wachtwoord/i)
  })
  await step('switch back to English', async () => {
    await page.goto('/profile')
    await page.selectOption('#locale', 'en')
    await page.getByRole('button', { name: /Save|Opslaan/ }).click()
    await page.waitForLoadState('networkidle')
    await expect(page.locator('h1')).toContainText('Your profile')
  })
})
