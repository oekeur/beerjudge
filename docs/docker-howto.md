# How to deploy with Docker

Step-by-step instructions for deploying BeerJudge on a Linux server using Docker Compose. For a reference of what each Compose file and service does, see [docs/docker-reference.md](docker-reference.md).

The image is built on a development machine and pushed to a private GitHub Container Registry package, `ghcr.io/oekeur/beerjudge:latest`. The server has no checkout of the repository and never builds: it holds two Compose files and `.env.production`, and pulls the image. CI does not build or push images.

---

## Prerequisites

- Docker Engine 24+ and Docker Compose v2 on both machines
- An nginx-proxy setup on the server (see [nginx-proxy](https://github.com/nginx-proxy/nginx-proxy)), and the external Docker network it uses, named `proxy`
- `docker login ghcr.io` on the development machine with a token that has `write:packages`, and on the server with a token that has `read:packages`

---

## First deployment

### 1. Build and push the image

On the development machine, from a clean checkout of `main`:

```bash
docker compose build app
docker push ghcr.io/oekeur/beerjudge:latest
```

`docker-compose.yaml` names the build output `ghcr.io/oekeur/beerjudge:latest`, so the build tags it for the registry.

### 2. Copy the Compose files to the server

```bash
ssh <server> 'mkdir -p ~/git/beerjudge'
scp docker-compose.yaml docker-compose.prod.yaml <server>:git/beerjudge/
```

### 3. Create the environment file

On the server, create `~/git/beerjudge/.env.production` from `.env.docker.example` and set at minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=           # generate with: php artisan key:generate --show
APP_URL=https://yourdomain.com
CLUB_NAME=Your Club Name

DB_PASSWORD=changeme

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=beerjudge@yourdomain.com

SENTRY_LARAVEL_DSN=   # optional; leave empty to disable error tracking

VIRTUAL_HOST=yourdomain.com
LETSENCRYPT_HOST=yourdomain.com   # only if acme-companion should issue the certificate
```

`docker-compose.prod.yaml` loads this file as the app container's environment through `env_file`. nginx-proxy reads `VIRTUAL_HOST` and `LETSENCRYPT_HOST` from that environment, so no host-specific Compose overlay is needed.

`DB_PASSWORD` has to be set in a second place. The Postgres service gets `POSTGRES_PASSWORD` from `${DB_PASSWORD}`, which Compose resolves from the shell or a `.env` file next to the Compose files, not from `.env.production`. Without either, Postgres falls back to the default `secret`. Put the same value in `~/git/beerjudge/.env`:

```dotenv
DB_PASSWORD=changeme
```

The password only takes effect on a fresh `postgres_data` volume; see Troubleshooting for changing it later.

See [docs/env-reference.md](env-reference.md) for the full variable list.

### 4. Create the proxy network (if it does not exist)

```bash
docker network create proxy
```

### 5. Start the stack

```bash
cd ~/git/beerjudge
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml pull app
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d
```

Do not pass `--build` on the server: there is no source to build from. On first start, the entrypoint runs `php artisan migrate --force` automatically.

---

## Verify the deployment

```bash
# Check all three containers are running
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml ps

# Tail the app logs
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml logs -f app

# Check the migrate output in the startup logs
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml logs app | grep -i migrat

# Confirm the server runs the image you pushed: compare this digest with the one docker push printed
docker image inspect -f '{{index .RepoDigests 0}} {{.Created}}' ghcr.io/oekeur/beerjudge:latest

# Confirm the service responds
curl -I https://yourdomain.com
```

The app is ready when you see an HTTP 200 or 302 response and the startup logs show migration output without errors. A container showing "Up 2 hours" only means it restarted then; check the image digest to see which build is live.

---

## Updating / redeploying

On the development machine, from a clean checkout of `main`:

```bash
docker compose build app
docker push ghcr.io/oekeur/beerjudge:latest
```

On the server:

```bash
cd ~/git/beerjudge
docker tag ghcr.io/oekeur/beerjudge:latest beerjudge:previous   # rollback point
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml pull app
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d app
```

If `docker-compose.yaml` or `docker-compose.prod.yaml` changed on `main`, copy them to the server again (step 2) before `up`.

The entrypoint runs `migrate --force` on each start, so new migrations apply automatically during the restart. Config, route, and view caches are also rebuilt. There is a brief period (typically a few seconds) while the new container starts where the app returns 502.

Watchtower runs on the server but does not deploy BeerJudge. Its container has no ghcr credentials, so its daily check fails with `unauthorized` on this private image. Pull by hand as above.

### Rolling back

```bash
docker tag beerjudge:previous ghcr.io/oekeur/beerjudge:latest
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d app
```

Migrations already applied by the newer image stay applied. Roll back the schema by hand if the older code cannot run against it.

---

## Troubleshooting

### "No application encryption key has been specified"

`APP_KEY` is empty in `.env.production`. Generate one:

```bash
php artisan key:generate --show
```

Copy the output into `.env.production`, then recreate the container. `env_file` is read only when the container is created, so `restart` keeps the old values:

```bash
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d --force-recreate app
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

If your nginx-proxy uses a different name, add `name: <network>` under `networks.proxy` in `docker-compose.prod.yaml` on the server.

### Vite manifest missing / assets 404

The image bakes `public/build/` in at build time. A 404 means the pushed image was built without assets or the server still runs an older image. Rebuild and push on the development machine, then pull and `up -d app` on the server (see Updating).

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
