import { test, expect } from '@playwright/test'
import { JUDGE_EMAIL, PASSWORD } from './helpers'

test.describe('authentication', () => {
    test('member can log in with valid credentials', async ({ page }) => {
        await page.goto('/login')
        await page.fill('#email', JUDGE_EMAIL)
        await page.fill('#password', PASSWORD)
        await page.click('button[type="submit"]')

        await expect(page).toHaveURL('/')
        await expect(page.getByRole('button', { name: 'Log out' })).toBeVisible()
    })

    test('invalid credentials show an error message', async ({ page }) => {
        await page.goto('/login')
        await page.fill('#email', JUDGE_EMAIL)
        await page.fill('#password', 'wrong-password')
        await page.click('button[type="submit"]')

        await expect(page).toHaveURL('/login')
        await expect(page.getByTestId('form-error')).toBeVisible()
        await expect(page.getByTestId('form-error')).toContainText('Invalid credentials')
    })

    test('unauthenticated user is redirected to login', async ({ page }) => {
        await page.goto('/')
        await expect(page).toHaveURL('/login')
    })
})
