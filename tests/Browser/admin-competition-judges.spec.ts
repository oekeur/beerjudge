import { execFileSync } from 'child_process'
import * as path from 'path'
import { fileURLToPath } from 'url'
import { test, expect } from '@playwright/test'
import { PASSWORD, loadFixtures } from './helpers'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.resolve(__dirname, '../..')
const email = 'judges-admin@browser-test.local'

test.beforeAll(() => {
    const php = `\\App\\Models\\Member::updateOrCreate(['email' => '${email}'], ['name' => 'Judges admin', 'password' => \\Illuminate\\Support\\Facades\\Hash::make('${PASSWORD}'), 'is_admin' => true, 'is_active' => true, 'locale' => 'en']);`
    execFileSync('php', ['artisan', 'tinker', '--env=browser-test', `--execute=${php}`], { cwd: root })
})

test('judges added and removed on the edit page persist without pressing Save', async ({ page }) => {
    const { openCompetitionId } = loadFixtures()
    const pageErrors: string[] = []
    page.on('pageerror', (error) => pageErrors.push(error.message))

    await page.goto('/login')
    await page.fill('#email', email)
    await page.fill('#password', PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL((url) => url.pathname !== '/login')

    const removeBrewer = page.getByRole('button', { name: 'Remove Browser Brewer', exact: true })
    const editUrl = `/admin/competitions/${openCompetitionId}/edit`
    await page.goto(editUrl)
    await page.getByRole('tab', { name: 'Dashboard' }).click()
    await page.getByRole('button', { name: 'Select an option' }).click()
    await page.getByRole('option', { name: 'Browser Brewer' }).click()
    await expect(removeBrewer).toBeVisible()
    // The change is saved by the live update request; wait for it to settle before reloading.
    await page.waitForLoadState('networkidle')

    await page.reload()
    await page.getByRole('tab', { name: 'Dashboard' }).click()
    await expect(removeBrewer).toBeVisible()

    await removeBrewer.click()
    await expect(removeBrewer).toHaveCount(0)
    await page.waitForLoadState('networkidle')

    await page.reload()
    await page.getByRole('tab', { name: 'Dashboard' }).click()
    await expect(removeBrewer).toHaveCount(0)
    expect(pageErrors).toEqual([])
})
