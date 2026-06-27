# Environment Variables Reference

All variables recognised by the application. The `.env.example` file covers the simple local setup (SQLite, no external services). The `.env.docker.example` file covers the Docker and production setup (Postgres, Redis).

See [README.md](../README.md) for which env file to copy in each setup scenario.

---

## App

| Variable | Type | Default | Required for prod | Notes |
|---|---|---|---|---|
| `APP_NAME` | string | `BeerJudge` | no | Appears in page titles and mail footer. |
| `CLUB_NAME` | string | _(empty)_ | yes | Shown on the home page and admin header. Set it before inviting members. |
| `APP_ENV` | string | `local` | yes | Set to `production`. Controls error detail, caching defaults, and log verbosity. |
| `APP_KEY` | string | _(empty)_ | yes | Laravel encryption key. Generate with `php artisan key:generate --show`. Never leave blank. |
| `APP_DEBUG` | bool | `true` | yes | Set to `false` in production. When `true`, full stack traces are sent to the browser. |
| `APP_URL` | string | `http://localhost` | yes | The canonical URL, including scheme. Used for route generation and mail links. |
| `APP_LOCALE` | string | `en` | no | Application locale for translations. |
| `APP_FALLBACK_LOCALE` | string | `en` | no | Fallback when a translation key is missing in `APP_LOCALE`. |
| `APP_FAKER_LOCALE` | string | `en_US` | no | Locale used by Faker in seeders and factories. |
| `APP_MAINTENANCE_DRIVER` | string | `file` | no | Where maintenance mode state is stored. `file` is fine for single-server deployments. |
| `BCRYPT_ROUNDS` | int | `12` | no | Cost factor for password hashing. Higher is slower and more secure. 12 is a reasonable default. |
| `PHP_CLI_SERVER_WORKERS` | int | _(unset)_ | no | Worker count for `artisan serve`. Useful for load testing locally; not used in Docker (nginx handles concurrency). |

---

## Logging

| Variable | Type | Default | Required for prod | Notes |
|---|---|---|---|---|
| `LOG_CHANNEL` | string | `stack` | no | Top-level log channel. `stack` aggregates the channels in `LOG_STACK`. |
| `LOG_STACK` | string | `single` | no | Comma-separated list of channels used by the `stack` channel. |
| `LOG_DEPRECATIONS_CHANNEL` | string | `null` | no | Where PHP/Laravel deprecation notices go. `null` silences them. |
| `LOG_LEVEL` | string | `debug` | yes | Minimum severity to write. Set to `error` or `warning` in production to reduce noise. |

---

## Database

| Variable | Type | Default (.env.example) | Default (.env.docker.example) | Notes |
|---|---|---|---|---|
| `DB_CONNECTION` | string | `sqlite` | `pgsql` | Driver. `sqlite` for local dev; `pgsql` for Docker and production. |
| `DB_HOST` | string | _(unset)_ | `127.0.0.1` | Postgres host. In Docker, set to the service name (`postgres`). Injected by `docker-compose.yaml`. |
| `DB_PORT` | int | _(unset)_ | `5432` | Postgres port. |
| `DB_DATABASE` | string | _(unset)_ | `beerjudge` | Database name. |
| `DB_USERNAME` | string | _(unset)_ | `beerjudge` | Database user. |
| `DB_PASSWORD` | string | _(unset)_ | `secret` | Database password. Change before any production deployment. |

`DB_HOST` and `DB_PORT` are not needed when `DB_CONNECTION=sqlite`. When running inside the Docker stack, `docker-compose.yaml` injects `DB_HOST=postgres` and `DB_PORT=5432` directly into the container environment, overriding the `.env` values.

---

## Cache, Session, and Queue

