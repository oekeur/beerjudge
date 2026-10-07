---
id: TASK-64
title: Translate the admin login page and the password-reset email
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
labels:
  - i18n
dependencies: []
priority: low
type: bug
ordinal: 64000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
SetLocale runs in the web group and the panel's authMiddleware only, so /admin/login (Filament ->login()) never gets the guest's Accept-Language locale. The password-reset email uses Laravel's stock ResetPassword strings and lang/nl.json has no keys for them, so it is always English. Found by the TASK-59 adversarial gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 /admin/login renders in Dutch for a browser sending Accept-Language: nl
- [ ] #2 The password-reset email follows the member's profile locale
<!-- AC:END -->
