---
id: TASK-68
title: Validate SRM ranges in the style form
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
labels:
  - srm-visualization
dependencies: []
priority: low
type: bug
ordinal: 68000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The style form's numeric srm_min/srm_max fields have no bounds or ordering rule. Inverted (min 20, max 1) or out-of-range (0, above 40) values give negative glass and mask positions such as -23% and 123% in the judging screen's style guide. Found by the TASK-57 adversarial gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The style form rejects SRM values outside 1-40 and a minimum above the maximum, covered by a test
<!-- AC:END -->
