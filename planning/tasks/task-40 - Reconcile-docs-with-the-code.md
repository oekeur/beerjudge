---
id: TASK-40
title: Reconcile docs with the code
status: To Do
assignee: []
created_date: '2026-10-07 14:39'
labels:
  - docs
dependencies: []
documentation:
  - planning/specs/authentication.md
priority: low
type: docs
ordinal: 40000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Known drift found while seeding the tracker.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 CLAUDE.md and the enhancements PRD describe email on publish as triggered by closing, not published_at
- [ ] #2 CLAUDE.md data model shows descriptors as a scoresheets column, not descriptors + scoresheet_descriptor tables
- [ ] #3 planning/specs/authentication.md magic-link section matches verifyMagicLink (redirects to /login, regenerates rather than destroys the session)
<!-- AC:END -->
