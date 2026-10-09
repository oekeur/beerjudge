---
id: TASK-91
title: Decide whether bottle numbers should follow the tasting order
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - abv-tasting-order
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: chore
ordinal: 88000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
BottleNumberAssignmentService numbers bottles by entry id, while the tasting order (sort_order) is set separately (e.g. Sort by ABV). Bottle numbers therefore never match the tasting sequence. This may be intended (blind codes independent of order) or not. Noticed during the 2026-10-09 gate rounds.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Record the decision (keep by id, or follow sort_order) in planning/specs/competitions.md; if it changes, file the implementation as its own task
<!-- AC:END -->
