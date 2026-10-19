---
id: TASK-69
title: Fix three stale doc lines found during the auth batch
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 21:40'
updated_date: '2026-10-19 14:21'
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
- [x] #1 The three named lines, and the other doc lines repeating the same claims (env-reference, docker-howto, active-member-middleware-reference), match the code; the rg check in the plan prints nothing
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-fix-stale-doc-lines.md (Phase 1: Correct the stale lines)
Goal: make the three stale doc lines and their same-claim copies match the code.
Phase 1 - Correct the stale lines: Authenticate/canAccessPanel/RequireActiveMember wording, drop batch-print, rewrite SESSION_SAME_SITE paragraph with no claim about strict, fix env-reference, docker-howto, active-member-middleware-reference; docs/private edit is local only; commit straight to main [AC 1]
[RISK] none. Non-blocking unknown: whether strict now works is untested; edits avoid claiming either way.
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: six lines reworded to match the code (commit 6a9f2bd): filament-overview-explanation and active-member-middleware-reference now say the panel runs RequireActiveMember (AdminPanelProvider.php:58-62) and canAccessPanel returns is_admin (Member.php:79-82); authentication spec, env-reference and docker-howto say lax is the default (config/session.php:202), GET /magic-link renders a confirm page and POST consumes (routes/web.php:41-42), with no claim about strict; ranking-service-explanation drops the batch-print view. Verified by Pass A (each reworded claim read against the cited code) and the done-check rg (exit 1 on the branch, hits on main). Departure: the plan's rg pattern 'still reach' also matched an unrelated sentence in docs/judging/explanation-offline-sync.md:35; narrowed to 'still reach (the panel|.?/admin)' in the plan. Not done: the gitignored docs/private/deploy-notes.md:201 still says strict breaks magic links; it is local-only and outside git.
<!-- SECTION:FINAL_SUMMARY:END -->
