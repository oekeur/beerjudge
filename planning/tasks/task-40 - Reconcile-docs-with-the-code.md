---
id: TASK-40
title: Reconcile docs with the code
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 19:46'
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
- [x] #1 CLAUDE.md and the enhancements PRD describe email on publish as triggered by closing, not published_at
- [x] #2 CLAUDE.md data model shows descriptors as a scoresheets column, not descriptors + scoresheet_descriptor tables
- [x] #3 planning/specs/authentication.md magic-link section matches verifyMagicLink (redirects to /login, regenerates rather than destroys the session)
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 4: Docs reconciled)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
CLAUDE.md: competitions line published_at -> results_mailed_at; descriptors shown as a json column on scoresheets (no descriptor tables exist); batch-printing bullet replaced; Enhancement 4 rewritten as email on close (booted hook, results_mailed_at guard, active members, login-gated link, resend). PRD Enhancement 4 retitled and rewritten (no publish step), line 33 'before closing'. specs/authentication.md magic-link line matches verifyMagicLink (redirect to /login on invalid, remember-me login, session regenerated, used_at marked). Verified by Pass A against the code: done-when greps empty, every claim backed. Remaining drift outside scope reported to Oscar: README.md:96, planning/requirements/homebrew-competition-prd.md, planning/specs/results.md:15, docs/competition/results-howto.md:44.
<!-- SECTION:FINAL_SUMMARY:END -->
