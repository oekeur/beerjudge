---
id: TASK-106
title: >-
  Fix email capitals, note length, double-tap submit and invite-while-logged-in
  before the first club evening
status: Done
assignee: []
created_date: '2026-10-13 14:35'
updated_date: '2026-10-13 14:36'
labels:
  - launch-ops
  - auth
dependencies: []
documentation:
  - docs/user-journeys.md
priority: high
type: bug
ordinal: 103000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Four findings from the 2026-10-13 pre-launch walkthrough that Oscar chose to fix before the first evening.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Login, magic link and password reset ignore capitals in the email; stored emails are lowercased
- [x] #2 Scoresheet notes cannot exceed the server's 2000-character limit
- [x] #3 A double tap on Submit entry saves one entry and never answers 500
- [x] #4 Opening an invite link while logged in explains why it lands on home
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Shipped in PR #62 (fa5eb08). Member stores emails lowercased; MemberUserProvider lowercases credentials for the guard and password broker (member and Filament login, reset); lookups use Member::findByEmail; uniqueness checks normalise first; a migration lowercases stored emails, skipping case-only duplicates. Note textareas get maxlength=2000. Submit entry disables on submit and EntryController uses createOrFirst for the race (a plain catch broke on Postgres). GET /join moved out of the guest group and redirects a logged-in member home with a message. Tests: EmailCaseTest, EntryFormTest race test (fails without the fix on SQLite and Postgres), JudgingScreenTest, InvitationTest. Browser probes at 390 px confirmed all four.
<!-- SECTION:FINAL_SUMMARY:END -->
