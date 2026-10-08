---
id: TASK-75
title: Self-host the Sentry SDK for the admin feedback widget
status: Backlog
assignee: []
created_date: '2026-10-08 11:39'
labels:
  - sentry
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 75000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
AdminPanelProvider.php imports the Sentry SDK from https://browser.sentry-cdn.com/10.58.0/bundle.esm.min.js. Ad-blocker lists block that domain, which is why TASK-47 self-hosted the SDK for the 500 page. The request was aborted on every admin page load in the 2026-10-08 walkthrough (docs/user-journeys.md, finding 7).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The admin panel loads the Sentry SDK from a self-hosted file, like public/vendor/error-report/bundle.js
- [ ] #2 No request to browser.sentry-cdn.com remains in the app
<!-- AC:END -->
