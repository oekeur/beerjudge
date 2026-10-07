---
id: TASK-49
title: Show the beer name in the variance widget
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 19:40'
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

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 3: Small fixes)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
JudgeVarianceWidget Beer column now shows '<style code> <style name>' via ->state() on the eager-loaded style (entries.name was dropped 2026-06-10). Gate retry: Pass B found a blank cell for an empty style code (Filament skips formatStateUsing on blank state); fixed by computing the cell with ->state(trim(code name)). Verified: tests/Feature/JudgeVarianceWidgetTest.php (style code shown, fails at merge base; empty-code style shows its name), Pass B probes (one styles query for 4 entries, no lazy load, escaping, competition scoping), full suite green.
<!-- SECTION:FINAL_SUMMARY:END -->
