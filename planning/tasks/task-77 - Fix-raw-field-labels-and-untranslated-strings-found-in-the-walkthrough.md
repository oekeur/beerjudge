---
id: TASK-77
title: Fix raw field labels and untranslated strings found in the walkthrough
status: Backlog
assignee: []
created_date: '2026-10-08 11:39'
updated_date: '2026-10-08 13:14'
labels:
  - i18n
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 77000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Found in the 2026-10-08 walkthrough (docs/user-journeys.md, finding 9): the entries repeater labels read 'Style id' and 'Abv'; the invite type radio is labelled 'Single-use', the same as its first option; the Sentry 'Report a Bug' button and the scoresheet aria-labels ('Aroma score, 0 to 12', 'Aroma notes') stay English in the Dutch interface; the login page <title> is a fixed 'Log in'. TASK-64 covers the admin login page and reset email separately.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Repeater fields have translated labels (Style, ABV)
- [ ] #2 The invite type radio has its own label
- [ ] #3 Scoresheet aria-labels and the member login title come from lang files in en and nl
- [ ] #4 The feedback button text follows the member's locale, or the limitation is documented
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (split over tasks 1.2, 2.1, 2.3, 3.1, 3.2, 4.1)
<!-- SECTION:NOTES:END -->
