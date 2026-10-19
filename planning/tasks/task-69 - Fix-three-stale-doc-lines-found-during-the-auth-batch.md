---
id: TASK-69
title: Fix three stale doc lines found during the auth batch
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-07 21:40'
updated_date: '2026-10-19 14:18'
labels:
  - docs
dependencies: []
priority: low
type: docs
ordinal: 108000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
docs/admin/filament-overview-explanation.md:64 says the Authenticate middleware enforces is_admin (it is Member::canAccessPanel()). docs/competition/ranking-service-explanation.md:5 still mentions a batch-print view. planning/specs/authentication.md:42 justifies SESSION_SAME_SITE=lax by saying a strict cookie leaves the token already spent; since TASK-39 the GET no longer consumes the token. Found by the TASK-55, 61 and 39 gates, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The three named lines, and the other doc lines repeating the same claims (env-reference, docker-howto, active-member-middleware-reference), match the code; the rg check in the plan prints nothing
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-fix-stale-doc-lines.md (Phase 1: Correct the stale lines)
Goal: make the three stale doc lines and their same-claim copies match the code.
Phase 1 - Correct the stale lines: Authenticate/canAccessPanel/RequireActiveMember wording, drop batch-print, rewrite SESSION_SAME_SITE paragraph with no claim about strict, fix env-reference, docker-howto, active-member-middleware-reference; docs/private edit is local only; commit straight to main [AC 1]
[RISK] none. Non-blocking unknown: whether strict now works is untested; edits avoid claiming either way.
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
