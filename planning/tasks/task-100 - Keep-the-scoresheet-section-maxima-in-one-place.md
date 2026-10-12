---
id: TASK-100
title: Keep the scoresheet section maxima in one place
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:44'
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
- [x] #1 Every section maximum in StoreScoresheetRequest (5 max: rules) and judging/show.blade.php (5 @for bounds, 5 aria max values, 5 header and 5 read-only labels, and the /50 totals as array_sum) reads Scoresheet::SECTION_MAX, and the rendered judging page is unchanged
- [x] #2 Existing scoresheet and judging tests pass unchanged
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 2: Generalize the app)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 2 - Scoresheet maxima: every maximum and total in the request and judging Blade reads Scoresheet::SECTION_MAX [AC 1]; tests unchanged [AC 2]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: StoreScoresheetRequest's five max: rules and every maximum in judging/show.blade.php (5 @for bounds, 5 aria max values, 5 header labels, 5 read-only labels, 3 /50 totals via array_sum) read Scoresheet::SECTION_MAX through a $sectionMax variable; the judging page rendered before and after the change differs only in CSRF tokens (diffed with a throwaway test, since deleted). AC 2: 72 scoresheet/judging/judge tests pass unchanged; pint and phpstan clean. Departure: the plan counted 15 sites; reading the view found 10 display labels and the totals as well, so AC 1 and the plan were widened to cover them.
<!-- SECTION:FINAL_SUMMARY:END -->
