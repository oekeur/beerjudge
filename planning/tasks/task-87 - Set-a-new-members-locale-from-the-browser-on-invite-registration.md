---
id: TASK-87
title: Set a new member's locale from the browser on invite registration
status: Planned
assignee: []
created_date: '2026-10-09 13:16'
updated_date: '2026-10-14 14:47'
labels:
  - i18n
dependencies:
  - TASK-109
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 112000
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

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-invite-registration-locale.md (Phase 1: Persist the resolved guest locale)
Goal: A Dutch-browser guest registering by invite is stored as locale nl, so the pending-approval message and activation email arrive in Dutch.
Phase 1 - Persist the resolved guest locale: RegistrationController::store stores app()->getLocale() (admin notification locale untouched); InvitationTest covers nl, default fallback, activation email locale and the pending-member login message; docs/user-journeys.md updated [AC 1,2]
[RISK] none open. TASK-109 key choice (reword vs new key) handled by resolving the key at execution time - Resolved.
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
