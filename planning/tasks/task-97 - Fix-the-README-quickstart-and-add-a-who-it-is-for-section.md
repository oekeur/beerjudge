---
id: TASK-97
title: Fix the README quickstart and add a who-it-is-for section
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
labels:
  - public-launch
dependencies: []
priority: high
type: bug
ordinal: 94000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit items 7 and 9. Simple setup runs php artisan key:generate before composer install, and never creates a user (member:create is only in the docs index). The README does not state scope against BCOE&M.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Quickstart steps run in an order that works on a fresh clone, verified on a clean clone
- [ ] #2 Quickstart creates a first admin via member:create and mentions DemoSeeder
- [ ] #3 README has a who-this-is-for and not-for section: in-club, phone-based offline judging, one instance per club, competition-wide ranking without per-style flights
<!-- AC:END -->
