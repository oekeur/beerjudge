# Plan: Prepare beerjudge for open-sourcing

**File:** planning/2026-10-12-public-launch.md
**Goal:** Land TASK-93..102 so the repo can be made public: licensed and credited, free of club-specific hardcoding, self-hostable from a public image, with a working README quickstart, ending in a v1.0.0 release.
**Out of scope:** Flipping repo visibility, making the ghcr package public, granting the repo write access to the existing package if Actions needs it, and enabling GitHub private vulnerability reporting (all Oscar's). Configurable scoresheet layouts for mead/cider (TASK-37). Multi-club support. Rewriting git history. Changing Oscar's own deploy flow (base/override/prod compose files keep working as today).
**Constraints:** Decisions 2026-10-12: MIT; keep the beerjson BJCP JSON (MIT, modified) with credit; keep BA text with attribution; drop the scoresheet PDF; leave history; generic deploy doc plus Oscar's server specifics in gitignored `docs/private/`; Claude sets description/topics and pushes the v1.0.0 tag and release last; SECURITY.md points at GitHub private vulnerability reporting; neutral icon = current pint glass without shield/XXX, the arms version becomes Foebar's override. Executed serially in one session: each task lands (branch, PR, CI green, squash-merge, tracker Done) before the next starts. File owners: README.md -> TASK-97 only; CLAUDE.md -> TASK-102 only; `docs/private/` and its `.gitignore` rule are created by TASK-95. A pushed tag is never moved.

---

## Phase 1: License and credits

**Outcome:** The repo is legally publishable: MIT license, correct package metadata, third-party data credited, PDF gone.

- [x] TASK-93 LICENSE and composer.json — done when: `LICENSE` holds the MIT text (copyright 2026 Oscar Keur) and `composer validate --no-check-publish` passes with `name: oekeur/beerjudge` and a one-line description [AC 1,2]. The README license link and Rob credit moved to TASK-97 AC 3.
  Context: rob518183 consented 2026-10-12 (TASK-93 notes).
- [x] TASK-94 THIRD_PARTY_NOTICES.md and PDF removal — done when: `SCP_BeerScoreSheet.pdf` is gone; `THIRD_PARTY_NOTICES.md` names beerjson/bjcp-json with its full MIT notice and "modified: text fixes", and lists the BA 2026 guidelines with the exact `Style::SOURCE_ATTRIBUTIONS['ba_beer']` string; `docs/admin/bjcp-seeder-reference.md` names the source repo [AC 1,2,3,4].
  Context: verified 2026-10-12: upstream is beerjson/bjcp-json styles/bjcp_styleguide-2021.json, MIT (c) 2022 BeerJSON; local copy differs by ~300 word-split fixes. No code references the PDF.

Unknowns: none found after checking (PDF unreferenced; attribution constant exists).

---

## Phase 2: Generalize the app

**Outcome:** A club other than Foebar can run the app with its own name, icon and timezone; Foebar keeps its branding through config; scoring limits have one source; CI proves Postgres.

- [x] TASK-99 APP_TIMEZONE — done when: `config/app.php` reads `env('APP_TIMEZONE', 'UTC')`; `.env.example`, `.env.docker.example` and `docs/env-reference.md` list it [AC 1,2].
- [x] TASK-100 Scoresheet maxima single source — done when: every section maximum in `StoreScoresheetRequest` (5 `max:` rules) and `judging/show.blade.php` (5 `@for` bounds, 5 aria `'max' =>` values, 5 header and 5 read-only `/N` labels, and the `/ 50` totals via `array_sum`) reads `Scoresheet::SECTION_MAX[...]`, and the rendered judging page is unchanged (revised during execution: 10 display labels and the totals were found beyond the original 15 sites); scoresheet and judging feature tests pass unchanged [AC 1,2].
  Context: verified 2026-10-12: `Scoresheet::SECTION_MAX` exists and `results/judge-report.blade.php:108` already uses it.
- [x] TASK-95 Configurable branding — done when: (a) `public/manifest.json` is deleted and a `GET /manifest.json` route returns `name` "{CLUB_NAME} BeerJudge" ("BeerJudge" when empty) and a description that names CLUB_NAME when set and no club when empty; (b) default `resources/images/icon.svg`, `public/favicon.svg`, `public/icons/icon-{192,512}.png` show the pint glass without shield or XXX; (c) with `CLUB_ICON_DIR` set to a directory holding `icon-192.png`, `icon-512.png`, `favicon.svg`, the manifest icon URLs and `<x-app-head>` links resolve to routes that return those files' bytes (feature test asserts 200 and identical content); (d) feature tests cover empty name, set name, default icons and override icons; (e) the current arms icons and the unreferenced root `logo.png` move to `resources/branding/foebar/`; (f) `.gitignore` ignores `docs/private/`, and `docs/private/deploy-notes.md` says how Foebar production sets `CLUB_NAME` and `CLUB_ICON_DIR` before deploying this image (revised during execution: the image already contains `resources/branding/foebar/` at `/var/www`, so no mount is needed); (g) `docs/pwa-reference.md` and `docs/env-reference.md` describe the override. Oscar checks the new icon visually before it lands [AC 1-7].
  Context: verified 2026-10-12: nginx `try_files $uri $uri/ /index.php` reaches Laravel only once the static manifest is deleted; vite-plugin-pwa `manifest: false`, SW precaches only js/css/woff2. `errors/500.blade.php` keeps static default icon paths (`/icons/...`, `/favicon.svg` stay as files); its `/manifest.json` link now hits the route, which is acceptable on a 500.
- [x] TASK-101 Postgres CI job — done when: `ci.yml` has a job with a `postgres:16` service that sets `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` and `DB_URL=` (phpunit.xml sets sqlite and `:memory:` without force), runs `php artisan migrate --force` against the service, then `php artisan test`, and is green on the PR [AC 1]; `DemoSeeder`'s `Style::first()` fallback gets a deterministic order that picks a BJCP style, fixing `BaStyleSeederTest::test_demo_competition_gets_only_bjcp_styles` on Postgres [AC 2].
  Context: verified 2026-10-12 by red-team on a throwaway postgres:16: 302/303 pass; the one failure is that DemoSeeder ordering bug (also a real bug on production's engine). The MySQL claim moved to TASK-102 AC 3.

Unknowns: none open. The Postgres result is known (above); the Foebar icon gap is closed by (f).

---

## Phase 3: Self-hosting and README

**Outcome:** A stranger can go from the image or a clone to a first admin login by following the docs.

- [x] TASK-96 Public image and generic deploy guide — done when: `.github/workflows/release-image.yml` builds the Dockerfile on pull requests (no push) and on `v*` tags pushes `ghcr.io/oekeur/beerjudge:{version}` and `:latest` with `GITHUB_TOKEN` (`packages: write`), and its PR build is green; a new standalone `docker-compose.selfhost.yaml` (app with `ports: 8080`, `env_file: .env`, postgres, redis; no proxy network, no dev override) serves the login page on localhost:8080 when run alone in an empty scratch directory with a filled `.env` and a locally built image tagged as the ghcr name; `docs/docker-howto.md` and `docs/docker-reference.md` describe that file, `member:create`, and contain no Watchtower, laptop-push or ssh-path text (`git grep -n "Watchtower\|laptop\|ssh " docs/docker-*.md` empty); those specifics move to `docs/private/deploy-notes.md` [AC 1-4].
  Context: verified 2026-10-12 by red-team: base `docker-compose.yaml` has no ports/env_file, the override is dev mode, prod needs an external `proxy` network. Base/override/prod files stay unchanged.
- [x] TASK-97 README — done when: following the quickstart verbatim in a fresh clone in the scratchpad (stdin piped only where the README says the command prompts) reaches a logged-in admin page: `composer install` before `key:generate`, `php artisan member:create --admin` documented as interactive, DemoSeeder mentioned; README has a who-it-is-for / not-for section, a License section linking `LICENSE`, credits naming rob518183 and linking `THIRD_PARTY_NOTICES.md`; its self-hosting section points to the selfhost guide and contains no Watchtower or private-package wording [AC 1-4].

Unknowns:
- The tag push path (Actions writing to the existing private package) is only proven by the first tag; see TASK-98.

---

## Phase 4: Cleanup and release

**Outcome:** Nothing stale or personal remains, and v1.0.0 is tagged.

- [x] TASK-102 Cleanup — done when: `PLAN.md` deleted; `CLAUDE.md` has no home-directory path, no "once the project is scaffolded" text, no MySQL claim, and its PWA lines match TASK-95; `planning/` (tracker included) contains no hostname, IP address or credential, and the home-directory and server-checkout paths in task-27, 74, 78 and 81 (and any other tracker hit of `git grep -nE "/home/[a-z]+" planning`) are redacted to `<dev-checkout>` / `<server-checkout>` by hand-editing those files (Oscar's OK 2026-10-12, scoped to this redaction) (`git grep -nE "([0-9]{1,3}\.){3}[0-9]{1,3}|@[a-z0-9-]+\.(nl|com|net)" planning` reviewed: only example/placeholder hits); `docker run --rm -v "$PWD:/repo" zricethezav/gitleaks detect -s /repo` over full history reports 0 leaks or each finding is shown to be a placeholder [AC 2-5; AC 1 done 2026-10-12].
- [x] TASK-98 SECURITY.md, metadata, release — done when: `SECURITY.md` points to GitHub private vulnerability reporting and names 1.x as supported; `CHANGELOG.md` has a 1.0.0 section; `gh repo view --json description,repositoryTopics` shows the description and topics homebrew, bjcp, laravel, filament, pwa; tag `v1.0.0` on merged main and `gh release view v1.0.0` succeeds; the tag's image workflow run is reported (pass, or failure with cause) [AC 1-4]. Runs last.
  Context: if the tag workflow fails on a settings issue (package access), Oscar fixes the setting and the run is re-run on the same tag; if it needs a code fix, the fix ships as v1.0.1. The tag is never moved.

---

## Open decisions

- [x] Tracker paths: redact (Oscar, 2026-10-12).
