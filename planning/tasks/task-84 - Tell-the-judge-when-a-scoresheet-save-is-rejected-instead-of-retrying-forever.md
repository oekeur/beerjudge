---
id: TASK-84
title: Tell the judge when a scoresheet save is rejected instead of retrying forever
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - pwa
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 81000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A 4xx response to POST /scoresheets (419 session expired, 422 competition closed, 403) is marked 'error' and retried every 5 s with no limit. On judging day an expired session or CSRF token leaves the sheet unsaved with only a red Retry badge and no hint to log in again. Found in the 2026-10-09 gate rounds (docs/user-journeys.md, 'Found while fixing' g).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 419 and 401 responses show a clear message to log in again (and keep the sheet queued locally), 422/403 show why the save was refused; neither retries in a loop
- [ ] #2 A browser test covers the expired-session case
<!-- AC:END -->
