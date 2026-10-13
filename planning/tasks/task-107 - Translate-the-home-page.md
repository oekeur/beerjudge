---
id: TASK-107
title: Translate the home page
status: To Do
assignee: []
created_date: '2026-10-13 14:45'
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
<!-- AC:END -->
