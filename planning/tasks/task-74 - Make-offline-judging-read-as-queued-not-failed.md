---
id: TASK-74
title: 'Make offline judging read as queued, not failed'
status: Done
assignee: []
created_date: '2026-10-08 11:39'
updated_date: '2026-10-09 11:57'
labels:
  - pwa
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: enhancement
ordinal: 74000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A scoresheet submitted without a connection shows the red retry badge (judging.retry, 'Opnieuw' in Dutch), although it is queued and syncs on its own within 5 s of the connection returning, without a reload (1 of 1 probes, 2026-10-08). Reloading /judging/{id} while offline fails with ERR_INTERNET_DISCONNECTED: the service worker caches js/css/woff2 but not the page. docs/user-journeys.md, finding 6.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A sheet waiting for a connection shows a neutral queued/offline state; the red retry state is kept for server rejections
- [x] #2 Decide and document whether the judging page itself should load offline; if yes, the service worker serves it from cache
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 3.1)

Review (2026-10-09): gates CLEAN after 8 rounds (design decisions by Oscar: server revision guard, adopt-server notice, one active tab). Check in a browser: server running from this worktree on http://localhost:8029 (APP_ENV=browser-test; if stopped: cd <dev-checkout>/.claude/worktrees/fix+task-74-offline-judging && PHP_CLI_SERVER_WORKERS=4 APP_ENV=browser-test php artisan serve --host=localhost --port=8029). Log in at http://localhost:8029/login as judge@browser-test.local / password, open /judging/1. (1) Devtools Network -> Offline, score and submit an entry: grey 'Waiting for connection' badge, no red Retry; back Online: 'Submitted' within seconds, no reload. (2) Open /judging/1 in a second tab: banner 'Judging is open in another tab.' with 'Use this tab'; click it: the first tab switches to the banner. Banner screenshots: storage/logs/one-tab/banner-{en,nl}-390.png.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Judging screen: network failures show a grey 'Waiting for connection' state (red Retry kept for server errors), retried on the online event and every 5 s; accessible labels and guide titles translated. Decided not to cache the page offline (documented). Hardened over 8 gate rounds after a journey flake exposed a real submit race: per-entry save chain with 15 s abort, server-side ordering guard (scoresheets.client_revision; stale payloads ignored and answered with the stored sheet), snapshot-per-revision, adopt-server with an 'Updated from another device' notice, one IDB row per entry, one active judging tab per browser via Web Locks with take-over. Verified: PHPUnit 283, Playwright 38/38 (reproductions of every gate finding committed), journeys 100/0 twice, Pass A/B CLEAN; Oscar approved in the browser 2026-10-09. Plan task 3.1.
<!-- SECTION:FINAL_SUMMARY:END -->
