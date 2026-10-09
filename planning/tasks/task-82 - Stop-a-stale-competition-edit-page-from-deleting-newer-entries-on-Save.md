---
id: TASK-82
title: Stop a stale competition edit page from deleting newer entries on Save
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - v1
dependencies: []
documentation:
  - docs/user-journeys.md
priority: high
type: bug
ordinal: 79000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The competition edit page's entries Repeater is relationship-bound: on Save, Filament deletes every entry row that is missing from the page's form state. An admin who opened the page before members registered more entries deletes those entries silently when they press Save (any field, any tab). Reproduced by gate probes on 2026-10-08/09 (docs/user-journeys.md, 2026-10-09 log, 'Found while fixing' a). Most likely during registration, when entries arrive while an admin has the page open.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Saving a competition edit page that was opened before another entry was registered keeps that entry, covered by a feature test
- [ ] #2 An admin who removed an entry on the page still deletes it on Save (intended deletes keep working)
<!-- AC:END -->
