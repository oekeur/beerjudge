---
id: TASK-38
title: Land the historical competition backfill importer
status: To Do
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 18:09'
labels:
  - backfill
dependencies: []
priority: medium
type: feature
ordinal: 38000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
BackfillImportCommand, its test and backfill/ data exist only on the local, unpushed branch feat/backfill-historical-competitions (489a17e, 720d7dc), plus a WIP stash commit c487d4a. Rebase on main, verify, open a PR.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Branch rebased on main and tests pass
- [ ] #2 Merged via PR
<!-- AC:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] [RISK] Description is wrong: 720d7dc is the stash index commit, so part of the work exists only in stash@{0}, uncommitted. 489a17e also commits the raw Google Forms .xlsx and names/beers mapping CSVs (probably member names), and the mappings need Oscar's input. — Apply the stash onto the branch and commit it; decide whether raw exports and personal data belong in the repo; Oscar completes the mappings before landing.
---
<!-- COMMENTS:END -->
