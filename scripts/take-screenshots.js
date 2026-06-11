import { chromium } from 'playwright';
import { execSync } from 'child_process';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');
const BASE_URL = 'http://localhost:8001';
const OUT = join(ROOT, 'docs', 'screenshots');

function artisan(cmd) {
    execSync(`php artisan tinker --execute '${cmd}'`, { cwd: ROOT, stdio: 'inherit' });
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });

try {
    // 1. Login page (no auth needed)
    {
        const page = await context.newPage();
        await page.goto(`${BASE_URL}/login`);
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: `${OUT}/login.png` });
        await page.close();
    }

    // Login as Club Admin
    const page = await context.newPage();
    await page.goto(`${BASE_URL}/login`);
    await page.fill('#email', 'admin@beerjudge.local');
    await page.fill('#password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // 2. Judging entry list
    await page.goto(`${BASE_URL}/judging/1`);
    await page.waitForLoadState('networkidle');
    // Wait for Alpine.js to render entry names into the buttons
    await page.waitForFunction(() => {
        const buttons = document.querySelectorAll('main button');
        return buttons.length > 0 && buttons[0].textContent.trim().length > 0;
    }, { timeout: 10000 });
    await page.screenshot({ path: `${OUT}/judging-list.png` });

    // 3. Judging scoresheet — open Dmitri's American Stout (3rd entry, draft with scores filled)
    // Entry header buttons have class "w-full text-left" (score buttons do not)
    await page.locator('main button.w-full').nth(2).click();
    await page.waitForTimeout(600);
    const thirdCard = page.locator('main .bg-white.rounded-xl').nth(2);
    const box = await thirdCard.boundingBox();
    if (box) {
        await page.screenshot({
            path: `${OUT}/judging-scoresheet.png`,
            clip: { x: box.x, y: box.y, width: box.width, height: Math.min(box.height, 800) },
        });
    }

    // 4. Admin — competition edit page, Entries tab
    await page.goto(`${BASE_URL}/admin/competitions/1/edit`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await page.getByRole('tab', { name: 'Entries' }).click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);
    await page.screenshot({ path: `${OUT}/admin-competition.png` });

    // 5. Results — temporarily close the competition
    artisan('App\\Models\\Competition::find(1)->update(["status" => "closed"]);');
    await page.goto(`${BASE_URL}/competitions/1/results/2`);
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: `${OUT}/results.png`, fullPage: true });

    await page.close();
} finally {
    await browser.close();
    artisan('App\\Models\\Competition::find(1)->update(["status" => "judging"]);');
}

console.log('Screenshots saved to docs/screenshots/');
