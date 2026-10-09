# Journey walkthrough

Playwright scripts that walk the admin and member journeys in [docs/user-journeys.md](../../docs/user-journeys.md) in a real browser. They are a manual check on top of `php artisan test` and `npm run test:browser`, not part of CI: they record video and a screenshot per step for a person to review.

```bash
npm run build                       # once, the server needs the Vite manifest
scripts/journeys/run-all.sh         # fresh database, all journeys
scripts/journeys/run-all.sh m4-judge a5-close-and-publish   # subset, current database
```

- Runs `php artisan serve` on `localhost:8010` (`JOURNEY_PORT` to change) against `database/journeys.sqlite`, seeded with the BJCP styles and `DemoSeeder`. `.env`, Postgres and Redis are not touched.
- Output goes to `storage/logs/journeys/<journey>/`: numbered screenshots, `video-<actor>/`, and `results.json` with each step and any console errors, failed requests or 5xx responses, and `serve.log` from the server.
- Each line prints `PASS` or `FAIL` per step. Indented lines are measurements the journey logs (bottle numbers, mail recipients, sync counts).
- Mail goes to the log driver; journeys read magic links, reset links and results mail from `storage/logs/laravel.log`.
- `POST /login` allows 6 attempts per minute per email and IP, and 30 per minute per IP. `login()` caches session cookies per member and waits out a 429, so a full run takes a few minutes longer when it hits the limit.
- Journeys depend on each other in story order: A2 creates the competition that M3, A3, A4, M4, A5, M5 and A6 use (`storage/logs/journeys/state.json`). Run a subset only after a full run.
