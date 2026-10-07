---
id: TASK-51
title: Make 'Print all' on the results index do something or remove it
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 19:36'
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

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Removed the Print all button (and its empty wrapper div) from resources/views/results/index.blade.php; ?print=all was read by no controller. Oscar chose removal over a print-all view (2026-10-07). Verified: ResultsAccessTest admin index test asserts assertDontSee('Print all') and fails at the merge base (Pass A probe), grep for print=all in resources/app is empty, full suite passed. Commit: fix(results): remove non-functional Print all button.
<!-- SECTION:FINAL_SUMMARY:END -->
