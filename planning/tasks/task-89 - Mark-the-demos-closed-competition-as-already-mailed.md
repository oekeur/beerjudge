---
id: TASK-89
title: Mark the demo's closed competition as already mailed
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - email-on-publish
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: chore
ordinal: 86000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
DemoSeeder creates the closed 'Autumn Harvest 2024' competition without results_mailed_at, so reopening and closing it on a demo database emails its participants. Found by the TASK-67 Pass B gate, 2026-10-09.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 DemoSeeder sets results_mailed_at on the closed demo competition; reopen and close on a fresh demo database sends no mail (test)
<!-- AC:END -->
