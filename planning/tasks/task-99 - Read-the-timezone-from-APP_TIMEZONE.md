---
id: TASK-99
title: Read the timezone from APP_TIMEZONE
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:32'
labels:
  - public-launch
dependencies: []
priority: low
type: chore
ordinal: 96000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 13. config/app.php hardcodes timezone UTC.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 config/app.php reads env(APP_TIMEZONE) with UTC as default
- [ ] #2 docs/env-reference.md and .env.example list APP_TIMEZONE
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 2: Generalize the app)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 2 - APP_TIMEZONE: config reads the env [AC 1]; env examples and env reference list it [AC 2]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
