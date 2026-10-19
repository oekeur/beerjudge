---
id: TASK-111
title: Remove per-entry members and styles queries from the competition edit form
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-14 08:06'
updated_date: '2026-10-19 14:18'
labels:
  - sentry
dependencies: []
documentation:
  - planning/2026-10-13-fix-edit-competition-n1.md
priority: low
type: bug
ordinal: 108000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Sentry issue 152596637 (N+1 on EditCompetition save). Each entry row's member_id Select looks up its member record and the style_id options closure reruns its Style query per row, on every validation pass. Probe 2026-10-13, 6 entries: save runs 24 styles and 18 members queries; mount runs 12 styles queries.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 member_id Select uses ->options() from a once()-memoized query of all members (inactive included) ordered by name; style_id options come from a once()-memoized helper keyed on the style-id array
- [ ] #2 Feature test fixture: competition has styles attached and every member and entry exists before the first mount; 2-entry and 6-entry scenarios are separate test methods; Once::flush() runs before the measured mount and before the measured call('save')
- [ ] #3 On save: 0 queries containing 'from "members" where "members"."id" = ?' and exactly 1 query matching 'from "styles" where "id" in (' with 'order by "code"', in both scenarios
- [ ] #4 On mount: exactly 1 of that styles query in both scenarios
- [ ] #5 competition_style exists queries on save equal the entry count (Entry saving guard untouched)
- [ ] #6 CompetitionEntriesSortTest, CompetitionStatusGuardTest, CompetitionResultsEmailTest and the new test pass; phpstan and pint clean
- [ ] #7 Journey A3 (scripts/journeys/a3-manage-entries.mjs) passes: member picker opens on 'Select an option', filters on typed 'Member' and saves the picked option
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-13-fix-edit-competition-n1.md (Phase 1: Memoize the entry row Select options)
Goal: loading or saving the competition edit page runs a fixed number of members and styles queries, independent of the entry count.
Phase 1 - Memoize the entry row Select options: swap the member relationship Select for once()-memoized options, memoize the style options, pin query counts in a feature test, run journey A3 [AC 1,2,3,4,5,6,7]
Out of scope: Entry saving exists guard, Filament per-row sort_order updates (Oscar, 2026-10-13).
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
