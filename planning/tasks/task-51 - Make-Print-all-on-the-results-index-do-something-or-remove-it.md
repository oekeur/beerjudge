---
id: TASK-51
title: Make 'Print all' on the results index do something or remove it
status: Planned
assignee: []
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 19:11'
labels:
  - results
dependencies: []
priority: low
type: bug
ordinal: 51000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
results/index.blade.php:59 opens ?print=all, but no controller reads that parameter, so it reopens the same page.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 3: Small fixes)
<!-- SECTION:NOTES:END -->
