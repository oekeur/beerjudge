---
id: TASK-101
title: Run the test suite against Postgres in CI
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:32'
labels:
  - public-launch
dependencies: []
priority: medium
type: chore
ordinal: 98000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 11. ci.yml tests on SQLite only while production runs Postgres; CLAUDE.md also claims MySQL support that nothing tests.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 ci.yml has a job with a postgres:16 service that sets DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD and an empty DB_URL, migrates, runs php artisan test, and is green on the PR
- [ ] #2 DemoSeeder's Style::first() fallback uses a deterministic order that picks a BJCP style, and BaStyleSeederTest passes on Postgres
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 2: Generalize the app)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 2 - Postgres CI: job with full DB env [AC 1]; DemoSeeder ordering fix [AC 2]
[RISK] Suite failures on Postgres - Resolved: red-team run 302/303, the one failure is AC 2
The MySQL claim moved to TASK-102 AC 3.
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
