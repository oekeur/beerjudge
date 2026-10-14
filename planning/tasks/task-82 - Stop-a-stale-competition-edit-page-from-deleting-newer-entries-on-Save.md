---
id: TASK-82
title: Stop a stale competition edit page from deleting newer entries on Save
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-09 13:16'
updated_date: '2026-10-14 12:41'
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
- [ ] #1 Saving a competition edit page that was opened before another entry was registered keeps that entry, covered by a feature test that fails on main
- [ ] #2 An admin who removed an entry on the page still deletes it on Save (intended deletes keep working)
- [ ] #3 Adding an entry on the page and editing an existing entry fields still save as before
- [ ] #4 After a Save, a second Save on the same page without changes deletes nothing and duplicates nothing
- [ ] #5 A row added on the page, saved, then removed and saved again is deleted
- [ ] #6 An entry registered while the page is open survives two consecutive Saves from that page and ends up after the page rows in the tasting order (sort_order)
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-stale-entries-save.md (Phase 1: scope Repeater deletions to rows the page loaded)
Goal: Save deletes only entries the admin removed on that page; late entries are kept and placed after the page rows.
Phase 1 - remember loaded entry ids across Livewire requests, delete only loaded-but-removed, refresh the loaded set from the post-Save form state, append late entries after the page rows; Livewire tests plus Playwright and journey A3 [AC 1,2,3,4,5,6]
Open (non-blocking): override via saveRelationshipsUsing or a narrower hook, decided in implementation
<!-- SECTION:PLAN:END -->
