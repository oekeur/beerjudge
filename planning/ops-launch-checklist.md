# Launch ops checklist

These are the remaining manual tasks before going public. All code changes are committed.

## Blocking

- [ ] Set `APP_ENV=production` in `.env.production` (currently `testing`)
- [ ] Rotate `DB_PASSWORD` — change the trivial default to a strong password; update `.env.production` and the Postgres user
- [ ] Set `MAIL_FROM_ADDRESS` in `.env.production` to the real sender address (currently `hello@example.com`)
- [ ] Confirm Redis is bound to localhost only: `redis-cli CONFIG GET bind` — if anything other than `127.0.0.1`, set `REDIS_PASSWORD` in `.env.production` and `requirepass` in `redis.conf`

## Verify after deploy

- [ ] `php artisan config:show app.env` returns `production`
- [ ] Trigger a test error — confirm no stack trace is shown in the browser
- [ ] Send a test magic link — confirm mail is delivered from the right address

## Post-launch (deferred)

- [ ] Last-admin guard in Filament — prevent an admin from removing their own `is_admin` when they are the only admin
