---
id: TASK-90
title: Expose the judging style guide panel as a dialog to screen readers
status: Planned
assignee: []
created_date: '2026-10-09 13:16'
updated_date: '2026-10-14 14:36'
labels:
  - style-guidelines
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 114000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The style guide panel on the judging screen opens as an overlay without role=dialog, aria-modal or focus handling, so screen readers do not announce it. Walkthrough finding 11 (docs/user-journeys.md, 2026-10-08).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Both judging bottom sheets (section guideline and style info) have role=dialog, aria-modal=true and an accessible name (guideline sheet: Blade-injected judging.aria.guide_dialog; style info sheet: labelled by its heading); the close buttons have an accessible name (judging.aria.close); new keys exist in both lang/en.json and lang/nl.json
- [ ] #2 Opening a sheet moves focus onto it (also when opened by touch or script), closing it by Escape, close button or backdrop returns focus to the control that opened it, Tab and Shift+Tab stay inside it, and Escape pressed with no sheet open does not move focus
- [ ] #3 A browser test asserts the dialog role, name, aria-modal, focus in, Tab containment, focus return and Escape for both sheets, fails on the pre-change markup, and the existing judging-style-guide-srm spec's close-button locators are updated to the new name
- [ ] #4 A PHPUnit test asserts the en and nl pages render the new close and guideline dialog strings
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-judging-style-guide-dialog.md (Phase 1: dialog semantics and focus handling on both sheets)
Goal: both judging bottom sheets are named modal dialogs with focus in, focus back, Escape and Tab containment.
Phase 1 - lang keys (en+nl), role/aria-modal/name/close-button markup in show.blade.php, focus handling and Escape guard in judging.js (rAF-deferred focus), PHPUnit locale test, new Playwright spec, update six '×' locators in the srm spec [AC 1,2,3,4]
[RISK] real touch taps may differ from Playwright clicks for show/focus timing - Deferred: manual phone tap after merge
Open (non-blocking): both sheets vs guideline sheet only (planned: both); depends on TASK-84 landing first (orchestrator records the dependency)
<!-- SECTION:PLAN:END -->
