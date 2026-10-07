---
id: TASK-55
title: Remove or apply the unused RequireAdmin middleware
status: In Progress
assignee:
  - '@claude'
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 21:06'
labels:
  - auth
dependencies: []
priority: low
type: chore
ordinal: 55000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
RequireAdmin is aliased as 'admin' in bootstrap/app.php but applied nowhere; panel access goes through Member::canAccessPanel().
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 RequireAdmin class, its 'admin' alias and its MiddlewareTest cases are removed; the suite passes
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decision (Oscar, 2026-10-07): remove it.
<!-- SECTION:NOTES:END -->
