import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
    testDir: './tests/Browser',
    timeout: 30_000,
    expect: { timeout: 10_000 },
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: 'html',
    use: {
        baseURL: process.env.APP_URL ?? 'http://localhost:8000',
        headless: true,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    globalSetup: './tests/Browser/setup.ts',
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
})
