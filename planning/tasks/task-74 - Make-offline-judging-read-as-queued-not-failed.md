---
id: TASK-74
title: 'Make offline judging read as queued, not failed'
status: Backlog
assignee: []
created_date: '2026-10-08 11:39'
updated_date: '2026-10-08 13:14'
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
- [ ] #1 A sheet waiting for a connection shows a neutral queued/offline state; the red retry state is kept for server rejections
- [ ] #2 Decide and document whether the judging page itself should load offline; if yes, the service worker serves it from cache
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 3.1)
<!-- SECTION:NOTES:END -->
