---
id: TASK-110
title: >-
  Record failed jobs and report rejected scoresheet saves before the first club
  evening
status: Done
assignee: []
created_date: '2026-10-13 15:34'
updated_date: '2026-10-13 15:34'
labels:
  - launch-ops
  - sentry
dependencies: []
documentation:
  - docs/user-journeys.md
priority: high
type: bug
ordinal: 107000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Observability gaps found 2026-10-13: production had no failed_jobs table, and a scoresheet save rejected by the server left a trace only in the judge's browser console. Oscar approved fixing both and a nightly backup.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A job that runs out of tries is stored in failed_jobs and can be retried
- [x] #2 A rejected scoresheet save is reported to Sentry once per entry and status, with the server's message
- [x] #3 beerjudge is backed up nightly on the VPS
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Code in PR #65 (55ce283): failed_jobs migration (FailedJobsTableTest fails without it); judging.js reportSyncFailure() sends a Sentry warning per entry and status per page load with the HTTP status, server message, uuid, revision and navigator.onLine (judging-sync-report.spec.ts injects a fake DSN and catches the ingest request; fails without the change); docs/docker-howto.md covers queue:failed and queue:retry. A first CI failure was test pollution (a leftover submitted sheet), reproduced locally and fixed by clearing the judge's sheets. Server: ~/infra/beerjudge/bin/backup.sh, cron 03:45, custom-format dumps kept 14 days in ~/infra/beerjudge/backups; first run verified with pg_restore -l (15 tables). Crontab backup at ~/crontab.bak-2026-10-13. Both code changes reach production with the next deploy.
<!-- SECTION:FINAL_SUMMARY:END -->
