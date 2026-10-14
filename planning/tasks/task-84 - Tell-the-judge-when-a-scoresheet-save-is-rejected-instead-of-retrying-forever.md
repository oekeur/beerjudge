---
id: TASK-84
title: Tell the judge when a scoresheet save is rejected instead of retrying forever
status: Planned
assignee: []
created_date: '2026-10-09 13:16'
updated_date: '2026-10-14 14:33'
labels:
  - pwa
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 110000
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

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-judging-rejected-save-feedback.md (Phase 1)
Goal: a 4xx on POST /scoresheets stops the 5 s retry loop and tells the judge to log in again (401/419) or why the sheet was refused (403/422).
Phase 1 - Translated server reasons, client classification (auth/rejected states, banner, badges, no retry loop), updated sync-report spec and new session-expired spec: one task, one browser review [AC 1,2]
[RISK] Wording of the signed-out banner for deactivated members - Deferred: confirm with Oscar in the browser review
Open (non-blocking): login return path lands on / not the judging screen; assumes TASK-107 landed (lang key parity)
<!-- SECTION:PLAN:END -->
