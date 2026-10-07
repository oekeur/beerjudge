---
id: TASK-13
title: 'Fix magic-link login: SameSite strict on production'
status: Done
assignee: []
created_date: '2026-10-07 14:38'
labels:
  - auth
dependencies: []
documentation:
  - planning/specs/authentication.md
priority: medium
type: bug
ordinal: 13000
---

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Production .env.production had SESSION_SAME_SITE=strict; set to lax and container recreated on 2026-10-07, login via emailed link confirmed. Docs: ff6cf37 (PR #17).
<!-- SECTION:FINAL_SUMMARY:END -->
