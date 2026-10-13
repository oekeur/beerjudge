---
id: TASK-109
title: >-
  Translate the registration notice and tell a pending member they await
  approval
status: To Do
assignee: []
created_date: '2026-10-13 14:45'
labels:
  - i18n
dependencies: []
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
