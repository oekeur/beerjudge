---
id: TASK-39
title: Use the magic-link token only on an explicit POST
status: Backlog
assignee: []
created_date: '2026-10-07 14:39'
labels:
  - auth
dependencies: []
documentation:
  - planning/specs/authentication.md
priority: medium
type: enhancement
ordinal: 39000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
GET /magic-link/{token} marks the token used before login succeeds, so a mail scanner that follows links, or any failed login, burns it. Proposed: GET shows a 'Log in' button, POST consumes the token. Not yet decided.
<!-- SECTION:DESCRIPTION:END -->
