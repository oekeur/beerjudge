---
id: TASK-50
title: Give member-registered entries a sort_order
status: Planned
assignee: []
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 19:11'
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
