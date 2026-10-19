---
id: TASK-108
title: 'Translate the results, score sheet and judge report pages'
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-13 14:45'
updated_date: '2026-10-19 14:41'
labels:
  - i18n
dependencies:
  - TASK-107
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 105000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
results/index, results/show, results/judge-report and the shared full-standings partial are almost entirely English: headings (Your judge report, Full Standings), table columns, score bands (Excellent, Very Good), Print, All results, the explanatory sentences, and dates formatted as 'October 19, 2024'. These are the pages the results email links to. Found 2026-10-13 by the nl/en render diff; deferred by Oscar: 'our group is mostly English, so it's fine for now'.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Every visible string on the three pages and the partial comes from the lang files, and dates use the member's locale
- [ ] #2 Tests render each page for an nl member and find the Dutch strings
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-translate-member-pages.md (Phase 2: Results, score sheet and judge report)
Goal: results/index, show, judge-report and the full-standings partial render from lang files with day-first Dutch dates; English output unchanged.
Phase 2 - Translate: move the plan's inventory (titles, headings, columns, Str::plural -> trans_choice, ordinals, judge-report sentences as single keys, results.section.* labels, band pills via judging.band.{key}.name, date format key) to results.* keys [AC 1]
Phase 2 - Tests: nl feature tests per page with presence and absence checks; existing English assertions in ResultsAccessTest and JudgeReportAccessTest still pass [AC 2]
Open (non-blocking): Oscar's review includes a print preview of one Dutch sheet (longer strings)
<!-- SECTION:PLAN:END -->
