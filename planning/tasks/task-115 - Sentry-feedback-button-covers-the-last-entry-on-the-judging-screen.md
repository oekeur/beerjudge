---
id: TASK-115
title: Sentry feedback button covers the last entry on the judging screen
status: To Do
assignee: []
created_date: '2026-10-14 08:25'
labels:
  - sentry
dependencies: []
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
- [ ] #1 With the list scrolled to the end, the last entry header is fully tappable and not covered by the feedback button on a phone-width viewport
- [ ] #2 Verified in a browser at phone width (Playwright or manual), not only by reading the markup
<!-- AC:END -->
