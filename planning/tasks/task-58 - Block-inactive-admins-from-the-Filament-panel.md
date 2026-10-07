---
id: TASK-58
title: Block inactive admins from the Filament panel
status: Done
assignee:
  - '@claude'
created_date: '2026-10-07 20:15'
updated_date: '2026-10-07 21:22'
labels:
  - security-hardening
  - admin-guard
dependencies: []
priority: high
type: bug
ordinal: 58000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Member::canAccessPanel() returns is_admin only, and the admin panel's authMiddleware (Authenticate, SetLocale) does not include RequireActiveMember. A deactivated admin keeps full /admin access: password login sends admins to /admin, and GET /admin as an inactive admin renders the panel. Confirmed by the TASK-52 adversarial gate (probe test_inactive_admin_panel, 2026-10-07). Deactivating an admin is the way to revoke access, so this defeats it.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 An inactive admin requesting /admin is denied (403 or redirect to /login), covered by a feature test
- [x] #2 An active admin still reaches /admin
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
RequireActiveMember now runs in the admin panel's authMiddleware (after Authenticate and SetLocale, so the inactive-account message is translated), registered persistent so it also guards POST /livewire/update: a deactivated admin with an open tab can no longer save records. An inactive admin is logged out and redirected to /login. Tests: tests/Feature/AdminPanelAccessTest.php (5). Gates: Pass A CLEAN; Pass B CONFIRMED the Livewire update bypass, fixed with isPersistent: true; second Pass B CLEAN.
<!-- SECTION:FINAL_SUMMARY:END -->
