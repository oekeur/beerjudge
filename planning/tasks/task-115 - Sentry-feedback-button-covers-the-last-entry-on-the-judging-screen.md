---
id: TASK-115
title: Sentry feedback button covers the last entry on the judging screen
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-14 08:25'
updated_date: '2026-10-14 11:29'
labels:
  - sentry
dependencies:
  - TASK-112
priority: medium
type: bug
ordinal: 112000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
On the judging screen the floating Sentry feedback button (feedbackIntegration autoInject, resources/js/sentry.js) covers the last entry in the list, which made it hard to tap at the first club evening (2026-10-13). The entry list <main> in judging/show.blade.php has only py-4 at the bottom. The style-guide sheet already reserves pb-24 for the same button (comment at show.blade.php line 516).

Fix: add bottom padding to the entry list so the last entry scrolls clear of the button, including with the iOS home-indicator safe area.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 With a fake Sentry DSN at a 390x844 viewport, the feedback button is present and, scrolled to the end, the last entry header bounding box does not intersect it
- [ ] #2 Checked in a browser (the Playwright check of AC 1 counts), not only by reading the markup
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-judge-registration-fixes.md (Phase 3: bottom clearance for the Sentry button)
Goal: The last entry on the judging screen scrolls clear of the floating feedback button.
Phase 3 - bottom padding plus safe-area inset on <main> in judging/show.blade.php; Playwright check with a fake DSN (recipe in admin-sentry.spec.ts) [AC 1,2]
Runs after TASK-112 (same Blade file).
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
