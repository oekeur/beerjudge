# Docker Setup Reference

The Docker setup runs nginx, php-fpm, and a queue worker inside a single container. Postgres and Redis run as separate containers via Docker Compose. Four Compose files cover the setups: a standalone file for self-hosting, and a base file with a development and a reverse-proxy overlay.

See [docs/docker-howto.md](docker-howto.md) for step-by-step self-hosting instructions.

## Published image

`.github/workflows/release-image.yml` builds the `Dockerfile` and pushes `ghcr.io/oekeur/beerjudge` when a `v*` tag is pushed, tagged with the full version (`1.0.0`), the minor line (`1.0`) and `latest`. On pull requests that touch the Dockerfile, `docker/`, the dependency manifests or the Vite configs it only builds, so a broken image is caught before a release.

---

## Compose files

### `docker-compose.selfhost.yaml` (standalone)

Use on its own: `docker compose -f docker-compose.selfhost.yaml up -d`. It is the file the self-hosting guide uses and needs nothing else from the repository.

- `app`: pulls `ghcr.io/oekeur/beerjudge:${BEERJUDGE_VERSION:-latest}`, reads `.env` through `env_file`, and publishes container port 80 on `${BEERJUDGE_PORT:-8080}`. Like the base file, it hardcodes `DB_HOST=postgres` and `REDIS_HOST=redis`.
- `postgres` and `redis`: same images, healthchecks and volumes as the base file. `POSTGRES_PASSWORD` comes from `${DB_PASSWORD}` in the same `.env`, and Compose stops with an error when it is empty.

### `docker-compose.yaml` (base)

Defines the three services (`app`, `postgres`, `redis`) and the two persistent data volumes.

- `app`: builds from `Dockerfile` or pulls `ghcr.io/oekeur/beerjudge:latest`. Injects `DB_HOST=postgres`, `DB_PORT=5432`, `REDIS_HOST=redis`, `REDIS_PORT=6379` as environment variables, overriding any values in `.env`.
- `postgres`: `postgres:16-alpine`. Waits for the healthcheck (`pg_isready`) before `app` starts.
- `redis`: `redis:7-alpine`. Waits for the healthcheck (`redis-cli ping`) before `app` starts.

### `docker-compose.override.yaml` (dev, auto-applied)

Docker Compose merges this file automatically when you run `docker compose up` without `-f` flags. It is the development overlay.

Changes from the base:
- `app` reads env vars from `.env` via `env_file`.
- `app` is served on `http://localhost:8000`.
- The project directory is mounted into the container (`/var/www`) as a host bind mount, so PHP changes are reflected without a rebuild. Two named volumes (`storage`, `bootstrap_cache`) overlay those subdirectories so container-generated files do not leak back to the host.
- `app` uses `entrypoint.dev.sh` instead of `entrypoint.sh` (no cache warming; runs `artisan serve` instead of nginx).
- `postgres` is exposed on `localhost:5432`.
- `redis` is exposed on `localhost:6379`.
- A `vite` service (`node:24-alpine`) runs `npm run dev` inside the container with HMR on port 5173.

### `docker-compose.prod.yaml` (production overlay)

Not auto-applied. Used with: `docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d`

Changes from the base:
- `app` reads env vars from `.env.production`.
- `app` is attached to both `default` and the external `proxy` network (for nginx-proxy TLS termination). `postgres` and `redis` stay on `default` only, so the reverse proxy cannot reach them.
- No port bindings (the reverse proxy handles external traffic).

---

## Services

### `app`

| Property | Dev | Prod |
|---|---|---|
| Image | Built locally from `Dockerfile` | `ghcr.io/oekeur/beerjudge` (published) or built locally |
| PHP | 8.5-fpm-alpine | same |
| Web server | `artisan serve` (via supervisord) | nginx (via supervisord) |
| Port | 8000 (host) -> 80 (container) | `BEERJUDGE_PORT` (selfhost file) or none (proxy overlay) |
| Entrypoint | `entrypoint.dev.sh` | `entrypoint.sh` |
| Source mount | Host bind mount at `/var/www` | No mount; code baked into image |

### `postgres`

