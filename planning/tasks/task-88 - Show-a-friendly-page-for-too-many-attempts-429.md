---
id: TASK-88
title: Show a friendly page for too many attempts (429)
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - auth
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: enhancement
ordinal: 85000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A throttled login, magic-link or reset request shows Laravel's bare '429 Too Many Requests' page (no resources/views/errors/429.blade.php). Rare now that login is keyed by email+IP, but confusing when it happens. Found 2026-10-08/09.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A translated 429 page (en + nl) explains to wait and try again, in the app's standalone error-view style
- [ ] #2 A feature test covers it
<!-- AC:END -->
