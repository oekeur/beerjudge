---
id: TASK-70
title: Show the pending-approval message after invite registration
status: Backlog
assignee: []
created_date: '2026-10-08 11:39'
labels:
  - auth
dependencies: []
documentation:
  - docs/user-journeys.md
priority: medium
type: bug
ordinal: 70000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
RegistrationController@store redirects to login with ->with('success', __('auth.register_success')), but auth/login.blade.php renders only $errors, never session('success'). A new member sees an empty login form and no word that an admin must approve them. Seen in 3 of 3 walkthrough runs, 2026-10-08 (docs/user-journeys.md, finding 1).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 After registering through an invite, the login page shows auth.register_success in the member's locale
- [ ] #2 A feature test asserts the message is rendered on the login page after registration
<!-- AC:END -->
