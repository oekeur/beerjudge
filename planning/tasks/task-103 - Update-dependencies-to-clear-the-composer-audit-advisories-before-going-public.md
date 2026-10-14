---
id: TASK-103
title: Update dependencies to clear the composer audit advisories before going public
status: Done
assignee: []
created_date: '2026-10-12 18:36'
updated_date: '2026-10-14 13:46'
labels:
  - public-launch
  - security-hardening
dependencies: []
priority: high
type: chore
ordinal: 100000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
composer audit on 2026-10-12 (main 5fa5ee2) reports 31 advisories in 8 packages: league/commonmark 12 (high/medium, affected <=2.10.1), guzzlehttp/guzzle 9 (high/medium, <7.15.2), filament/filament 4 (high/medium/low, installed 4.11.7, fixed in 4.13.2), guzzlehttp/psr7 2 (medium, <2.12.3), ueberdosis/tiptap-php 1 (high, <2.1.1), laravel/framework 1 (low, <13.30.0), league/flysystem 1 (low, <=3.35.2), livewire/livewire 1 (medium, v3 <=3.8.2). The repo is about to become public, which makes these visible to anyone. Also run npm audit for the frontend.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 composer audit reports no advisories, or each remaining one is listed with why it cannot be fixed yet
- [x] #2 npm audit results are checked and high/critical ones fixed or listed with a reason
- [x] #3 CI is green on all jobs (static-analysis, tests, tests-postgres, browser-tests)
- [x] #4 The judging screen and the Filament admin are checked in a browser after the Filament and Livewire bumps
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Inline (Oscar, 2026-10-12), no plan file.
1. composer update -W limited to the 8 flagged packages, so no unrelated majors land [AC 1]
2. npm audit fix; list what stays and why [AC 2]
3. phpstan, full suite on SQLite and Postgres, CI incl. browser-tests [AC 3]
4. Oscar checks judging screen and Filament admin in a browser [AC 4]
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
A full composer update also bumped phpstan 2.2.2 -> 2.3.1, after which phpstan exits 1 with no output on analyse and list (--version and --help work; reproduced with -c /dev/null). Not needed for any advisory, so the update was limited to the flagged packages. npm: 11 of 14 fixed by npm audit fix; esbuild/vite/vitepress remain, all from vitepress 1.6.4 (latest stable; 2.0 is alpha), used only by the docs dev server and not shipped in the image.

Review: PR #55, CI green (all 5 jobs). Oscar checks the judging screen and Filament admin in the worktree: cd .claude/worktrees/improve+dependency-updates && composer run dev:lite

2026-10-13: user-journey walkthrough (scripts/journeys/run-all.sh) on the update: 100 of 100 steps after updating the scripts for Filament 4.15's alertdialog/combobox roles (first run 38 failures, all selector or cascade). Logged in docs/user-journeys.md.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Landed as 0857386 (squash of PR #55), rebased onto b67b8f2.
AC 1: composer update limited to the 8 flagged packages (Filament 4.11.7 -> 4.15.1, Livewire 3.8.1 -> 3.8.10, Laravel 13.15 -> 13.35, commonmark, guzzle, psr7, tiptap-php, flysystem). composer audit after rebase: no advisories.
AC 2: npm audit fix cleared 11 of 14; the 3 left (esbuild/vite via vitepress 1.6.4, 1 high) are docs dev-server only and not shipped, see notes.
AC 3: after rebase, phpstan 0 errors, PHPUnit 379/379, Playwright 48 passed/2 skipped locally; CI green on static-analysis, tests, tests-postgres, browser-tests and image. The rebase pulled in the judges spec from PR #70, which still used the pre-4.15 'Select an option' button; fixed to the combobox role (5cf27e9) before merge.
AC 4: journey walkthrough on the update, 100 of 100 steps (docs/user-journeys.md, 2026-10-13); journey scripts updated for Filament 4.15 alertdialog/combobox roles.
Departure from plan: the extra browser-spec fix after rebase; otherwise as planned.
<!-- SECTION:FINAL_SUMMARY:END -->
