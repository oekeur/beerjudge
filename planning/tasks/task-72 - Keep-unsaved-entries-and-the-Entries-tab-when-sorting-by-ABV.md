---
id: TASK-72
title: Keep unsaved entries and the Entries tab when sorting by ABV
status: Done
assignee: []
created_date: '2026-10-08 11:39'
updated_date: '2026-10-08 15:28'
labels:
  - abv-tasting-order
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 72000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The sortByAbv action in CompetitionForm.php sorts saved entries and then redirects to the edit URL. The redirect lands on the Overview tab and discards form state, so a repeater row that was added but not saved is lost without warning (1 of 1 probes, 2026-10-08; docs/user-journeys.md, finding 4).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Sort by ABV either saves pending repeater changes first or refuses with a message while there are unsaved changes
- [x] #2 After sorting the admin stays on the Entries tab and sees the new order
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 2.3)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Sort by ABV reorders the entries repeater rows in the form (stable, empty ABV last, unsaved rows included) without a database write or redirect; Save persists sort_order via the repeater's order column, and the admin stays on the Entries tab. Design revised with Oscar mid-run (save-then-sort could delete late registrations). Repeater fields and the action got translated labels. Verified: CompetitionEntriesSortTest (4 tests, all fail at base), full suite and phpstan clean, journey A3 probe (unsaved 3.3 row first, Entries tab active, sort_order 1 after Save), Pass A and Pass B CLEAN. Plan task 2.3.
<!-- SECTION:FINAL_SUMMARY:END -->
