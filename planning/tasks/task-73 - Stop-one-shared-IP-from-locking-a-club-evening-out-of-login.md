---
id: TASK-73
title: Stop one shared IP from locking a club evening out of login
status: Done
assignee:
  - '@agent'
created_date: '2026-10-08 11:39'
updated_date: '2026-10-08 13:49'
labels:
  - auth
  - security-hardening
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 73000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
POST /login uses throttle:6,1, which keys guests by IP alone. In the walkthrough (2026-10-08) the seventh login from one IP within a minute got a 429 page. At a club evening members share one Wi-Fi connection and so one public IP; ten members logging in at the start would lock each other out (expected, not observed in production). Related: TASK-62 separates the counters per route but keeps the per-IP key and current numbers. docs/user-journeys.md, finding 5.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 The login limiter is keyed so that different members on one IP do not share an attempt budget (for example email plus IP), while brute force on one account stays limited
- [x] #2 A test logs in ten different members from one IP within a minute without a 429
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 1.1)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Login limiter keyed 6/min per lowercased email+IP plus 30/min per IP; non-string email falls back to IP. Verified: tests prove ten members log in from one IP within a minute (assertAuthenticatedAs), 7th failed attempt per email gets 429, 31st per IP gets 429, email[]=x gives 302; full suite 252 passed; Pass A and Pass B CLEAN. Pre-existing, not changed: trustProxies('*') lets a client rotating X-Forwarded-For escape IP-keyed limits. Plan task 1.1.
<!-- SECTION:FINAL_SUMMARY:END -->
