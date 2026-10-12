---
id: TASK-99
title: Read the timezone from APP_TIMEZONE
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
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
