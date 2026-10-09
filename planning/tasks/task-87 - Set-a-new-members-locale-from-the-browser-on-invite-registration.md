---
id: TASK-87
title: Set a new member's locale from the browser on invite registration
status: Backlog
assignee: []
created_date: '2026-10-09 13:16'
labels:
  - i18n
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 84000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
RegistrationController::store creates members with locale 'en' regardless of the guest's Accept-Language, so a Dutch member gets the approval email and the app in English until they change their profile. Found by the TASK-70 Pass B gate, 2026-10-09.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A guest registering with Accept-Language: nl gets locale 'nl', covered by a test
- [ ] #2 The activation email follows that locale
<!-- AC:END -->
