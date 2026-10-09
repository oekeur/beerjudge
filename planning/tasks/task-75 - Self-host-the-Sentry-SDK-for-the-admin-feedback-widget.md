---
id: TASK-75
title: Self-host the Sentry SDK for the admin feedback widget
status: Done
assignee: []
created_date: '2026-10-08 11:39'
updated_date: '2026-10-09 11:57'
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
- [x] #1 The admin panel loads the Sentry SDK from a self-hosted file, like public/vendor/error-report/bundle.js
- [x] #2 No request to browser.sentry-cdn.com remains in the app
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 3.2)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Admin panel loads the committed self-hosted Sentry bundle (/vendor/error-report/bundle.js) instead of browser.sentry-cdn.com, in an IIFE that bails without a DSN or bundle; all 24 feedback-widget texts translated (en + nl) on admin and member sites via a sentry-feedback-labels meta tag. Also fixed a pre-existing self-XSS/data loss: the sentry-user meta now encodes with JSON_HEX_APOS|QUOT|AMP|TAG (names like D'Hondt no longer break it). Verified: SentryMetaTagTest (new tests fail at base), full suite, npm run build, admin-sentry.spec.ts (bundle test always; Dutch button test with a DSN, verified on the final commit), Pass A and Pass B CLEAN (XSS payloads on 5 pages). Plan task 3.2.
<!-- SECTION:FINAL_SUMMARY:END -->
