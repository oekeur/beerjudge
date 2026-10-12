---
id: TASK-103
title: Update dependencies to clear the composer audit advisories before going public
status: To Do
assignee: []
created_date: '2026-10-12 18:36'
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
- [ ] #1 composer audit reports no advisories, or each remaining one is listed with why it cannot be fixed yet
- [ ] #2 npm audit results are checked and high/critical ones fixed or listed with a reason
- [ ] #3 CI is green on all jobs (static-analysis, tests, tests-postgres, browser-tests)
- [ ] #4 The judging screen and the Filament admin are checked in a browser after the Filament and Livewire bumps
<!-- AC:END -->
