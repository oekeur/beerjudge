# Plan: Sentry User Feedback Widget

**Goal:** Add the persistent Sentry User Feedback Widget to the judging screen and Filament admin panel so users can submit feedback at any time without requiring an error to have occurred.
**Out of scope:** Session Replay integration, custom feedback UI (using Sentry's embeddable widget as-is), feedback on auth/results pages.
**Constraints:** `@sentry/browser` is not yet installed (package.json has only alpinejs + dexie); DSN is already in config; no shared Blade layout — each view is standalone.

---

## Phase 1: Install Sentry JS SDK and create a shared init module

**Outcome:** `@sentry/browser` is importable in the Vite build; a reusable `sentry.js` module initialises Sentry with `feedbackIntegration()` using a DSN read from a `<meta>` tag.

Tasks:
- [x] Run `npm install @sentry/browser` to add the package.
- [x] Create `resources/js/sentry.js` — reads DSN from `<meta name="sentry-dsn">`, reads user name/email from `<meta name="sentry-user">` (JSON), calls `Sentry.init()` with `feedbackIntegration({ colorScheme: 'light', autoInject: true })`, and pre-fills user contact info on the widget via `Sentry.setUser()`.
- [x] The module should be a no-op (silent early return) when the meta tag is absent, so it is safe to import unconditionally.

Unknowns:
- [RISK] The DSN is in `config('sentry.dsn')` which may be `null` in local dev. The module must guard against an empty DSN and skip `Sentry.init()` entirely to avoid a Sentry console error.
- Is the installed `@sentry/browser` version compatible with `feedbackIntegration`? Minimum required is 7.85.0 — check after install.

---

## Phase 2: Non-Filament Blade views (judging screen + app pages)

**Outcome:** The Sentry feedback widget appears on all authenticated non-admin pages: judging screen, entry registration, results. User name/email are pre-filled.

Tasks:
- [x] Add `<meta name="sentry-dsn" content="{{ config('sentry.dsn') ?? '' }}">` and `<meta name="sentry-user" content='@json(["name" => auth()->user()?->name, "email" => auth()->user()?->email])'>` to the `<head>` of every non-Filament Blade view that loads Vite assets: `judging/show.blade.php`, `entries/create.blade.php`, `results/index.blade.php`, `results/show.blade.php`. (Auth and welcome views are unauthenticated — skip them.)
- [x] Import the shared module in `resources/js/app.js`: `import './sentry.js';` — this covers all views loading `app.js`.
- [x] Import the shared module in `resources/js/judging.js`: `import './sentry.js';` — this covers the judging screen which loads `judging.js` instead of `app.js`.
- [x] Run `npm run build` and smoke-test: confirm the widget button appears on the judging screen and the entry registration page.

Unknowns:
- `judging.js` uses Alpine + Dexie and calls `Alpine.start()`. Sentry must be initialised before Alpine finishes mounting, but order within the same module entry point is sequential — this should be safe. Verify in practice.
- `results/show.blade.php` only loads `app.css` (no `app.js`) — the meta tags will be present but Sentry JS won't load on that page. Either also load `app.js` there, or accept that the results page won't show the widget. Flag during execution.

---

## Phase 3: Filament admin panel

**Outcome:** The Sentry feedback widget appears on all Filament admin pages for logged-in admins.

Tasks:
- [x] In `app/Providers/Filament/AdminPanelProvider.php`, add a `renderHook` for `'panels::head.end'` that returns a Blade string containing the two `<meta>` tags and a `<script type="module">` that imports `@sentry/browser` from a CDN ESM build (or, if Filament supports Vite asset injection, include it via `@vite`).
- [x] Decide between CDN ESM and Vite injection (see Open decisions below). The simplest approach is a CDN ESM `<script type="module">` similar to the existing 500 page, since Filament's panel build is separate from `app.js`.
- [x] Confirm the widget appears on the Filament dashboard after the panel loads.

Unknowns:
- [RISK] Filament's admin panel compiles its own assets separately from the Vite build (it uses `filament/filament`'s own JS pipeline). You cannot simply import `resources/js/sentry.js` from PHP-rendered Blade; it must either use a CDN ESM bundle or be a separate Vite entry point loaded via `@vite`.
- Does Filament v4's `renderHook` accept raw HTML strings? Check `Filament\Support\Facades\FilamentView::renderHook` signature.
- `app.js` is currently empty (`//`). If other non-Filament, non-judging views (e.g. `entries/create.blade.php`, `results/`) should also get the widget, `app.js` would be the right place for that — but this phase is scoped to Filament only.

---

## Phase 4: Tests

**Outcome:** PHPUnit assertions confirm the meta tags are rendered on both the judging screen and admin panel; no regression in existing scoresheet tests.

Tasks:
- [x] Add a test to `tests/Feature/JudgingTest.php` (or a new file) asserting that an authenticated judge sees `<meta name="sentry-dsn">` on the judging screen.
- [x] Add a test asserting that when `SENTRY_DSN` is empty, the meta tag has an empty content value and no JS error is thrown (this can be an assertion on the rendered HTML, not browser-level).
- [x] Run `php artisan test --compact` to confirm no regressions.

Unknowns:
- None anticipated — these are straightforward view/rendering assertions.

---

## Open decisions

- [x] **Filament: CDN ESM.** Inject a `<script type="module">` loading `@sentry/browser` from CDN via `renderHook`.
- [x] **Widget scope: all authenticated pages.** Import `sentry.js` in `app.js` so the widget also appears on entry registration and results pages.
- [x] **English labels.** Leave Sentry's default English strings.
