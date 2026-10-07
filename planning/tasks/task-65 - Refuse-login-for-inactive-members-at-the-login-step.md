---
id: TASK-65
title: Refuse login for inactive members at the login step
status: Backlog
assignee: []
created_date: '2026-10-07 21:40'
labels:
  - auth
  - security-hardening
dependencies: []
priority: low
type: enhancement
ordinal: 65000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Password login (AuthController::login) and magic-link login never check is_active; an inactive member is logged in and only bounced by RequireActiveMember on the next page. Reasoned, not probed: for a stale admin tab the inactive-account flash may be consumed by Livewire's hidden follow of the 302 to /login, so the member sees the login page without the message. Found by the TASK-58 and TASK-39 gates, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 An inactive member's password or magic-link login is refused with auth.inactive_account and no session is created, covered by tests
<!-- AC:END -->
