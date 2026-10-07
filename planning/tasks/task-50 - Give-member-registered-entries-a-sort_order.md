---
id: TASK-50
title: Give member-registered entries a sort_order
status: To Do
assignee: []
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 18:09'
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

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] No acceptance criteria; the value for new entries is undecided and existing null sort_order rows need a backfill. — Add ACs: new entries get max+1, a migration fills existing nulls, a test covers it.
---
<!-- COMMENTS:END -->
