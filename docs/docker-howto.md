# How to self-host with Docker

Run BeerJudge on a Linux server from the published image, `ghcr.io/oekeur/beerjudge`. You need no checkout of the repository and no PHP or Node on the server: one Compose file and one `.env` file are enough. For what each service and Compose file does, see [docs/docker-reference.md](docker-reference.md).

---

## Prerequisites

- Docker Engine 24+ with Docker Compose v2
- A domain and a TLS-terminating reverse proxy (Caddy, Traefik, nginx) if members will reach it over the internet. The stack itself serves plain HTTP on one port.
- SMTP credentials if you want invitation, magic-link and results emails delivered

---

## First deployment

### 1. Fetch the Compose file and the environment template

```bash
mkdir beerjudge && cd beerjudge
curl -fsSLO https://raw.githubusercontent.com/oekeur/beerjudge/main/docker-compose.selfhost.yaml
curl -fsSL https://raw.githubusercontent.com/oekeur/beerjudge/main/.env.docker.example -o .env
```

### 2. Fill in `.env`

Set at least these values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...        # generate with: echo "base64:$(openssl rand -base64 32)"
APP_URL=https://beerjudge.example.org
APP_TIMEZONE=Europe/Amsterdam   # set before the first competition; see env-reference
CLUB_NAME=Your Club

DB_PASSWORD=a-long-random-password

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.org
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=beerjudge@example.org
```

The same `.env` file feeds both the app container (`env_file`) and the Postgres container's initial password (`${DB_PASSWORD}`), so the two always match. Compose refuses to start when `DB_PASSWORD` is empty.

Two optional values only affect Compose itself:

| Variable | Default | Effect |
|---|---|---|
| `BEERJUDGE_VERSION` | `latest` | Image tag to run, e.g. `1.0.0` to pin a release. |
| `BEERJUDGE_PORT` | `8080` | Host port the app listens on. Point your reverse proxy here. |

To show your club's own icon, see "Club icons" in [docs/pwa-reference.md](pwa-reference.md). [docs/env-reference.md](env-reference.md) lists every variable.

### 3. Start the stack

```bash
docker compose -f docker-compose.selfhost.yaml up -d
```

On every start the app container runs `php artisan migrate --force`, then caches config, routes and views.

### 4. Load the style guides

```bash
docker compose -f docker-compose.selfhost.yaml exec app php artisan db:seed --force
```

This loads the BJCP 2021 and Brewers Association 2026 beer styles. It is safe to run again: rows are upserted on code and source.

### 5. Create the first admin

```bash
docker compose -f docker-compose.selfhost.yaml exec app php artisan member:create --admin
```

The command asks for a name, an email address and a password. Then open `APP_URL` (or `http://<server>:8080` before the proxy is set up) and log in. Further members register themselves or get an invitation from the admin panel.

---

## Verify the deployment

```bash
docker compose -f docker-compose.selfhost.yaml ps                  # app, postgres and redis running, the last two healthy
docker compose -f docker-compose.selfhost.yaml logs app | grep -i migrat
curl -I http://localhost:8080/login                                 # HTTP 200
```

---

## Updating

```bash
docker compose -f docker-compose.selfhost.yaml pull app
docker compose -f docker-compose.selfhost.yaml up -d app
```

Migrations run on start. Read the [CHANGELOG](https://github.com/oekeur/beerjudge/blob/main/CHANGELOG.md) before a major version. To roll back, set `BEERJUDGE_VERSION` to the previous release and run the same two commands; a rollback across a migration that changed data may need a database restore.

---

## Troubleshooting

### "No application encryption key has been specified"

`APP_KEY` is empty in `.env`. Generate one with `echo "base64:$(openssl rand -base64 32)"`, put it in `.env`, then recreate the container. `env_file` is read only when the container is created, so `restart` keeps the old values:

```bash
docker compose -f docker-compose.selfhost.yaml up -d --force-recreate app
```

Never change `APP_KEY` once members exist: it signs sessions and invitation links.

### Login redirects to `/login` in an infinite loop

1. `APP_URL` matches the address in the browser exactly (scheme, domain, no trailing slash).
2. Behind an HTTPS proxy, the proxy forwards `X-Forwarded-Proto`; otherwise Laravel builds `http://` URLs and secure cookies are dropped.
3. Redis is healthy: `docker compose -f docker-compose.selfhost.yaml logs redis`. This stack runs Redis without a password, so leave `REDIS_PASSWORD` as it is in the template.
4. `SESSION_SAME_SITE` is `lax` (the default).

### App returns 502 right after start

The entrypoint is still running migrations and caching. Wait 10 to 20 seconds, then check `docker compose -f docker-compose.selfhost.yaml logs app`.

### Postgres password change does not take effect

`POSTGRES_PASSWORD` only applies when the `postgres_data` volume is first created. On an existing volume, change it inside Postgres:

```bash
docker compose -f docker-compose.selfhost.yaml exec postgres psql -U beerjudge
```

Run `ALTER USER beerjudge WITH PASSWORD '...';` at the `psql` prompt, update `DB_PASSWORD` in `.env`, and recreate the app container with `up -d --force-recreate app`.

---

## Data management

### Backing up Postgres

```bash
docker compose -f docker-compose.selfhost.yaml exec -T postgres pg_dump -U beerjudge beerjudge > backup-$(date +%F).sql
```

### Restoring Postgres

```bash
docker compose -f docker-compose.selfhost.yaml exec -T postgres psql -U beerjudge beerjudge < backup-2026-01-01.sql
```

### Retrying failed background jobs

The results email goes through the queue worker. A job that fails three times is stored in the `failed_jobs` table:

```bash
docker compose -f docker-compose.selfhost.yaml exec app php artisan queue:failed
docker compose -f docker-compose.selfhost.yaml exec app php artisan queue:retry all
```

### Deleting all data (destructive)

```bash
docker compose -f docker-compose.selfhost.yaml down -v
```

`-v` removes the named volumes `postgres_data` and `redis_data`. All competition data is lost.

---

## Building the image yourself

From a checkout of the repository:

```bash
docker build -t ghcr.io/oekeur/beerjudge:local .
BEERJUDGE_VERSION=local docker compose -f docker-compose.selfhost.yaml up -d
```

---

## Related

- [docs/docker-reference.md](docker-reference.md) — services, volumes, entrypoint behaviour
- [docs/env-reference.md](env-reference.md) — all environment variables
- [README.md](../README.md) — local development setup
