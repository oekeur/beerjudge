# How to deploy with Docker

Step-by-step instructions for deploying BeerJudge on a Linux server using Docker Compose. For a reference of what each Compose file and service does, see [docs/docker-reference.md](docker-reference.md).

---

## Prerequisites

- Docker Engine 24+ and Docker Compose v2 on the server
- An nginx-proxy setup handling TLS termination (see [nginx-proxy](https://github.com/nginx-proxy/nginx-proxy))
- The external Docker network that nginx-proxy uses (commonly called `webproxy` or `proxy`)
- Git access to the repository, or the ability to copy the project files to the server

---

## First deployment

### 1. Copy the project to the server

```bash
git clone https://github.com/oekeur/beerjudge /srv/beerjudge
cd /srv/beerjudge
```

Or transfer the files via `scp`/`rsync` if you prefer.

### 2. Create the environment file

```bash
cp .env.docker.example .env.production
```

Edit `.env.production` and set at minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=           # generate with: php artisan key:generate --show
APP_URL=https://yourdomain.com
CLUB_NAME=Your Club Name

DB_PASSWORD=changeme   # change before first start; only effective on a fresh volume. Compose passes ${DB_PASSWORD} to postgres from the shell or .env, not from .env.production, so set the same value there too

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=beerjudge@yourdomain.com

SENTRY_LARAVEL_DSN=   # optional; leave empty to disable error tracking
```

See [docs/env-reference.md](env-reference.md) for the full variable list.

### 3. Create the proxy network (if it does not exist)

```bash
docker network create proxy
```

If your nginx-proxy uses a different network name, note it for step 4.

### 4. Create the host-specific production overlay

`docker-compose.prod.yaml` (committed to the repo) attaches the app to the `proxy` network but does not configure the virtual host or network name. Create a `compose.host.yaml` file on the server (not committed):

```yaml
services:
  app:
    environment:
      VIRTUAL_HOST: yourdomain.com
      LETSENCRYPT_HOST: yourdomain.com
      VIRTUAL_PORT: 80

networks:
  proxy:
    external: true
    name: webproxy   # the actual Docker network name nginx-proxy uses
```

### 5. Start the stack

```bash
docker compose \
  -f docker-compose.yaml \
  -f docker-compose.prod.yaml \
  -f compose.host.yaml \
  up -d --build
```

On first start, the entrypoint runs `php artisan migrate --force` automatically. No manual migration step is needed.

---

## Verify the deployment

```bash
# Check all three containers are running
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml ps

# Tail the app logs
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml logs -f app

# Check the migrate output in the startup logs
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml logs app | grep -i migrat

# Confirm the service responds
curl -I https://yourdomain.com
```

The app is ready when you see an HTTP 200 or 302 response and the startup logs show migration output without errors.

---

## Updating / redeploying

```bash
cd /srv/beerjudge
git pull

docker compose \
  -f docker-compose.yaml \
  -f docker-compose.prod.yaml \
  -f compose.host.yaml \
  up -d --build
```

The entrypoint runs `migrate --force` on each start, so new migrations apply automatically during the restart. Config, route, and view caches are also rebuilt.

There is a brief period (typically a few seconds) while the new container starts and runs the entrypoint where the app returns 502. Plan updates during low-traffic windows.

---

## Troubleshooting

### "No application encryption key has been specified"

`APP_KEY` is empty in `.env.production`. Generate one:

```bash
php artisan key:generate --show
```

Copy the output into `.env.production`, then recreate the container. `env_file` is read only when the container is created, so `restart` keeps the old values:

```bash
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml -f compose.host.yaml up -d --force-recreate app
```

### Login redirects to `/login` in an infinite loop

Most likely the `SESSION_DOMAIN` is wrong, `APP_URL` does not match the actual URL, or `SESSION_DRIVER` is misconfigured. Check:

1. `APP_URL` matches the domain in the browser exactly (scheme, domain, no trailing slash).
2. `SESSION_DRIVER=redis` and Redis is reachable (`docker compose logs redis`).
3. `REDIS_PASSWORD` is `null` (the string, not an empty value) if the Redis container has no auth.

Also check: if `SESSION_SAME_SITE` is `strict`, email magic links will not carry the cookie. Use `lax` (the default).

### App returns 502 after deploy

The container is still starting. Wait 10–20 seconds for the entrypoint to finish, then check logs:

```bash
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml logs app
```

If migrations are running against a large dataset, startup can take longer than the reverse proxy's upstream timeout.

### Postgres password change does not take effect

`POSTGRES_PASSWORD` in the Compose environment only applies when the `postgres_data` volume is created for the first time. On an existing volume, change the password inside the running container:

```bash
docker compose exec postgres psql -U beerjudge -c "ALTER USER beerjudge PASSWORD 'newpassword';"
```

Then update `DB_PASSWORD` in `.env.production` (and wherever Compose reads it, see above) to match, and recreate the app container with `up -d --force-recreate app`.

### "network proxy declared as external, but could not be found"

The external Docker network does not exist. Create it:

```bash
docker network create proxy
```

If your nginx-proxy uses a different name, update the `name:` field in `compose.host.yaml`.

### Vite manifest missing / assets 404

The production image bakes `public/build/` into the image at build time. A 404 means the image was not rebuilt. Pass `--build` to the `up` command:

```bash
docker compose ... up -d --build
```

---

## Data management

### Backing up Postgres

```bash
docker compose exec postgres pg_dump -U beerjudge beerjudge > backup-$(date +%F).sql
```

### Restoring Postgres

```bash
docker compose exec -T postgres psql -U beerjudge beerjudge < backup-2025-01-01.sql
```

### Deleting all data (destructive)

```bash
docker compose down -v
```

`-v` removes named volumes including `postgres_data` and `redis_data`. All competition data is lost.

---

## Related

- [docs/docker-reference.md](docker-reference.md) — services, volumes, entrypoint behaviour
- [docs/env-reference.md](env-reference.md) — all environment variables
- [README.md](../README.md) — local development setup
