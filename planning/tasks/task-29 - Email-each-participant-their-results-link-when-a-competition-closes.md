---
id: TASK-29
title: Email each participant their results link when a competition closes
status: Review
assignee:
  - '@agent'
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 19:43'
labels:
  - email-on-publish
dependencies: []
documentation:
  - planning/roadmap.md
  - planning/requirements/homebrew-competition-enhancements-prd.md
  - planning/specs/results.md
priority: high
type: feature
ordinal: 29000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Trigger is the transition to closed (published_at was dropped in 2e9013e). Open choices in the roadmap: login-gated vs signed URL. Needs mail transport and a queue worker in production.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Closing a competition queues one email per member with an entry
- [x] #2 Re-closing or a status bounce does not send twice
- [x] #3 Admins can resend to one member from Filament
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 2: Results email on close)

Landed on feat/launch-email-bugfix-batch (03460ab). Gates: Pass A + Pass B; retry fixed two CONFIRMED findings (resend offered/mailed inactive members; backfill left closed rows with NULL updated_at unguarded). Evidence: tests/Feature/CompetitionResultsEmailTest.php (7 tests), Pass B probes (form-save close path sends once, sync queue mail in member locale, COALESCE on Postgres 16). Review for Oscar: resend modal and the email body have not been seen in a browser. Worktree: /home/oscar/projects/beerjudge/.claude/worktrees/feat+task-29-results-email
<!-- SECTION:NOTES:END -->
