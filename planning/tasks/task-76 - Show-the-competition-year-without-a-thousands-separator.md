---
id: TASK-76
title: Show the competition year without a thousands separator
status: Done
assignee: []
created_date: '2026-10-08 11:39'
updated_date: '2026-10-09 11:58'
labels:
  - v1
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 76000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The Year column in the Filament competition list renders 2024 as '2,024' (2026-10-08 walkthrough, docs/user-journeys.md, finding 8).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 The competition list shows the year as 2024
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 4.1)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Competition list year column no longer uses ->numeric() (2024, not 2,024); sorting stays numeric in the database. Verified: AdminListPolishTest fails at base and passes; full suite; Pass A and Pass B CLEAN. Plan task 4.1.
<!-- SECTION:FINAL_SUMMARY:END -->
