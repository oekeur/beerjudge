---
id: TASK-64
title: Translate the admin login page and the password-reset email
status: Planned
assignee: []
created_date: '2026-10-07 21:40'
updated_date: '2026-10-14 14:34'
labels:
  - i18n
dependencies: []
priority: low
type: bug
ordinal: 107000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
SetLocale runs in the web group and the panel's authMiddleware only, so /admin/login (Filament ->login()) never gets the guest's Accept-Language locale. The password-reset email uses Laravel's stock ResetPassword strings and lang/nl.json has no keys for them, so it is always English. Found by the TASK-59 adversarial gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 /admin/login renders in Dutch for a browser sending Accept-Language: nl (English otherwise), and a signed-in member keeps their profile locale on admin pages
- [ ] #2 The password-reset email follows the member's profile locale regardless of the requester's Accept-Language, including the greeting, sign-off, footer and button subcopy
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-translate-admin-login-reset-mail.md
Goal: /admin/login follows the guest's Accept-Language; the reset email follows the member's profile locale, mail chrome included.
Phase 1 - Admin login locale: add SetLocale to the panel middleware() list (after StartSession/AuthenticateSession), keep the authMiddleware entry; tests in GuestLocaleTest [AC 1]
Phase 2 - Reset email locale: Member::sendPasswordResetNotification with ->locale(profile ?: default); nine stock mail keys in en.json and nl.json; tests in PasswordResetTest [AC 2]
[RISK] Subcopy key must match the framework string byte for byte - Resolved (asserted by test, probed by red-team)
Open (non-blocking): assumes TASK-85, TASK-109 and TASK-107 (key-parity test) landed first
<!-- SECTION:PLAN:END -->
