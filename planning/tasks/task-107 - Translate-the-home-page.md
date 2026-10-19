---
id: TASK-107
title: Translate the home page
status: Review
assignee:
  - '@agent'
created_date: '2026-10-13 14:45'
updated_date: '2026-10-19 14:38'
labels:
  - i18n
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 104000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
welcome.blade.php hardcodes the 'Competitions' heading, the Judging/Open/Past section headings and status labels, the 'Judge entries', 'Register entry' and 'View results' links and 'No competitions yet.' A Dutch member sees them in English on the first page after login. Found 2026-10-13 by rendering every member page in nl and en and diffing the text; deferred by Oscar: 'our group is mostly English, so it's fine for now'.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Every visible string on the home page comes from lang/en.json and lang/nl.json
- [x] #2 A test renders the home page for an nl member and finds the Dutch strings
- [x] #3 A unit test asserts lang/en.json and lang/nl.json hold identical key sets
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-translate-member-pages.md (Phase 1: Home page and key-parity guard)
Goal: the home page renders entirely from lang files; en/nl key drift fails a test.
Phase 1 - Home page: move every welcome.blade.php string to home.* keys (single section/badge label kept, so English 'Past' stays), translatedFormat for the judging date, nl feature test with absence checks [AC 1,2]
Phase 1 - Parity guard: unit test comparing sorted key sets of en.json and nl.json [AC 3]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Review: merged as 677b27c (PR #76) so TASK-108, 31, 83 and 84 build on it. Check in a browser: set your profile language to Nederlands, open /, read the cards and dates, hover the three theme buttons. Worktree kept: /home/oscar/projects/beerjudge/.claude/worktrees/fix+task-107-translate-home (run 'composer run dev:lite' there). Gate history: Pass B found the theme toggle still English (fixed in the retry, now nav.theme.* keys); retry changed only that, re-gate skipped after a diff read and green tests.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: welcome.blade.php strings moved to home.* keys, judging date via translatedFormat('j M Y'); theme toggle tooltips and sr-only labels moved to nav.theme.* (found by Pass B). AC 2: HomeLocaleTest renders / for an nl member, asserts Dutch strings, date (15 mrt. 2025) and theme labels, and absence of the English ones; empty state covered. Fails at base. AC 3: TranslationKeyParityTest compares en/nl key sets both ways. CI green, merged as 677b27c (PR #76). Departure: the plan scoped welcome.blade.php only; the shared theme-toggle component was added because it renders on the home page. Notes from Pass B (theoretical): a member locale outside en/nl would show raw keys, but every write path validates in:en,nl.
<!-- SECTION:FINAL_SUMMARY:END -->
