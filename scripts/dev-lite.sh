#!/usr/bin/env bash
# Lightweight dev mode: SQLite + database drivers, no Redis or Postgres required.
# Exported vars take precedence over .env because Laravel uses createImmutable().
export DB_CONNECTION=sqlite
# config/database.php falls back to database/database.sqlite only when DB_DATABASE
# is unset; a .env set up for Postgres has DB_DATABASE=beerjudge, which SQLite
# would read as a relative file path.
export DB_DATABASE="$PWD/database/database.sqlite"
export SESSION_DRIVER=database
export SESSION_ENCRYPT=false
export QUEUE_CONNECTION=database
export CACHE_STORE=database

touch database/database.sqlite

npx concurrently \
    -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
    "php artisan serve --host=localhost" \
    "php artisan queue:listen --tries=1 --timeout=0" \
    "php artisan pail --timeout=0" \
    "npm run dev" \
    --names=server,queue,logs,vite \
    --kill-others
