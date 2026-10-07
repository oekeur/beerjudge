---
id: TASK-55
title: Remove or apply the unused RequireAdmin middleware
status: Done
assignee:
  - '@claude'
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 21:16'
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
- [x] #1 RequireAdmin class, its 'admin' alias and its MiddlewareTest cases are removed; the suite passes
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decision (Oscar, 2026-10-07): remove it.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Removed the unused RequireAdmin middleware class and its 'admin' alias (bootstrap/app.php). The MiddlewareTest cases under the old RequireAdmin comment exercise ResultsController's own gate, so they stay with a corrected section comment. docs/member/active-member-middleware-reference.md now says panel access goes through Member::canAccessPanel(). Gates: Pass A CLEAN, Pass B CLEAN.
<!-- SECTION:FINAL_SUMMARY:END -->
