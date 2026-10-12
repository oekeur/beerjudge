---
id: TASK-100
title: Keep the scoresheet section maxima in one place
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:32'
labels:
  - public-launch
dependencies: []
priority: low
type: chore
ordinal: 97000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 14. Limits 12/3/20/5/10 are repeated in Scoresheet, StoreScoresheetRequest and judging/show.blade.php. Single source only; configurable sheet layouts are out of scope until mead or cider (TASK-37) is planned.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 All 15 section-maximum sites (5 max: rules in StoreScoresheetRequest, 5 @for bounds and 5 aria max values in judging/show.blade.php) read Scoresheet::SECTION_MAX
- [ ] #2 Existing scoresheet and judging tests pass unchanged
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 2: Generalize the app)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 2 - Scoresheet maxima: the 15 sites read Scoresheet::SECTION_MAX [AC 1]; tests unchanged [AC 2]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
