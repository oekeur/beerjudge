---
id: TASK-85
title: Rate-limit the password reset email request
status: Done
assignee:
  - '@agent'
created_date: '2026-10-09 13:16'
updated_date: '2026-10-19 14:38'
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
- [x] #1 POST /password/reset uses a named limiter keyed by its name plus IP (and email), covered by a test like RateLimitingTest
- [x] #2 The response stays identical whether or not the address exists
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-throttle-password-reset-request.md
Phase 1: password-reset-request named limiter (3 per 5 min per email+IP, 45 per IP) on POST /password/reset, tests in RateLimitingTest proving 429 and identical responses for member and unknown address, docs update [AC 1,2]
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: new password-reset-request limiter in AppServiceProvider (3 per 5 min keyed name|email|IP, 45 per 5 min per IP) on POST /password/reset; RateLimitingTest covers the per-email limit (case-insensitive), the per-IP limit and separate counters. AC 2: identical status, Location, flash and 429 body for a member and an unknown address, tested. Probe 2: 3 of 4 new tests fail at base (the 4th asserts isolation). Pass A and Pass B CLEAN; Pass B also cleared enumeration through the broker's 60s throttle, whitespace/case bypass, malformed input (no 500) and the Dutch 429 page. CI green, merged as b37acef (PR #75). As planned. Notes from Pass B, not fixed (pre-existing): reset mail is sent synchronously, so a request for an existing member is likely slower than for an unknown one (timing side channel; queueing ResetPassword would close it); the trustProxies('*') X-Forwarded-For bypass already listed in docs/user-journeys.md also applies to this limiter.
<!-- SECTION:FINAL_SUMMARY:END -->
