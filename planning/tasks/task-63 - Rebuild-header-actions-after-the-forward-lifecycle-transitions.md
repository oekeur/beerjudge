---
id: TASK-63
title: Rebuild header actions after the forward lifecycle transitions
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
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
- [ ] #1 After each lifecycle action the page shows the header actions for the new status without a manual reload, covered by a test
<!-- AC:END -->
