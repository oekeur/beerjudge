---
id: TASK-79
title: >-
  Email the admin notification address about pending members; remove inactive
  demo members from prod
status: Planned
assignee: []
created_date: '2026-10-08 22:05'
updated_date: '2026-10-09 18:48'
labels:
  - auth
  - launch-ops
dependencies: []
documentation:
  - planning/2026-10-09-pending-member-notification-mail.md
priority: medium
type: feature
ordinal: 79000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Record an admin notification address (ADMIN_NOTIFICATION_EMAIL, Oscar's address) and email it whenever an invite registration creates a member awaiting approval. Separately, prod holds DemoSeeder members (member1..member8@beerjudge.local); the inactive ones must be removed, together with their references in the demo competitions. Plan: planning/2026-10-09-pending-member-notification-mail.md
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 config('app.admin_notification_email') reads ADMIN_NOTIFICATION_EMAIL (config/app.php), documented in .env.example and .env.docker.example; unset or blank sends nothing and registration still creates the member inactive
- [ ] #2 Invite registration queues one on-demand mail to that address with the new member's name and email and a link to the Members list carrying filters[pending_approval][isActive]=true; a Livewire test confirms that query lists the inactive member and not an active one
- [ ] #3 Only invite registration triggers the mail: an invalid or used invitation and an inactive member created in Filament send nothing
- [ ] #4 The mail uses the locale of the member whose email equals the notification address, else SetLocale's captured default, never the registrant's Accept-Language
- [ ] #5 A failed notification dispatch is reported and registration still redirects to login with the success flash
- [ ] #6 planning/specs/authentication.md and planning/specs/members.md describe the notification
- [ ] #7 Prod: deployed with ADMIN_NOTIFICATION_EMAIL set; a throwaway registration delivered the mail and the throwaway member was deleted
- [x] #8 Prod: after a pg_dump backup, demo members member2..member8@beerjudge.local are deleted together with their scoresheets and entries in the TEST competitions; member1 stays (Oscar, 2026-10-09)
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-09-pending-member-notification-mail.md
Goal: email the admin notification address about every pending invite registration, and remove the inactive DemoSeeder members from prod.
Phase 1 - Config key, queued on-demand notification from RegistrationController::store, EN/NL text, locale from matching member else SetLocale default, report-and-continue on dispatch failure, specs updated; base main after improve/journey-walkthrough-fixes lands [AC 1,2,3,4,5,6]
Phase 2 - Oscar deploys, sets ADMIN_NOTIFICATION_EMAIL on the server, verifies with a throwaway registration [AC 7]
Phase 3 - Oscar runs the runbook: pg_dump via the postgres container, read-only listing grouped by competition, transaction deleting judged scoresheets, entries, then member [AC 8]
[RISK] none open; queued-mail delivery on prod is verified by AC 7
Open (non-blocking): what to do with ACTIVE demo members (member1/member2 are seeded as admins with password 'password')
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-10-09 Phase 3 run on prod: pg_dump backup at VPS ~/backup-2026-10-09-pre-demo-cleanup.sql (413660 bytes, 15 tables). Listing: 0 inactive members on prod; member1..8 are all active (1 and 2 admins), so AC 8's delete set is empty and nothing was deleted. member2,4,5,6,7,8 still have password 'password' (Hash::check); member1 and 3 do not. Demo data sits in competitions 'TEST - CLOSED', 'TEST - JUDGING', 'TEST - OPEN FOR SUBMITTING' (not the seeder names); 'Old World Hops' has no demo references. Waiting on Oscar: what to do with the active demo accounts; Phase 3 and AC 8 need revising to match.

2026-10-09 Oscar: 'Delete members 2-8 for me'. Backup VPS ~/backup-2026-10-09-2047-pre-delete-members-2-8.sql (15 tables). One transaction, guarded to abort on references outside 'TEST - %' competitions (none): deleted 14 scoresheets judged by them, 17 entries (with 21 scoresheets on them), 7 members. Remaining demo account: member1 (admin). Active admins: Member 1, Erwin. Members total 12.
<!-- SECTION:NOTES:END -->
