---
id: TASK-1
title: Remove the beta notice from the registration page
status: To Do
assignee: []
created_date: '2026-10-07 14:36'
labels:
  - auth
dependencies: []
documentation:
  - planning/specs/authentication.md
priority: medium
type: chore
ordinal: 1000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
resources/views/auth/register.blade.php:9 shows: "Beta version — expect rough edges. Email notifications are not set up yet, so you won't receive a confirmation email." Mail is live in production (magic-link emails arrive, confirmed 2026-10-07), so the notice is wrong. Registration sends no confirmation email at all, so nothing replaces the sentence.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The beta notice is gone from /join/{token}
- [ ] #2 No other view still claims email is not set up (grep resources/ and lang/)
<!-- AC:END -->
