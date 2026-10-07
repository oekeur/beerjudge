---
id: TASK-55
title: Remove or apply the unused RequireAdmin middleware
status: Backlog
assignee: []
created_date: '2026-10-07 15:10'
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
