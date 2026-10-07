---
id: TASK-54
title: Guard competition status changes from the Overview tab
status: Backlog
assignee: []
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 18:09'
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

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] No acceptance criteria; approach open: remove the Status field, allow only valid transitions, or run bottle assignment on a jump to Judging. — Decide the approach, then add ACs.
---
<!-- COMMENTS:END -->
