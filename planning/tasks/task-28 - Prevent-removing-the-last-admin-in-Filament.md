---
id: TASK-28
title: Prevent removing the last admin in Filament
status: To Do
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 18:09'
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

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] [RISK] AC says 'zero active admins', but panel access ignores is_active (Member::canAccessPanel() checks is_admin only), and admins can also be removed via Filament delete and member:delete. — Define the invariant (does an inactive admin count?) and add ACs for the delete action and the artisan command.
---
<!-- COMMENTS:END -->
