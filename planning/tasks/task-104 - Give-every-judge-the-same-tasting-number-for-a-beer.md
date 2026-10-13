---
id: TASK-104
title: Give every judge the same tasting number for a beer
status: Done
assignee: []
created_date: '2026-10-13 12:28'
updated_date: '2026-10-13 12:28'
labels:
  - abv-tasting-order
dependencies: []
priority: high
type: bug
ordinal: 101000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The judging screen numbered entries after removing the judge's own, so entrants saw later beers one number lower than other judges. Found in the 2026-10-13 pre-launch walkthrough (docs/user-journeys.md).
<!-- SECTION:DESCRIPTION:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Numbers come from the full tasting order; the judge's own entries show as locked 'Your entry' rows and stay out of My ranking. Commit 5a65fe3 (PR #56).
<!-- SECTION:FINAL_SUMMARY:END -->
