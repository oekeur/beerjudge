import { execSync } from 'child_process'
import * as fs from 'fs'
import * as path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default async function globalSetup() {
    const root = path.resolve(__dirname, '../..')
    const dbPath = path.join(root, 'database', 'browser-test.sqlite')

    if (fs.existsSync(dbPath)) {
        fs.unlinkSync(dbPath)
    }
    fs.writeFileSync(dbPath, '')

    execSync('php artisan migrate --force --env=browser-test', {
        cwd: root,
        stdio: 'inherit',
    })

    execSync(
        'php artisan db:seed --class=BrowserTestSeeder --force --env=browser-test',
        { cwd: root, stdio: 'inherit' },
    )

    // BrowserTestSeeder always creates competitions in this order on a fresh DB:
    // 1 → judging competition, 2 → open competition
    const fixtures = { judgingCompetitionId: 1, openCompetitionId: 2 }
    fs.writeFileSync(path.join(__dirname, 'fixtures.json'), JSON.stringify(fixtures))
}
