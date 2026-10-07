---
id: TASK-36
title: Make the variance threshold configurable and let admins mark flags handled
status: Backlog
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 18:09'
labels:
  - judge-variance
dependencies: []
documentation:
  - planning/requirements/homebrew-competition-enhancements-prd.md
priority: low
type: enhancement
ordinal: 36000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The 7-point threshold is hardcoded in JudgeVarianceWidget.php:59,63. The PRD's optional reviewed_at column does not exist.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] Unclear whether the threshold is global config or per competition. — Decide where it lives.
---
<!-- COMMENTS:END -->
