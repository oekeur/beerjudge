#!/usr/bin/env bash
# Boots a bare clone for a cloud agent session: .env, dependencies, SQLite database, built assets.
# Safe to re-run; each step skips what already exists.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
[ -f .env ] || cp .env.example .env
[ -d vendor ] || composer install --no-interaction --prefer-dist
if grep -q '^APP_KEY=$' .env; then php artisan key:generate; fi
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --force
[ -d node_modules ] || npm ci
npm run build
