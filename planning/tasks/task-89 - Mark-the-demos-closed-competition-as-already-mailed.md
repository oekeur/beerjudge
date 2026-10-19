---
id: TASK-89
title: Mark the demo's closed competition as already mailed
status: Done
assignee:
  - '@agent'
created_date: '2026-10-09 13:16'
updated_date: '2026-10-19 14:38'
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
- [x] #1 DemoSeeder sets results_mailed_at on the closed demo competition; reopen and close on a fresh demo database sends no mail (test)
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-demo-closed-competition-mailed.md (Phase 1: Seed the closed demo competition as mailed)
Goal: Reopening and closing DemoSeeder's closed competition sends no results mail.
Phase 1 - Seed as mailed: set results_mailed_at on the closed demo competition in DemoSeeder; add a DemoSeederTest case that reopens and closes it with Notification::fake() and asserts nothing is sent (fails without the seeder line) [AC 1]
[RISK] none found
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: DemoSeeder seeds the closed demo competition (Autumn Harvest 2024) with results_mailed_at = now(); DemoSeederTest::test_reopening_and_closing_the_closed_demo_competition_sends_no_mail reopens and closes it under Notification::fake() and asserts nothing sent. Fails at the merge base (Probe 2). Pass A and Pass B CLEAN; Pass B confirmed the open and judging demo competitions still seed unmailed and mail once on first close. CI green, merged as eef85e9 (PR #74). As planned. Note: docs/user-journeys.md:243 still lists the old seeder gap inside a dated run log; left as history per the plan's out-of-scope line.
<!-- SECTION:FINAL_SUMMARY:END -->
