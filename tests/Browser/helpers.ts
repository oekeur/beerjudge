import { type Page, expect } from '@playwright/test'
import * as fs from 'fs'
import * as path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

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

/**
 * Clear IndexedDB to ensure test isolation.
 * This prevents stale scoresheet data from previous tests affecting the current test.
 */
export async function clearIndexedDB(page: Page): Promise<void> {
    await page.evaluate(() => {
        return new Promise<void>((resolve, reject) => {
            const openRequest = indexedDB.open('beerjudge')

            openRequest.onsuccess = () => {
                const db = openRequest.result
                try {
                    const transaction = db.transaction('scoresheets', 'readwrite')
                    const store = transaction.objectStore('scoresheets')
                    store.clear()

                    transaction.oncomplete = () => resolve()
                    transaction.onerror = () => reject(transaction.error)
                } catch (e) {
                    resolve()
                }
            }

            openRequest.onerror = () => resolve()
        })
    })
}
