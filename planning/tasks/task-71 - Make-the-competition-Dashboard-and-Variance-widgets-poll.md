---
id: TASK-71
title: Make the competition Dashboard and Variance widgets poll
status: Done
assignee: []
created_date: '2026-10-08 11:39'
updated_date: '2026-10-08 14:12'
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
- [x] #1 With the Dashboard tab open, a new scoresheet or judge assignment shows up within one polling interval without a reload
- [x] #2 Same for the Results & Variance tab
- [x] #3 A test covers that both widgets render a polling directive
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 2.1)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Summary widget polls via wire:poll.5s on its root div (base Widget has no polling) and stops polling with a translated empty state if the competition is deleted; variance table polls via ->poll('5s'); dead $pollingInterval removed; summary tiles, headers, links and the variance 'pts' suffix translated (en + nl). Verified: ResultsSummaryWidgetTest/JudgeVarianceWidgetTest incl. a real-request nl test and a deleted-competition test; full suite passed; journey A4 step 6 'judges tile after poll: 2 Judges'; Pass A (after retry) and Pass B (after retry for the null dereference) CLEAN. Plan task 2.1.
<!-- SECTION:FINAL_SUMMARY:END -->
