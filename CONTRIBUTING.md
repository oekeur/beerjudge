# Contributing

This is a small internal project for a homebrew club. The scope is deliberately narrow: no payments, no multi-club support, no BJCP report generation. Before adding something new, check whether it fits that scope.

## Prerequisites

- PHP 8.5
- Composer
- Node.js and npm
- SQLite (default), MySQL, or Postgres

## Setup

```bash
composer setup
```

`composer setup` installs Composer and npm dependencies, copies `.env.example` to `.env`, generates an app key, runs migrations, and builds frontend assets. It also installs git hooks from `scripts/hooks/` into `.git/hooks/`.

The default configuration uses SQLite and needs no additional services. To use MySQL or Postgres, edit `.env` after setup.

Seed the BJCP 2021 style data:

```bash
php artisan migrate --seed
```

## Development

```bash
composer dev
```

`composer dev` starts the local server, queue worker, log watcher, and Vite dev server concurrently.

For `app.css`, Vite picks up changes automatically. The Filament admin theme at `resources/css/filament/admin/theme.css` is not hot-reloaded; run `npm run build` after changing it.

## Testing

Tests run against an in-memory SQLite database.

```bash
php artisan test --compact                                   # all tests
php artisan test --compact tests/Feature/HomeTest.php        # single file
php artisan test --compact --filter=testName                 # single test
```

Every change needs a test. Write a new one or update an existing one; run the affected tests before pushing. Do not remove tests.

## Static analysis

PHPStan runs at level 10 against `app/`:

```bash
vendor/bin/phpstan analyse
```

This must pass before pushing. Fix errors by following the type-narrowing patterns already in the codebase. Claude Code users: invoke the `phpstan-fix` skill (see `CLAUDE.md`).

## Code style

Pint enforces formatting on staged PHP files. If it reformats anything during the pre-commit hook, re-stage and commit again.

```bash
vendor/bin/pint              # fix in place
vendor/bin/pint --test       # report violations without fixing (CI mode)
```

## Git hooks

`composer install` installs two hooks from `scripts/hooks/`.

The pre-commit hook runs Pint on staged PHP files. It exits non-zero if any file is reformatted.

The pre-push hook runs PHPStan and the full test suite. A push fails if either fails.

Do not skip hooks with `--no-verify`.

## Branch and commit conventions

Branches follow the format `type/short-description`:

```
feat/offline-sync-retry
fix/scoresheet-upsert-race
improve/dashboard-judge-names
hotfix/results-auth-bypass
```

Commits follow `[type](scope): description`:

```
feat(judging): add offline sync retry
fix(scoresheets): resolve upsert race on duplicate client_uuid
improve(dashboard): show missing judge names per entry
```

Valid types: `feat`, `fix`, `improve`, `hotfix`, `chore`, `docs`, `test`.

The description names the behavior, not the file. "add offline sync retry" not "update JudgingController".

## CI

GitHub Actions runs three jobs on every push and pull request: formatting (Pint), static analysis (PHPStan), and tests against in-memory SQLite. All three must pass before a PR can merge.
