---
id: TASK-63
title: Rebuild header actions after the forward lifecycle transitions
status: Done
assignee: []
created_date: '2026-10-07 21:40'
updated_date: '2026-10-08 15:16'
labels:
  - dashboard-widgets
dependencies: []
priority: low
type: bug
ordinal: 63000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
After Move to Open, Move to Judging or Move to Closed on the competition edit page, the header actions stay those of the previous status until a reload, because getHeaderActions() resolves from the record before the action runs and refreshFormData() only refreshes the form. No wrong data gets through (actions are rebuilt on the next request). TASK-54 fixed this for Reopen judging with a redirect to the edit page. Found by the TASK-54 adversarial gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 After each lifecycle action the page shows the header actions for the new status without a manual reload, covered by a test
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Seen again in the 2026-10-08 browser walkthrough: after Move to Open the header still offered Move to Open until a reload (3 of 3 runs). docs/user-journeys.md, finding 3.

Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 2.2)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Move to Open/Judging/Closed rebuild the page's header actions in place (reset $cachedHeaderActions + cacheInteractsWithHeaderActions()) with no form save and no redirect, so the next status's buttons show at once and unsaved edits stay on screen. Design revised with Oscar mid-run: a save-first version deleted entries registered and judges joined after the page loaded. Verified: CompetitionStatusGuardTest (header rebuild in one component fails at base; unsaved edits and late entries/judges/entry edits survive every forward transition); full suite passed; journey A2 asserts the header without reload; Pass A and Pass B CLEAN after three rounds. Plan task 2.2.
<!-- SECTION:FINAL_SUMMARY:END -->
