---
id: TASK-60
title: Guard the MemberDelete artisan command against removing the last active admin
status: Done
assignee:
  - '@claude'
created_date: '2026-10-07 20:15'
updated_date: '2026-10-07 21:17'
labels:
  - admin-guard
dependencies: []
priority: low
type: bug
ordinal: 60000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
app/Console/Commands/MemberDelete.php deactivates (lines ~31, ~44) or deletes (~56) a member with no last-admin check. TASK-28 guarded the Filament form and delete action only (decided scope). Member::isLastActiveAdmin() exists since TASK-28 and can be reused.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 The command refuses to deactivate or delete the last active admin and says why, covered by a test
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
member:delete now refuses to deactivate or delete the last active admin (Member::isLastActiveAdmin()), printing why and exiting 1 before any prompt or change. Deleting an inactive admin stays allowed. tests/Feature/MemberDeleteCommandTest.php covers --deactivate, the interactive run and the allowed cases. Gates: Pass A CONFIRMED a duplicated test (fixed in retry), Pass B CLEAN.
<!-- SECTION:FINAL_SUMMARY:END -->
