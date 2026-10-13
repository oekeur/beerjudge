---
id: TASK-108
title: 'Translate the results, score sheet and judge report pages'
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