| Variable | Type | Default (.env.example) | Default (.env.docker.example) | Notes |
|---|---|---|---|---|
| `CACHE_STORE` | string | `database` | `redis` | Where cached data lives. `database` works without Redis; `redis` is faster and required in production. |
| `CACHE_PREFIX` | string | _(unset)_ | _(unset)_ | Prefix for cache keys. Useful when multiple apps share a Redis instance. |
| `SESSION_DRIVER` | string | `database` | `redis` | Session backend. Use `redis` in production. |
| `SESSION_LIFETIME` | int | `10080` | `10080` | Session TTL in minutes. 10080 = one week; judges do not want to re-authenticate mid-session. |
| `SESSION_ENCRYPT` | bool | `false` | `false` | Encrypt session payload at rest. Not required when the session store is a private Redis instance. |
| `SESSION_PATH` | string | `/` | `/` | Cookie path scope. |
| `SESSION_DOMAIN` | string | `null` | `null` | Cookie domain. `null` defaults to the request host. |
| `QUEUE_CONNECTION` | string | `database` | `redis` | Queue backend. `database` is fine for low-volume use; `redis` is preferred in production. |
| `BROADCAST_CONNECTION` | string | `log` | `log` | Broadcast driver. `log` disables real broadcasting; the app does not use websockets. |
| `FILESYSTEM_DISK` | string | `local` | `local` | Default storage disk. `local` stores files under `storage/app`. |

---

## Redis

Only needed when `CACHE_STORE`, `SESSION_DRIVER`, or `QUEUE_CONNECTION` is set to `redis`.

| Variable | Type | Default | Notes |
|---|---|---|---|
| `REDIS_CLIENT` | string | _(unset)_ | Set to `phpredis` when the `redis` PHP extension is available (it is in the Docker image). |
| `REDIS_HOST` | string | `127.0.0.1` | Redis hostname. In the Docker stack this is overridden to `redis` by `docker-compose.yaml`. |
| `REDIS_PASSWORD` | string | `null` | Auth password. The bundled Redis container has no auth; leave as `null`. If you add a password, set it here and in the Redis container config. |
| `REDIS_PORT` | int | `6379` | Redis port. |
| `REDIS_DB` | int | `0` | Redis database index. |

`REDIS_PASSWORD=null` is the literal string `null`, which Laravel interprets as no password. Do not set it to an empty string.

---

## Mail

| Variable | Type | Default | Required for prod | Notes |
|---|---|---|---|---|
| `MAIL_MAILER` | string | `log` | yes | Transport. `log` writes mails to the log file (safe default; no real delivery). Change to `smtp` or a provider driver for production. |
| `MAIL_SCHEME` | string | `null` | no | `null`, `tls`, or `ssl`. |
| `MAIL_HOST` | string | `127.0.0.1` | when mailer=smtp | SMTP server host. |
| `MAIL_PORT` | int | `2525` | when mailer=smtp | SMTP port. |
| `MAIL_USERNAME` | string | `null` | when mailer=smtp | SMTP credentials. |
| `MAIL_PASSWORD` | string | `null` | when mailer=smtp | SMTP credentials. |
| `MAIL_FROM_ADDRESS` | string | `hello@example.com` | yes | From address on all outgoing mail. Change to a real address. |
| `MAIL_FROM_NAME` | string | `${APP_NAME}` | no | From name. Defaults to `APP_NAME`. |

Mail is only used for magic-link login emails and (when implemented) result publication notifications. With `MAIL_MAILER=log`, those emails appear in `storage/logs/laravel.log` and are never actually sent.

---

## Monitoring

| Variable | Type | Default | Notes |
|---|---|---|---|
| `SENTRY_LARAVEL_DSN` | string | _(empty)_ | Sentry DSN. Leave empty to disable Sentry. When set, uncaught exceptions and performance traces are sent to Sentry. |

---

## Vite (build-time only)

| Variable | Type | Default | Notes |
|---|---|---|---|
| `VITE_APP_NAME` | string | `${APP_NAME}` | Passed to the frontend bundle at build time. Available in JS as `import.meta.env.VITE_APP_NAME`. |
| `VITE_SKIP_FONTS` | bool | _(unset)_ | Set to `1` to skip the Bunny Fonts download during `npm run build`. Useful in CI environments without internet access. |

---

## AWS (unused by default)

The `.env.example` includes AWS variables because Laravel's default template includes them. BeerJudge does not use S3 unless you configure `FILESYSTEM_DISK=s3`. Leave these blank unless you set up S3 storage.

| Variable | Default |
|---|---|
| `AWS_ACCESS_KEY_ID` | _(empty)_ |
| `AWS_SECRET_ACCESS_KEY` | _(empty)_ |
| `AWS_DEFAULT_REGION` | `us-east-1` |
| `AWS_BUCKET` | _(empty)_ |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` |

---

## Related

- [README.md](../README.md) — which `.env` file to copy per setup
- [docs/docker-reference.md](docker-reference.md) — which variables Docker Compose injects automatically
- [docs/docker-howto.md](docker-howto.md) — production deployment steps
