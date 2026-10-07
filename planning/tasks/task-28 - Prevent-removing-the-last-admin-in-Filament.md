---
id: TASK-28
title: Prevent removing the last admin in Filament
status: To Do
assignee: []
created_date: '2026-10-07 14:39'
labels:
  - admin-guard
dependencies: []
documentation:
  - planning/features/security-hardening.md
  - planning/specs/members.md
priority: medium
type: enhancement
ordinal: 28000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
MemberForm.php has a plain is_admin toggle. Demoting or deactivating the last admin locks everyone out of /admin.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Saving a member that would leave zero active admins fails with a validation message
- [ ] #2 Feature test covers demote and deactivate of the last admin
<!-- AC:END -->
