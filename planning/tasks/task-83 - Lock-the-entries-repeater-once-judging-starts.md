---
id: TASK-83
title: Lock the entries repeater once judging starts
status: Planned
assignee: []
created_date: '2026-10-09 13:16'
updated_date: '2026-10-14 14:35'
labels:
  - v1
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 109000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The entries Repeater on the competition edit page stays editable during Judging and after Closed. Entries added there get no bottle number (bottle numbers are assigned on Move to Judging), and Sort by ABV plus Save can change the tasting order mid-judging. Found in the 2026-10-09 gate rounds (docs/user-journeys.md, 'Found while fixing' f).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 From Judging on, entries cannot be added, removed or reordered in the admin (read-only repeater or equivalent), covered by tests
- [ ] #2 Reopen judging keeps the lock
- [ ] #3 The entries repeater is documented as read-only from Judging on in planning/specs/entries.md and planning/specs/competitions.md
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-lock-entries-repeater-judging.md
Goal: from Judging on (incl. Closed and reopened Judging) the admin entries repeater and Sort by ABV are locked, enforced server-side.
Phase 1 - Lock repeater: Competition::entriesAreLocked(), disabled repeater + hidden Sort by ABV + hint (competition.entry.locked in en+nl), CompetitionEntriesLockTest covering add/remove/edit/reorder tamper, in-page lock after Move to Judging, and Reopen [AC 1,2]
Phase 2 - Specs: update planning/specs/entries.md and competitions.md to describe the lock [AC 3]
[RISK] disabled relationship Repeater skipping saves - Resolved (red-team probe, tests pass with lock, fail without)
Open (non-blocking): field edits locked too (planner's call, Oscar may overrule)
<!-- SECTION:PLAN:END -->
