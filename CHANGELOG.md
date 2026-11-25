# Changelog

Reconstructed from the git history on 2026-10-07. Each version covers one ISO week of commits; weeks without commits have no version. The date is the last commit of that week. Styling iterations, static-analysis passes, CI and test plumbing, and tracker housekeeping are summarised rather than listed.

## [1.1.0] - 2026-11-25

### Upgrade notes
- Three new migrations run on start: member emails are lowercased (case-only duplicates are skipped), a `failed_jobs` table is created, and judges who submitted a scoresheet are added to the competition's judge list.
- New optional `ADMIN_NOTIFICATION_EMAIL`: when set, that address gets an email for every pending invite registration.

### Added
- `ADMIN_NOTIFICATION_EMAIL` notification for pending invite registrations.
- `php artisan backfill:import` imports a historical competition from Google Forms judging data extracted by `backfill/extract_responses.py`; `--dry-run` validates without writing.
- Translated 403 and 429 error pages.
- Failed queue jobs are recorded, and rejected scoresheet saves are reported to Sentry.
- `HistoryStatsService`, the data layer for the planned history and stats pages.

### Changed
- Login throttling no longer lets one shared Wi-Fi IP lock out the whole room.
- The password-reset email request is rate-limited.
- The entries repeater on the competition edit page locks once judging starts.
- The judge list is saved per change instead of synced on Save.
- `docker-compose.prod.yaml` restarts the app container after a reboot.

### Fixed
- Every judge sees the same tasting number for a beer; a judge's own entries stay in the list as locked rows.
- Only judges taking part in a competition can score it.
- Saving a stale competition edit page no longer drops entries registered after it loaded.
- Member emails match regardless of capitals at login, password reset and registration.
- Note length limits, double-tap submit, and opening an invite link while logged in.
- The home page, results, score sheet and judge report pages are translated; a test guards en/nl key parity.
- The last entry on the judging screen stays clear of the feedback button.
- Entry row select options on the competition edit form are memoized (fewer queries).
- CI runs on pull requests into every branch.

### Security
- laravel/framework 13.44.1, symfony/yaml 8.1.12 and patched JS dev dependencies (vite, serialize-javascript, fast-uri) for open advisories.

## [1.0.0] - 2026-10-12

First public release.

### Added
- MIT licence (`LICENSE`) and `THIRD_PARTY_NOTICES.md` crediting the BJCP data from beerjson/bjcp-json and the Brewers Association guidelines.
- Brewers Association 2026 styles: `php artisan styles:scrape-ba` builds `ba_styleguide-2026.json`, and `BaStyleSeeder` seeds the 168 styles as source `ba_beer`. The BA attribution line shows on the judging screen, the style-info sheet and the style edit page.
- Club branding through configuration: the web manifest is generated from `CLUB_NAME`, the default icon is a neutral pint glass, and `CLUB_ICON_DIR` supplies a club's own icons.
- `APP_TIMEZONE` sets the application timezone.
- Self-hosting from a published image: a GitHub Actions workflow pushes `ghcr.io/oekeur/beerjudge` on version tags, and `docker-compose.selfhost.yaml` runs the app, Postgres and Redis from one `.env`.
- CI runs the test suite against Postgres 16 as well as SQLite.
- Closed competitions get a Reopen judging action; the status field is read-only on the form.
- One active judging tab per browser, with take-over, and a server-side ordering guard (`scoresheets.client_revision`) so a stale save never overwrites a newer one.
- Playwright journey walkthrough scripts and documented admin and member journeys.

### Changed
- Magic links show a confirm page; only the POST consumes the token.
- Login is rate-limited per email and IP with a separate per-IP cap, so a club on shared Wi-Fi is not locked out.
- Guests get their locale from `Accept-Language`; magic-link emails follow the member's locale.
- Offline scoresheets read "Waiting for connection" instead of a red Retry.
- The admin panel loads the self-hosted Sentry bundle, and the feedback widget is translated.
- Scoresheet section maxima come from one constant.

### Fixed
- Deactivated admins are logged out of the admin panel, also from an open tab.
- `member:delete` refuses to remove the last active admin.
- The Dashboard and Variance widgets poll; lifecycle buttons update the header in place.
- The demo seeder assigns the styles it names (it used zero-padded codes that never matched) and no longer picks a Brewers Association style on Postgres.
- A self-XSS in the `sentry-user` meta tag.
- The member navigation fits on one line at phone width; remaining raw labels are translated.

### Removed
- The bundled third-party scoresheet PDF.

### Docs
- README with a working quick start, scope, licence and credits; a generic self-hosting guide; `SECURITY.md`.

## [0.8.0] - 2026-10-07

### Added
- Members with an entry get an email with a link to their results sheet when a competition closes. A `results_mailed_at` guard prevents a second send if the status moves back and forth, and admins can resend to one member from the competition edit page.
- The member form refuses to demote or deactivate the last active admin, and deleting that admin is blocked with a notification.
- Members get an email when an admin activates their account.
- VitePress documentation site with a GitHub Pages workflow.
- Backlog.md task tracker under `planning/`.

