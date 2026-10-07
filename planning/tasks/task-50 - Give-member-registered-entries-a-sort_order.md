---
id: TASK-50
title: Give member-registered entries a sort_order
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 19:38'
labels:
  - abv-tasting-order
dependencies: []
priority: medium
type: bug
ordinal: 50000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
EntryController::store leaves sort_order null. orderBy('sort_order') puts nulls last on Postgres, first on SQLite, so tasting order for those entries is undefined until an admin reorders or saves the Entries tab.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 3: Small fixes)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Entry::booted() creating hook sets sort_order to the competition's max+1 when NULL; migration 2026_10_07_193322_backfill_null_entry_sort_order numbers existing NULL rows per competition in id order after the max via Entry::whereKey()->update (no model events, no dialect SQL); orderBy('id') tiebreaker added in JudgingController (show, progress) and results-summary.blade.php. Verified: tests/Feature/EntrySortOrderTest.php (POSTed entries get 8, 9 after max 7, fails at merge base; backfill incl. a detached-style row; explicit value kept), full suite green; Pass B probed the Filament Repeater path (Repeater sets sort_order itself, hook stays idle, [1,2,3]) and Postgres max() returning int. Known accepted edge: concurrent creates can tie, broken deterministically by id.
<!-- SECTION:FINAL_SUMMARY:END -->
