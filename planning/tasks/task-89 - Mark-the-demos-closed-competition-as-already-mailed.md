---
id: TASK-89
title: Mark the demo's closed competition as already mailed
status: Planned
assignee: []
created_date: '2026-10-09 13:16'
updated_date: '2026-10-14 14:31'
labels:
  - email-on-publish
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: chore
ordinal: 113000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
DemoSeeder creates the closed 'Autumn Harvest 2024' competition without results_mailed_at, so reopening and closing it on a demo database emails its participants. Found by the TASK-67 Pass B gate, 2026-10-09.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 DemoSeeder sets results_mailed_at on the closed demo competition; reopen and close on a fresh demo database sends no mail (test)
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-demo-closed-competition-mailed.md (Phase 1: Seed the closed demo competition as mailed)
Goal: Reopening and closing DemoSeeder's closed competition sends no results mail.
Phase 1 - Seed as mailed: set results_mailed_at on the closed demo competition in DemoSeeder; add a DemoSeederTest case that reopens and closes it with Notification::fake() and asserts nothing is sent (fails without the seeder line) [AC 1]
[RISK] none found
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
