---
id: TASK-38
title: Land the historical competition backfill importer
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 14:39'
updated_date: '2026-10-19 14:49'
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
- [x] #1 The importer is rebuilt on a branch from current main and the full test suite, PHPStan and Pint pass (rebuilt, not rebased: the old branch carries unrelated data and stash content)
- [x] #2 The branch is merged to main via a PR with all CI jobs green
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-land-backfill-importer.md
Goal: backfill:import (command, test, extract script) is on main via PR and works against current main models.
Phase 1 - Port onto main: new branch from main with only the command, test and extract script; data files kept out of git under gitignored backfill/data/; forceCreate closed + results_mailed_at (no mail), bottle numbers, float-safe total check, re-run guard, same-brewer-same-style error; end-to-end scratch run on real JSON [AC 1]
Phase 2 - Gate, PR, merge: pint, phpstan, full tests, PR, 3 CI jobs green, squash-merge [AC 1,2]
[RISK] none open
Open (non-blocking): data in git or not (default: not), Oscar fills mapping CSVs (fix 2015-era codes 7C/8C/27A) before the production run, drop stash and old branch after merge
<!-- SECTION:PLAN:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] [RISK] Description is wrong: 720d7dc is the stash index commit, so part of the work exists only in stash@{0}, uncommitted. 489a17e also commits the raw Google Forms .xlsx and names/beers mapping CSVs (probably member names), and the mappings need Oscar's input. — Apply the stash onto the branch and commit it; decide whether raw exports and personal data belong in the repo; Oscar completes the mappings before landing.
---
<!-- COMMENTS:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: importer rebuilt on a branch cut from main (feat/backfill-importer, not the old branch): BackfillImportCommand, extract_responses.py, /backfill/data/ gitignored, 22 tests on synthetic fixtures; pint, phpstan and full suite (409) pass, also on Postgres for the backfill tests. AC 2: PR #77 merged as 088e864 with all four CI jobs green. Gate history: Pass B round 1 confirmed four defects (own-entry scores imported, section scores unvalidated, fractional scores crashing Postgres, conflicting duplicate mapping rows) plus raw crashes on duplicate judge/beer pairs, unknown judge ids and BOM/blank-line CSVs; all fixed in one retry, round-1 probes now pass, Pass B round 2 CLEAN. The real exports hold only whole-number section scores (jq check), so the stricter rules do not block them. Departures: the plan's Phase 2 'gate, PR, merge' was run by the orchestrator. Open notes for Oscar (fail safe, nothing written): a CSV row with trailing empty cells dropped gives a column-count error; beers_to_map rows no record references, malformed JSON and ';'-delimited CSVs abort with a raw error rather than a readable one; a co-brewer judging a co-brew passes the own-entry check (one member_id per entry); submitted_at stores the naive Forms local time. Not done (out of scope): filling the names/beers mapping CSVs, running against production. Personal data copied into the worktree's backfill/data/ is removed with the worktree; stash@{0} and the old branch are untouched.
<!-- SECTION:FINAL_SUMMARY:END -->
