---
id: TASK-69
title: Fix three stale doc lines found during the auth batch
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
labels:
  - docs
dependencies: []
priority: low
type: docs
ordinal: 69000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
docs/admin/filament-overview-explanation.md:64 says the Authenticate middleware enforces is_admin (it is Member::canAccessPanel()). docs/competition/ranking-service-explanation.md:5 still mentions a batch-print view. planning/specs/authentication.md:42 justifies SESSION_SAME_SITE=lax by saying a strict cookie leaves the token already spent; since TASK-39 the GET no longer consumes the token. Found by the TASK-55, 61 and 39 gates, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The three lines match the code
<!-- AC:END -->
