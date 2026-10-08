---
id: TASK-71
title: Make the competition Dashboard and Variance widgets poll
status: Backlog
assignee: []
created_date: '2026-10-08 11:39'
labels:
  - dashboard-widgets
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 71000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
ResultsSummaryWidget and JudgeVarianceWidget set $pollingInterval = '5s', but on the competition edit page the DOM has no wire:poll attribute and no Livewire request is sent in 16 s idle. After saving two judges the Judges tile stays 0 until a reload. Regresses TASK-14 (polled widgets). Seen in 3 of 3 walkthrough runs, 2026-10-08 (docs/user-journeys.md, finding 2). Cause not yet established: the widgets are embedded with Livewire::make() in the form schema and render custom views.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 With the Dashboard tab open, a new scoresheet or judge assignment shows up within one polling interval without a reload
- [ ] #2 Same for the Results & Variance tab
- [ ] #3 A test covers that both widgets render a polling directive
<!-- AC:END -->