- Image: `postgres:16-alpine`
- Credentials: set via `POSTGRES_USER`, `POSTGRES_PASSWORD`, `POSTGRES_DB` in Compose, defaulting to `beerjudge`/`secret`/`beerjudge`. Override with `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE` in your `.env` (Compose reads them with `${VAR:-default}`).
- Data: persisted in `postgres_data` named volume.
- Healthcheck: `pg_isready -U ${DB_USERNAME:-beerjudge}` every 5 s.
- Port 5432 exposed to the host in dev; not exposed in prod.

### `redis`

- Image: `redis:7-alpine`
- No authentication configured.
- Data: persisted in `redis_data` named volume.
- Healthcheck: `redis-cli ping` every 5 s.
- Port 6379 exposed to the host in dev; not exposed in prod.

### `vite` (dev only)

- Image: `node:24-alpine`
- Runs `npm install && npm run dev` on startup.
- Source is a host bind mount; `node_modules` is a named volume (`vite_node_modules`) so the host and container do not share it.
- Port 5173 exposed to the host for HMR.

---

## Named volumes

| Volume | Service | Persists |
|---|---|---|
| `postgres_data` | postgres | All database data |
| `redis_data` | redis | Redis RDB snapshots |
| `storage` | app (dev only) | Laravel storage (logs, compiled views, uploaded files) |
| `bootstrap_cache` | app (dev only) | Laravel bootstrap cache |
| `vite_node_modules` | vite (dev only) | npm packages inside the Vite container |

`postgres_data` and `redis_data` survive container restarts and `docker compose down`. They are deleted by `docker compose down -v`.

In production there are no `storage` or `bootstrap_cache` volumes; cache files exist inside the container and are regenerated on each deploy by the production entrypoint.

---

## Dockerfile build stages

The image is built in three stages:

1. **`composer-deps`**: installs PHP dependencies without dev packages, using `--optimize-autoloader --no-scripts`.
2. **`assets`**: installs npm packages and runs `npm run build`, producing `public/build/`.
3. **`runtime`** (`php:8.5-fpm-alpine`): installs nginx, supervisord, and the PHP extensions (`pdo_pgsql`, `zip`, `pcntl`, `intl`, `bcmath`, `redis` via PECL). Copies app source, vendor directory, and built assets. Creates required storage directories and sets `www-data` ownership. Writes a Sentry PHP ini (`zend.exception_ignore_args = Off`).

PHP extensions installed: `pdo_pgsql`, `zip`, `pcntl`, `intl`, `bcmath`, `redis`.

---

## Entrypoints

### `entrypoint.sh` (production)

Runs on every container start:
1. `php artisan package:discover`
2. `php artisan migrate --force`
3. `php artisan storage:link`
4. `php artisan config:cache`
5. `php artisan route:cache`
6. `php artisan view:cache`
7. `exec supervisord -c /etc/supervisord.conf`

Supervisord then starts: nginx, php-fpm, queue-worker (`queue:work --sleep=3 --tries=3`).

### `entrypoint.dev.sh` (development)

Runs the same first three steps, then skips cache warming and starts supervisord with `supervisord.dev.conf`. That config runs `artisan serve --host=0.0.0.0 --port=80` and the queue worker; nginx and php-fpm are not used.

---

## Nginx

Config at `docker/nginx.conf`. Listens on port 80. Root at `/var/www/public`. PHP requests proxied to `127.0.0.1:9000` (php-fpm). Hidden files (`.ht*`) denied. Single server block; TLS is handled externally by the reverse proxy.

---

## Production network

In production, only the `app` service joins the `proxy` external network so an nginx-proxy container can route HTTPS traffic to it. Postgres and Redis stay on the private default network. The `proxy` network must be created on the host before starting the stack:

```bash
docker network create proxy
```

The service name `proxy` in `docker-compose.prod.yaml` is the network name in Compose configuration; the actual Docker network name may differ (e.g. `webproxy`). Set it with `name: <network>` under `networks.proxy` in the server's copy of `docker-compose.prod.yaml`.

---

## Related

- [docs/docker-howto.md](docker-howto.md) — step-by-step deployment instructions
- [docs/env-reference.md](env-reference.md) — all environment variables
- [README.md](../README.md) — quick-start commands for each setup mode
