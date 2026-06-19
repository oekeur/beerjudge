import { type Page, expect } from '@playwright/test'
import * as fs from 'fs'
import * as path from 'path'

export const JUDGE_EMAIL = 'judge@browser-test.local'
export const BREWER_EMAIL = 'brewer@browser-test.local'
export const PASSWORD = 'password'

export type Fixtures = {
    judgingCompetitionId: number
    openCompetitionId: number
}

export function loadFixtures(): Fixtures {
    return JSON.parse(
        fs.readFileSync(path.join(__dirname, 'fixtures.json'), 'utf8'),
    )
}

export async function login(page: Page, email = JUDGE_EMAIL): Promise<void> {
    await page.goto('/login')
    await page.fill('#email', email)
    await page.fill('#password', PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL('/')
}
