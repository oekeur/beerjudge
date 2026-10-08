---
id: TASK-62
title: 'Give login, magic-link request and password reset their own rate limiters'
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
updated_date: '2026-10-08 11:39'
labels:
  - security-hardening
  - auth
dependencies: []
priority: medium
type: bug
ordinal: 62000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Numeric throttle:N,M keys guests on sha1(domain|ip) with no route component (vendor ThrottleRequests.php:224-230), so POST /login (6,1), POST /magic-link (3,5) and password reset confirm (5,1) share one counter per IP. Hits on one route eat into the others; on shared venue wifi a few logins lock out magic-link requests for 5 minutes. TASK-39 moved only the token routes to a named limiter (magic-link-token in AppServiceProvider). Found by the TASK-39 adversarial gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Each throttled guest auth route uses a named limiter keyed by its own name plus IP, keeping its current numbers
- [ ] #2 A test shows hits on one route do not count toward another route's limit
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-10-08 walkthrough: login, magic-link request and reset from one IP in quick succession produced a 429 on a later login. Separate task filed for keying login by more than the IP.
<!-- SECTION:NOTES:END -->
