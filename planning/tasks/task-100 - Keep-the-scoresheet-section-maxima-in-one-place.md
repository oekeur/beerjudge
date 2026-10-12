---
id: TASK-100
title: Keep the scoresheet section maxima in one place
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
labels:
  - public-launch
dependencies: []
priority: low
type: chore
ordinal: 97000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 14. Limits 12/3/20/5/10 are repeated in Scoresheet, StoreScoresheetRequest and judging/show.blade.php. Single source only; configurable sheet layouts are out of scope until mead or cider (TASK-37) is planned.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 StoreScoresheetRequest and the judging Blade read the maxima from the Scoresheet constant
- [ ] #2 Existing scoresheet tests pass unchanged
<!-- AC:END -->
