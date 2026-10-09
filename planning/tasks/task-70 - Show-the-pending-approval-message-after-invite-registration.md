---
id: TASK-70
title: Show the pending-approval message after invite registration
status: Done
assignee:
  - '@agent'
created_date: '2026-10-08 11:39'
updated_date: '2026-10-08 14:00'
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
- [x] #1 After registering through an invite, the login page shows auth.register_success in the member's locale
- [x] #2 A feature test asserts the message is rendered on the login page after registration
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 1.2)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Login page renders session('success') (pending-approval message after invite registration) and its title uses __('auth.login'). Verified: two InvitationTest tests (en and nl via Accept-Language) fail at the merge base and pass on the branch; full suite passed; journey A1 step 2 passes; Pass A and Pass B CLEAN (Pass B probed stale flashes, logout and inactive bounces). Plan task 1.2.
<!-- SECTION:FINAL_SUMMARY:END -->
