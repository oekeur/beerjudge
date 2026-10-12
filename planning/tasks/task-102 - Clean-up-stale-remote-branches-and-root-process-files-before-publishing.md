---
id: TASK-102
title: Clean up stale remote branches and root process files before publishing
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
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
- [ ] #1 Stale remote branches are deleted after Oscar confirms the list
- [ ] #2 PLAN.md is removed
- [ ] #3 CLAUDE.md has no personal absolute paths or stale sections
- [ ] #4 planning/ has been read through for server details, and gitleaks detect reports no findings
<!-- AC:END -->
