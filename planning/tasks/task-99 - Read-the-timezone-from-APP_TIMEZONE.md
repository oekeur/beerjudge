---
id: TASK-99
title: Read the timezone from APP_TIMEZONE
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:42'
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
- [x] #1 config/app.php reads env(APP_TIMEZONE) with UTC as default
- [x] #2 docs/env-reference.md and .env.example list APP_TIMEZONE
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 2: Generalize the app)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 2 - APP_TIMEZONE: config reads the env [AC 1]; env examples and env reference list it [AC 2]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: config/app.php reads env('APP_TIMEZONE', 'UTC'); tests/Feature/AppTimezoneConfigTest covers the default and the env value, and fails when the config is hardcoded again (checked). AC 2: .env.example, .env.docker.example and docs/env-reference.md list APP_TIMEZONE; the reference warns that timestamps are stored without an offset (no timestampTz columns in migrations), so the value must be set before the first data. As planned, plus that warning.
<!-- SECTION:FINAL_SUMMARY:END -->
