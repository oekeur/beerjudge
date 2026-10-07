---
id: TASK-27
title: Verify production env against the launch checklist
status: To Do
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 18:09'
labels:
  - launch-ops
dependencies: []
documentation:
  - planning/ops-launch-checklist.md
  - planning/features/security-hardening.md
priority: high
type: chore
ordinal: 27000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The repo-root .env.production is a local copy that drifted from the server's file (SameSite incident, 2026-10-07), so earlier 'verified' values say nothing about production. Check each item on the server.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 APP_ENV=production, APP_DEBUG=false on the server
- [ ] #2 DB_PASSWORD rotated away from the dev value
- [ ] #3 MAIL_FROM_ADDRESS is a real club sender, not hello@example.com
- [ ] #4 Redis binds to localhost or the internal network only, or REDIS_PASSWORD is set
- [ ] #5 Local .env.production copy matches the server or is deleted
<!-- AC:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] [RISK] Only Oscar has server access, so an agent cannot do or verify this, and TASK-29 is blocked on it. — Oscar does it, or pastes redacted server values so the ACs can be checked.
---
<!-- COMMENTS:END -->
