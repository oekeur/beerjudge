---
id: TASK-52
title: Translate the inactive-account login error
status: To Do
assignee: []
created_date: '2026-10-07 15:10'
labels:
  - i18n
dependencies: []
priority: low
type: bug
ordinal: 52000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
RequireActiveMember.php:28 uses __('Your account is not active.'), which has no key in lang/en.json or nl.json, so Dutch members see English.
<!-- SECTION:DESCRIPTION:END -->
