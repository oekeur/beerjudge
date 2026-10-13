---
id: TASK-88
title: Show a friendly page for too many attempts (429)
status: Done
assignee: []
created_date: '2026-10-09 13:16'
updated_date: '2026-10-13 13:59'
labels:
  - auth
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: enhancement
ordinal: 85000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
A throttled login, magic-link or reset request shows Laravel's bare '429 Too Many Requests' page (no resources/views/errors/429.blade.php). Rare now that login is keyed by email+IP, but confusing when it happens. Found 2026-10-08/09.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A translated 429 page (en + nl) explains to wait and try again, in the app's standalone error-view style
- [x] #2 A feature test covers it
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Shipped in PR #60 (e6436ea), together with a 403 page. resources/views/errors/403.blade.php and 429.blade.php are standalone pages like 500.blade.php, translated through new errors.* keys in en and nl, with a link home. tests/Feature/ErrorPagesTest.php covers nl and en 403, nl 429 from the magic-link throttle, and a JSON 403. Checked both at 390 px in headless Chromium.
<!-- SECTION:FINAL_SUMMARY:END -->
