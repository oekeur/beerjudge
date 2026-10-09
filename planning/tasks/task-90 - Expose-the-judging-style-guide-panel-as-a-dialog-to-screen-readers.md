---
id: TASK-90
title: Expose the judging style guide panel as a dialog to screen readers
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - style-guidelines
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 87000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The style guide panel on the judging screen opens as an overlay without role=dialog, aria-modal or focus handling, so screen readers do not announce it. Walkthrough finding 11 (docs/user-journeys.md, 2026-10-08).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The panel has role=dialog, an accessible name, moves focus in on open and back on close, and closes on Escape
- [ ] #2 A browser test asserts the role and focus behaviour
<!-- AC:END -->
