# Changelog

Reconstructed from the git history on 2026-10-07. Each version covers one ISO week of commits; weeks without commits have no version. The date is the last commit of that week. Styling iterations, static-analysis passes, CI and test plumbing, and tracker housekeeping are summarised rather than listed.

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
