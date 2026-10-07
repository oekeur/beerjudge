---
id: TASK-45
title: Land or close the VitePress documentation site
status: In Progress
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 14:55'
labels:
  - docs-site
dependencies: []
priority: low
type: docs
ordinal: 45000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
4239163 lives only on docs/vitepress (PR #6), not on main.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 PR #6 merged with a passing build, or closed with the reason recorded
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
1. doc-drift pass over docs/ on the docs/vitepress branch against current main
2. fix false claims on the branch
3. merge PR #6 once CI passes
<!-- SECTION:PLAN:END -->
