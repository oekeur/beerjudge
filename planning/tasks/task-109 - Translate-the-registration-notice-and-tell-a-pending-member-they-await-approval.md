---
id: TASK-109
title: >-
  Translate the registration notice and tell a pending member they await
  approval
status: Planned
assignee: []
created_date: '2026-10-13 14:45'
updated_date: '2026-10-14 14:46'
labels:
  - i18n
dependencies:
  - TASK-108
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 106000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The invite registration page shows 'After registering, an admin will need to approve your account...' in English for a Dutch guest. After registering, a member logging in before approval is told 'Your account is not active.' without saying approval is pending. Found 2026-10-13 (nl/en render diff and the pre-launch walkthrough); deferred by Oscar: 'our group is mostly English, so it's fine for now'. Related: TASK-87 stores the guest's locale at registration.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The registration notice is translated in en and nl
- [ ] #2 A member awaiting approval is told the account is waiting for an admin, in their locale, covered by a test
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-translate-member-pages.md (Phase 3: Registration notice and pending-approval message)
Goal: the invite registration notice follows the guest locale; an inactive member is told an admin still has to approve the account, in their locale.
Phase 3 - Notice: new auth.register_notice key (en + nl), Accept-Language nl test [AC 1]
Phase 3 - Pending message: reword auth.inactive_account in en and nl (one message for pending and deactivated, Oscar 2026-10-14); update MiddlewareTest:31 and AdminPanelAccessTest:36; en wording test [AC 2]
Open (non-blocking): Dutch registrants see the message in English until TASK-87 stores their locale (TASK-87 depends on this task)
<!-- SECTION:PLAN:END -->
