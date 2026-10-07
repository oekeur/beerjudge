---
id: TASK-49
title: Show the beer name in the variance widget
status: To Do
assignee: []
created_date: '2026-10-07 15:10'
labels:
  - judge-variance
dependencies: []
priority: low
type: bug
ordinal: 49000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
JudgeVarianceWidget.php:51 renders TextColumn::make('name'), but entries has no name column (dropped 2026-06-10), so the Beer column is always empty.
<!-- SECTION:DESCRIPTION:END -->
