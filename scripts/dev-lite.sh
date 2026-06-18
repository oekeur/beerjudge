#!/usr/bin/env bash
# Lightweight dev mode: SQLite + database drivers, no Redis or Postgres required.
# Exported vars take precedence over .env because Laravel uses createImmutable().
export DB_CONNECTION=sqlite
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
