---
id: TASK-66
title: Warn that reopening judging hides results from members
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
updated_date: '2026-10-08 13:14'
labels:
  - dashboard-widgets
  - results
dependencies: []
priority: low
type: enhancement
ordinal: 66000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Reopen judging (TASK-54) moves a closed competition back to Judging. ResultsController requires closed for non-admins, so members who already got the results email get 403 on their link until the competition is closed again. The confirmation text does not say so. Found by the TASK-54 adversarial gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The Reopen judging confirmation says members cannot see results until the competition is closed again, in English and Dutch
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 2.2)
<!-- SECTION:NOTES:END -->
