---
id: TASK-67
title: Remove status from Competition mass assignment
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
labels:
  - security-hardening
dependencies: []
priority: low
type: chore
ordinal: 67000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Competition::$fillable includes status, so the read-only Status field (TASK-54) is a form-level guard only; any create() or update() with status bypasses the lifecycle actions. No such path is reachable from the UI today. Found by the TASK-54 conformance gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 status is set only through explicit lifecycle methods or forceFill; existing writers and tests still pass
<!-- AC:END -->
