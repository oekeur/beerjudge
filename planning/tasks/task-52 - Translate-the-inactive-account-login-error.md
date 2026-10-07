---
id: TASK-52
title: Translate the inactive-account login error
status: Planned
assignee: []
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 19:11'
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

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 3: Small fixes)
<!-- SECTION:NOTES:END -->
