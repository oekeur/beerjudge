---
id: TASK-107
title: Translate the home page
status: Planned
assignee: []
created_date: '2026-10-13 14:45'
updated_date: '2026-10-14 14:46'
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
- [ ] #1 Every visible string on the home page comes from lang/en.json and lang/nl.json
- [ ] #2 A test renders the home page for an nl member and finds the Dutch strings
- [ ] #3 A unit test asserts lang/en.json and lang/nl.json hold identical key sets
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-translate-member-pages.md (Phase 1: Home page and key-parity guard)
Goal: the home page renders entirely from lang files; en/nl key drift fails a test.
Phase 1 - Home page: move every welcome.blade.php string to home.* keys (single section/badge label kept, so English 'Past' stays), translatedFormat for the judging date, nl feature test with absence checks [AC 1,2]
Phase 1 - Parity guard: unit test comparing sorted key sets of en.json and nl.json [AC 3]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
