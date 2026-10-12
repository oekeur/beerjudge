---
id: TASK-97
title: Fix the README quickstart and add a who-it-is-for section
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:32'
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
- [ ] #1 Following the quickstart verbatim on a fresh clone reaches a logged-in admin page: composer install before key:generate, php artisan member:create --admin documented as interactive, DemoSeeder mentioned
- [ ] #2 README has a who-it-is-for / not-for section: in-club competitions, phone-based offline judging, one instance per club, competition-wide ranking without per-style flights
- [ ] #3 README has a License section linking LICENSE, and credits naming rob518183 with a link to THIRD_PARTY_NOTICES.md
- [ ] #4 README's self-hosting section points to the selfhost guide and contains no Watchtower or private-package wording
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 3: Self-hosting and README)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 3 - README: quickstart verified on a fresh clone [AC 1]; scope section [AC 2]; license and credits [AC 3]; self-hosting pointer [AC 4]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
