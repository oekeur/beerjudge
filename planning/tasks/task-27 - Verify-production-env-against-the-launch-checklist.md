---
id: TASK-27
title: Verify production env against the launch checklist
status: Done
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 20:16'
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
- [x] #1 APP_ENV=production, APP_DEBUG=false on the server
- [x] #2 DB_PASSWORD rotated away from the dev value
- [x] #3 MAIL_FROM_ADDRESS is a real club sender, not hello@example.com
- [x] #4 Redis binds to localhost or the internal network only, or REDIS_PASSWORD is set
- [x] #5 Local .env.production copy matches the server or is deleted
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 1: Production env verified)

Probes 2026-10-07 over ssh (read-only), server /home/oscar/git/beerjudge:
- AC1: file APP_ENV=production, APP_DEBUG=false; running container artisan config:show app.env=production, app.debug=false. PASS.
- AC2: app container DB_PASSWORD != secret (sha256 compare, value never printed) and artisan db:show connects to Postgres 16.15, so the role password is rotated. postgres container env POSTGRES_PASSWORD=secret is only the volume-init value. PASS. Gotcha: the file's DB_PASSWORD contains a $, which Compose v2.32 interpolates in env_file values, so the app gets a different string than the file shows; any rotation must avoid $ or write $$.
- AC3: file MAIL_FROM_ADDRESS=beerjudge@foebar.com, MAIL_MAILER=smtp; running config mail.from.address=beerjudge@foebar.com. PASS.
- AC4: redis port 6379 not published (Ports {6379/tcp:null}); only network beerjudge_default with app and postgres. bind '* -::*', protected-mode no, REDIS_PASSWORD empty, but unreachable outside that network. PASS.
- Queue worker: 1 queue:work process in beerjudge-app-1.
- AC5: repo-root .env.production differs from server (local MAIL_MAILER=log, MAIL_FROM hello@example.com). Waiting on Oscar: delete it or sync it.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Production env verified over ssh 2026-10-07 (read-only probes, evidence in notes): APP_ENV=production, APP_DEBUG=false; DB password rotated (app connects, not 'secret'); MAIL_FROM_ADDRESS=beerjudge@foebar.com over smtp; Redis unpublished and only on the beerjudge_default network; queue worker running. Oscar deleted the drifted repo-root .env.production copy; the server file is the only source. Gotcha kept in notes: the server DB_PASSWORD contains a $ that Compose interpolates.
<!-- SECTION:FINAL_SUMMARY:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] [RISK] Only Oscar has server access, so an agent cannot do or verify this, and TASK-29 is blocked on it. — Oscar does it, or pastes redacted server values so the ACs can be checked.
---
<!-- COMMENTS:END -->
