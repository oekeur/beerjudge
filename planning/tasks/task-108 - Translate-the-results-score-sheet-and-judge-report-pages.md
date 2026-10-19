---
id: TASK-108
title: 'Translate the results, score sheet and judge report pages'
status: Review
assignee:
  - '@agent'
created_date: '2026-10-13 14:45'
updated_date: '2026-10-19 15:04'
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
- [x] #1 Every visible string on the three pages and the partial comes from the lang files, and dates use the member's locale
- [x] #2 Tests render each page for an nl member and find the Dutch strings
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-translate-member-pages.md (Phase 2: Results, score sheet and judge report)
Goal: results/index, show, judge-report and the full-standings partial render from lang files with day-first Dutch dates; English output unchanged.
Phase 2 - Translate: move the plan's inventory (titles, headings, columns, Str::plural -> trans_choice, ordinals, judge-report sentences as single keys, results.section.* labels, band pills via judging.band.{key}.name, date format key) to results.* keys [AC 1]
Phase 2 - Tests: nl feature tests per page with presence and absence checks; existing English assertions in ResultsAccessTest and JudgeReportAccessTest still pass [AC 2]
Open (non-blocking): Oscar's review includes a print preview of one Dutch sheet (longer strings)
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Review: merged as a99dda8 (PR #79) so TASK-109 builds on it. Check in a browser with profile language Nederlands: a closed competition's results index, your score sheet and its print preview, and a judge report. Look at whether 'Mondgevoel' and 'Algeheel' fit their label column on screen and in print, and whether you like the Dutch wording (jurylid/juryleden, Niveau, Formulieren, Jouw keurprofiel). Worktree kept: /home/oscar/projects/beerjudge/.claude/worktrees/fix+task-108-translate-results (run 'composer run dev:lite' there).
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: index, show, judge-report and the full-standings partial take every string from 66 results.* keys; trans_choice for judges, entries, participants; ordinal keys (nl 'e'); judge-report sentences as placeholder keys; band pills via judging.band.{key}.name; dates via translatedFormat(__('results.date_format')). AC 2: ResultsLocaleTest (5 tests) renders each page for an nl member with presence and absence checks; fails at base. Pass A and Pass B CLEAN (Pass B rendered every page in nl and en across admin/member/judge branches, ties, zero- and one-sheet entries, hostile names with ':count' and HTML: no substitution, no unescaped output; English byte-identical to base apart from fixtures). CI green, merged as a99dda8 (PR #79). Departures: English '1 participants' is now '1 participant'; you_value/avg_value split to keep the bold/regular styling; the outlier list joins names with a translated 'and' in the view. Notes: decimals stay dot-formatted in Dutch (31.5); not in the ACs, your call.
<!-- SECTION:FINAL_SUMMARY:END -->
