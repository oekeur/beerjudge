---
id: TASK-102
title: Clean up stale remote branches and root process files before publishing
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 14:03'
labels:
  - public-launch
dependencies: []
priority: medium
type: chore
ordinal: 99000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit items 4 and 8. Remote has stale branches (claude/*, worktree-*, feat/ba-style-seeder). PLAN.md is a stale SRM plan. CLAUDE.md has home-directory paths and a stale Commands section. planning/ task-27 names the server path. Never push feat/backfill-historical-competitions (real member names).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Stale remote branches are deleted after Oscar confirms the list
- [x] #2 PLAN.md is removed
- [x] #3 CLAUDE.md has no home-directory path, no stale scaffolding text and no MySQL claim, and its PWA lines match TASK-95
- [x] #4 planning/ contains no hostname, IP address or credential, and home and server paths in tracker files are redacted to <dev-checkout> / <server-checkout> (Oscar's OK 2026-10-12)
- [x] #5 gitleaks detect over the full history reports 0 leaks, or each finding is shown to be a placeholder
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

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1 (earlier): stale remote branches deleted, local backup/* refs kept. AC 2: PLAN.md removed. AC 3: CLAUDE.md worktree commands use relative paths, Tech stack says Postgres (production, CI) and SQLite (dev, tests) instead of MySQL, Commands section is current, PWA files describe the manifest route, neutral icons and CLUB_ICON_DIR (matches TASK-95, which merges first). AC 4: tracker home paths redacted to <dev-checkout>/<server-checkout> in task-27/29/74/78/81/102 (git grep -E '/home/[a-z]+' planning: no hits); the production hostname (features/security-hardening.md), the club mail address and the production password's $ hint (task-27) redacted as well; IP/email/credential greps show only placeholders. specs/judging.md manifest line updated to the route. AC 5: gitleaks v8.24.3 detect over 306 commits: 1 finding, the sk_live_abc123/AWS doc example placeholder in .claude/skills/laravel-best-practices/rules/config.md. Departure: redaction went beyond paths to the hostname, mail domain and password hint, under AC 4's no-hostname/no-credential rule.
<!-- SECTION:FINAL_SUMMARY:END -->
