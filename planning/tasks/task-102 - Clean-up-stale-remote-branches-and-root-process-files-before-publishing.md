---
id: TASK-102
title: Clean up stale remote branches and root process files before publishing
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:32'
labels:
  - public-launch
dependencies: []
priority: medium
type: chore
ordinal: 99000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit items 4 and 8. Remote has stale branches (claude/*, worktree-*, feat/ba-style-seeder). PLAN.md is a stale SRM plan. CLAUDE.md has /home/oscar paths and a stale Commands section. planning/ task-27 names the server path. Never push feat/backfill-historical-competitions (real member names).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Stale remote branches are deleted after Oscar confirms the list
- [ ] #2 PLAN.md is removed
- [ ] #3 CLAUDE.md has no home-directory path, no stale scaffolding text and no MySQL claim, and its PWA lines match TASK-95
- [ ] #4 planning/ contains no hostname, IP address or credential, and home and server paths in tracker files are redacted to <dev-checkout> / <server-checkout> (Oscar's OK 2026-10-12)
- [ ] #5 gitleaks detect over the full history reports 0 leaks, or each finding is shown to be a placeholder
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 4: Cleanup and release)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Done 2026-10-12: stale remote branches [AC 1]
Phase 4 - Cleanup: delete PLAN.md [AC 2]; CLAUDE.md cleanup [AC 3]; planning/ scan and tracker path redaction [AC 4]; gitleaks over history [AC 5]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-10-12: deleted the 4 stale remote branches on Oscar's request (feat/ba-style-seeder was already gone). Local backups on Oscar's machine: backup/claude/feature-audit-spec-update-1ckjqd (fbaf161), backup/claude/system-settings-dark-mode-8znyq9 (ff64a3f), backup/worktree-fix-docker-entrypoint (6c940dc), backup/worktree-we-have-just-redesigned-woolly-knuth (7f34826).
<!-- SECTION:NOTES:END -->
