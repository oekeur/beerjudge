---
id: TASK-28
title: Prevent removing the last admin in Filament
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 19:57'
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
- [x] #1 Saving a member that would leave zero active admins fails with a validation message
- [x] #2 Feature test covers demote and deactivate of the last admin
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 3: Small fixes)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Filament guard against removing the last active admin (is_admin && is_active): closure validation rules on the is_admin and is_active toggles in MemberForm, DeleteAction before() halt with a danger notification on EditMember, Member::isLastActiveAdmin(), en/nl messages. Verified: tests/Feature/LastActiveAdminGuardTest.php (demote, deactivate, delete blocked and fail at merge base; allowed paths with another active admin), Pass B probes (combined demote+deactivate, unrelated edits save, CreateMember unaffected, stale forms saved one after the other blocked), full suite green. Accepted by Oscar 2026-10-07: a true simultaneous mutual demotion can race (no lock). Not covered by design: artisan MemberDelete command.
<!-- SECTION:FINAL_SUMMARY:END -->
