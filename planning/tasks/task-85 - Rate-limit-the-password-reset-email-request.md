---
id: TASK-85
title: Rate-limit the password reset email request
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-09 13:16'
updated_date: '2026-10-19 14:18'
labels:
  - security-hardening
  - auth
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 111000
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

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-throttle-password-reset-request.md
Phase 1: password-reset-request named limiter (3 per 5 min per email+IP, 45 per IP) on POST /password/reset, tests in RateLimitingTest proving 429 and identical responses for member and unknown address, docs update [AC 1,2]
<!-- SECTION:PLAN:END -->
