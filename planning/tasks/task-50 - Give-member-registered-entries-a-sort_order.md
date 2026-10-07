---
id: TASK-50
title: Give member-registered entries a sort_order
status: To Do
assignee: []
created_date: '2026-10-07 15:10'
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
