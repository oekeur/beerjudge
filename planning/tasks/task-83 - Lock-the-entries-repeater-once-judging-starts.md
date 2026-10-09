---
id: TASK-83
title: Lock the entries repeater once judging starts
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - v1
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 80000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The entries Repeater on the competition edit page stays editable during Judging and after Closed. Entries added there get no bottle number (bottle numbers are assigned on Move to Judging), and Sort by ABV plus Save can change the tasting order mid-judging. Found in the 2026-10-09 gate rounds (docs/user-journeys.md, 'Found while fixing' f).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 From Judging on, entries cannot be added, removed or reordered in the admin (read-only repeater or equivalent), covered by tests
- [ ] #2 Reopen judging keeps the lock
<!-- AC:END -->
