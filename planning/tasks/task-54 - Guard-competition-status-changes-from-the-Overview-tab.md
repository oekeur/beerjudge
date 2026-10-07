---
id: TASK-54
title: Guard competition status changes from the Overview tab
status: Done
assignee:
  - '@claude'
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 21:52'
labels:
  - dashboard-widgets
dependencies: []
priority: medium
type: bug
ordinal: 54000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The Status select on the Overview tab accepts any value, so an admin can move backwards or jump to Judging without BottleNumberAssignmentService::assign(). Header buttons only offer the forward step.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 On the competition edit page the Status field cannot be changed; on create it still defaults to Draft
- [x] #2 A closed competition shows a Reopen judging header action that moves it back to Judging, covered by a test
- [x] #3 Saving the edit form with a tampered status value does not change the status, covered by a test
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
See planning/2026-10-07-guard-competition-status-changes.md (single task: disable status field, force Draft on create, add reopenJudging header action, tests)
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decision (Oscar, 2026-10-07): Status is read-only on edit; add a Reopen judging header action on closed competitions. Judging -> Open is not offered.
<!-- SECTION:NOTES:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] No acceptance criteria; approach open: remove the Status field, allow only valid transitions, or run bottle assignment on a jump to Judging. — Decide the approach, then add ACs.
---
<!-- COMMENTS:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
The Status field is read-only on the competition form (disabled, so not dehydrated) and create always saves Draft. A Reopen judging header action on closed competitions moves the competition back to Judging without re-running bottle assignment or re-sending results (results_mailed_at stays set), then reloads the edit page so the header actions match the new status. Tests: tests/Feature/CompetitionStatusGuardTest.php (7). Gates: Pass A CLEAN; Pass B CONFIRMED stale header actions after reopen, fixed with the redirect. UI confirmed by Oscar 2026-10-07.
<!-- SECTION:FINAL_SUMMARY:END -->
