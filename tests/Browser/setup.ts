import { execSync } from 'child_process'
import * as fs from 'fs'
import * as path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default async function globalSetup() {
    const root = path.resolve(__dirname, '../..')
    const dbPath = path.join(root, 'database', 'browser-test.sqlite')

    assertBrowserTestDatabase(root, dbPath)

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

/**
 * Refuses to run unless --env=browser-test resolves to the browser-test SQLite file.
 * Without .env.browser-test, Laravel falls back to .env, and the migrate and seed
 * below would replace the local dev database with fixtures.
 */
function assertBrowserTestDatabase(root: string, dbPath: string) {
    const envFile = path.join(root, '.env.browser-test')
    if (!fs.existsSync(envFile)) {
        throw new Error(
            `Browser tests need ${envFile}. Copy .env.browser-test.example and run ` +
                '`php artisan key:generate --env=browser-test`.',
        )
    }

    const output = execSync(
        `php artisan tinker --env=browser-test --execute 'echo json_encode([config("database.default"), config("database.connections.".config("database.default").".database")]);'`,
        { cwd: root, encoding: 'utf8' },
    )
    const [connection, database] = JSON.parse(output.trim().split('\n').pop() ?? '') as [string, string]

    if (connection !== 'sqlite' || path.resolve(root, database) !== dbPath) {
        throw new Error(
            `Browser tests resolved database ${connection}:${database}, expected sqlite:${dbPath}. ` +
                'Refusing to migrate and seed it.',
        )
    }
}
