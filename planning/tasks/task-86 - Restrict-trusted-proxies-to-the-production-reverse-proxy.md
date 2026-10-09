---
id: TASK-86
title: Restrict trusted proxies to the production reverse proxy
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - security-hardening
  - launch-ops
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: chore
ordinal: 83000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
bootstrap/app.php trusts all proxies ('*'), so $request->ip() comes from X-Forwarded-For. A client that reaches the app directly (not through the reverse proxy) can rotate that header and escape every IP-keyed rate limit; a probe on 2026-10-09 sent 40 wrong-password logins with no 429. Behind a single nginx-proxy that appends the real client IP this is likely not exploitable (hypothesis, not checked against production).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Check how production reaches the app (nginx-proxy only, or also directly) and record the result
- [ ] #2 trustProxies lists the reverse proxy's address or network instead of '*', or the task records why '*' is safe in this deployment
<!-- AC:END -->
