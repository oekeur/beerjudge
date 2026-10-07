---
id: TASK-57
title: Show a glass for max-only SRM styles in the style guide
status: Review
assignee:
  - '@claude'
created_date: '2026-10-07 19:38'
updated_date: '2026-10-07 21:44'
labels:
  - srm-visualization
dependencies: []
priority: low
type: bug
ordinal: 57000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A style with srm_max set and srm_min null renders no glass in the judging screen's Color (SRM) section. The min glass is shown only when srmMin !== null (resources/views/judging/show.blade.php:432) and the max glass only when hasBoth (show.blade.php:441), so only the flat bar appears. Min-only styles show one glass. No seeded style is max-only (0 in the local DB); custom styles could be.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A style with only srm_max set shows one glass labelled with that value on the judging screen
<!-- AC:END -->
