---
id: TASK-57
title: Show a glass for max-only SRM styles in the style guide
status: Done
assignee:
  - '@claude'
created_date: '2026-10-07 19:38'
updated_date: '2026-10-07 21:54'
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
- [x] #1 A style with only srm_max set shows one glass labelled with that value on the judging screen
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
The judging screen's Color (SRM) section now shows one glass labelled with srm_max for max-only styles. The four SRM elements that combine x-show with :style use object syntax (string :style wiped x-show's display:none, giving ghost glasses); judging.js returns 50% positions and masks when the display range collapses to 0 (srm 1/40 and min = max at the clamp edges). Browser coverage: tests/Browser/judging-style-guide-srm.spec.ts with spec-owned seeded styles BT2-BT4. Gates took four rounds (max glass, NaN at clamp edges, ghost glass and order-dependent spec, half-wash at min = max edges). UI confirmed by Oscar 2026-10-07.
<!-- SECTION:FINAL_SUMMARY:END -->
