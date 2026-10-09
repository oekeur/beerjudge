---
id: TASK-85
title: Rate-limit the password reset email request
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - security-hardening
  - auth
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 82000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
POST /password/reset (send reset link) has no throttle, so anyone can trigger unlimited reset emails to a member's address (mail flooding, mail-provider reputation). The other guest auth routes got named limiters in TASK-62/73. Found by the TASK-62/73 Pass B gate, 2026-10-09.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 POST /password/reset uses a named limiter keyed by its name plus IP (and email), covered by a test like RateLimitingTest
- [ ] #2 The response stays identical whether or not the address exists
<!-- AC:END -->
