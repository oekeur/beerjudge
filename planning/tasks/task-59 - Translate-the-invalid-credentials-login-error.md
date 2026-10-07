---
id: TASK-59
title: Translate the invalid-credentials login error
status: To Do
assignee: []
created_date: '2026-10-07 20:15'
labels:
  - i18n
dependencies: []
priority: low
type: bug
ordinal: 59000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
AuthController.php:33 returns __('Invalid credentials.'), which has no key in lang/en.json or lang/nl.json, so Dutch members see English. Same bug class as TASK-52 (fixed with auth.inactive_account).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A failed password login with Dutch as the active locale shows a Dutch error, covered by a feature test
<!-- AC:END -->
