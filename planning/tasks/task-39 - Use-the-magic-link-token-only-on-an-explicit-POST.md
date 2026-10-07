---
id: TASK-39
title: Use the magic-link token only on an explicit POST
status: To Do
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 20:56'
labels:
  - auth
dependencies: []
documentation:
  - planning/specs/authentication.md
priority: medium
type: enhancement
ordinal: 39000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
GET /magic-link/{token} marks the token used before login succeeds, so a mail scanner that follows links, or any failed login, burns it. Proposed: GET shows a 'Log in' button, POST consumes the token. Not yet decided.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 GET /magic-link/{token} with a valid token renders a page with a Log in button and does not mark the token used, covered by a feature test
- [ ] #2 POST with a valid token marks it used, logs the member in and redirects (admins to /admin, others to /), covered by a feature test
- [ ] #3 An invalid, expired or used token on GET or POST redirects to /login with the magic_link_invalid error
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
See planning/2026-10-07-magic-link-post-only-consume.md (single phase: GET confirm page, POST consumes atomically; controller, routes, view, lang, MagicLinkTest)
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decision (Oscar, 2026-10-07): yes. GET shows a Log in button, POST consumes the token.
<!-- SECTION:NOTES:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] Waiting on Oscar's yes/no decision. — Decide before planning.
---
<!-- COMMENTS:END -->
