---
id: TASK-101
title: Run the test suite against Postgres in CI
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
labels:
  - public-launch
dependencies: []
priority: medium
type: chore
ordinal: 98000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 11. ci.yml tests on SQLite only while production runs Postgres; CLAUDE.md also claims MySQL support that nothing tests.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 CI has a job running the PHPUnit suite against a Postgres service
- [ ] #2 The untested MySQL claim is removed from CLAUDE.md
<!-- AC:END -->