### Fixed
- Entries registered by members get the next tasting position instead of none; existing entries without one are numbered by a migration.
- The judge-variance widget's Beer column shows the style code and name; it was empty since the beer name field was removed.
- The "Print all" button on the results index, which reopened the same page, is removed.
- The inactive-account login error is translated into Dutch.
- `composer run dev:lite` uses `database/database.sqlite` instead of reading the Postgres database name from `.env`.
- Off-flavour descriptors ticked on the scoresheet are now saved.
- Models throw instead of silently discarding attributes that are not fillable (outside production).
- The error page's "Send error report" button uses a self-hosted Sentry bundle, so ad-blockers no longer break it.
- The Docker build copies the error-report Vite config into the assets stage.

### Docs
- CLAUDE.md, the enhancements PRD and the authentication spec describe the code as it is: email on close, descriptors as a scoresheet column, the magic-link flow.
- Deployment docs describe the actual flow: build and push the image to ghcr from the laptop, pull on the server.
- Recorded `SESSION_SAME_SITE=strict` as the cause of failed magic-link logins.

## [0.7.0] - 2026-09-09

### Added
- Expired and already-used invite links show a page explaining what happened instead of a generic error.

## [0.6.0] - 2026-07-15

### Fixed
- Spelling and spacing in the BJCP style guide data.

## [0.5.0] - 2026-07-06

### Changed
- Logins persist with the remember-me cookie by default.

## [0.4.0] - 2026-07-01

### Added
- Per-judge scoresheet report: a judge's own scores compared with the other judges'.

### Changed
- Redesigned per-entry results view.
- The results index shows members only their own reports; admins get a personal start section.

### Fixed
- Judge notes dropdown on the per-entry scoresheet.
- Scoresheet layout and print gaps made consistent between the results views.

## [0.3.0] - 2026-06-28

### Added
- ABV field on entries, for both the member registration form and the admin entries list, with a "sort by ABV" action to set tasting order.
- Open competitions on the home page link to entry registration.
- Live, polled Livewire widgets on the competition dashboard; the separate judging tab is merged into it.
- Admin progress panel on the judging screen, refreshed every 30 seconds, plus links between the judging screen and the dashboard.

### Changed
- Tasting order is set by dragging entries (`sort_order`); bottle numbers no longer drive it.
- Entry registration uses a searchable style combobox with a brewer-notes hint.
- Compact single-row entry cards in the admin repeater.
- Shared page shell and navigation components with consistent dark mode.

### Fixed
- Inactive users are logged out instead of looping on a redirect.
- The PWA starts at `/` instead of `/judge`.
- Style range columns are cast to numbers consistently.
- Typos in the BJCP style guide data.

## [0.2.0] - 2026-06-21

### Added
- Styles from multiple sources, including admin-created custom styles.
- Password reset.
- Artisan commands `member:create`, `member:password` and `member:delete`.
- Approval notice and beta warning on the registration page.
- Copy buttons for invite and competition links.
- Sentry error tracking, a 500 page with a crash-report form, and the Sentry feedback widget on app pages and the admin panel.
- SRM colour visualization in the appearance guide on the judging screen.
- Sort toggle and bottle-number indicator on the judging entry list, header navigation, a theme toggle and a language preference on the profile page.
- Installable PWA.
- Docker setup for production and development (Vite HMR, live PHP reload) and a `dev:lite` SQLite mode.
- Playwright browser test suite in CI.

### Changed
- Coherent dark-mode palette on the judging screen.
- Internal refactor: services, enums and shared view components.

### Fixed
- Security hardening before public launch.
- A stale draft save can no longer downgrade a submitted scoresheet.
- IndexedDB failures no longer block syncing scoresheets to the server, and Alpine proxies are stripped before Dexie writes.
- Scores start empty instead of zero, and every aspect is required before submitting; score columns are nullable for draft saves.
- Integer route parameters are constrained to numbers.
- Seeded style queries are scoped to BJCP beer styles, and OG/FG gravity points are normalised.

## [0.1.0] - 2026-06-14

First version.

### Added
- Competitions, members, entries and BJCP 2021 styles managed in Filament, with a tabbed competition form (entries, dashboard with ranked standings and stats, judge variance).
- Offline-first judging screen: tap-button score grid, official BJCP placeholder text, off-flavour descriptor checkboxes, a per-section style guide, and a style info modal with category description, history and style comparison.
- Judge assignment and per-entry judging progress.
- Results visible to members once a competition closes (no separate publish step); admins see them at any status.
- English and Dutch, with a per-member language preference.
- Home page dashboard grouping competitions by status, and a `CLUB_NAME` setting for branding.
- Member onboarding: join links, magic-link login, a judge participation link and the entry form.
- Entry style choices limited to the competition's approved styles.
- Demo seeder, a GitHub Actions workflow and git hooks for lint, tests and PHPStan, and a custom Filament theme.

### Changed
- Entries no longer have a beer name, and members no longer have a BJCP rank.
