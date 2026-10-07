---
id: TASK-46
title: Email members when an admin activates their account
status: Review
assignee: []
created_date: '2026-10-07 14:43'
updated_date: '2026-10-07 14:46'
labels:
  - auth
dependencies: []
documentation:
  - planning/specs/members.md
  - planning/specs/authentication.md
priority: high
type: feature
ordinal: 46000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Registration creates members with is_active=false; an admin approves them via the Members table (single or bulk approve) or the edit form toggle. The member currently has no way to know. Send an email when is_active changes from false to true, from any of those paths.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Activating a member through any path sends one email in the member's locale with a link to /login
- [x] #2 Saving a member without changing is_active, deactivating, or creating an already-active member sends nothing
- [x] #3 Registration page tells the registrant they will be emailed once approved
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Member::booted() updated hook sends MemberActivatedNotification on is_active false->true, locale from member. tests/Feature/MemberActivationEmailTest.php (8 tests) covers model update, row approve, bulk approve, no-op cases. Fixed pre-existing bug: bulk 'Approve selected' was never registered because ->toolbarActions([]) after ->bulkActions() replaced it. Full suite 193 green, PHPStan clean.
Review: on branch feat/member-activation-email, check /join/{token} page text, the Members table bulk approve (select rows, Approve selected), and the email itself.
<!-- SECTION:NOTES:END -->
