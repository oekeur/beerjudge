---
id: TASK-29
title: Email each participant their results link when a competition closes
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 20:15'
labels:
  - email-on-publish
dependencies:
  - TASK-27
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

Landed on feat/launch-email-bugfix-batch (03460ab). Gates: Pass A + Pass B; retry fixed two CONFIRMED findings (resend offered/mailed inactive members; backfill left closed rows with NULL updated_at unguarded). Evidence: tests/Feature/CompetitionResultsEmailTest.php (7 tests), Pass B probes (form-save close path sends once, sync queue mail in member locale, COALESCE on Postgres 16). Review for Oscar: resend modal and the email body have not been seen in a browser. Worktree: <dev-checkout>/.claude/worktrees/feat+task-29-results-email
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Results email on close: queued CompetitionResultsNotification sent from Competition::booted() when status changes to closed (header action and status Select both covered), once per active member with an entry, guarded by competitions.results_mailed_at (migration backfills already-closed rows with COALESCE(updated_at, now)). Resend-to-one-member header action on EditCompetition, active participants only, ignores the guard. Specs/roadmap updated. Verified: CompetitionResultsEmailTest (7 tests), Pass A + Pass B (retry fixed inactive resend and NULL updated_at backfill), full suite green; Oscar confirmed the resend action and the close email in the browser (dev:lite, 2026-10-07).
<!-- SECTION:FINAL_SUMMARY:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] [RISK] Needs a real MAIL_FROM_ADDRESS (TASK-27) but had no dependency; link type (login-gated vs signed URL) undecided; ACs say 'member with an entry', which excludes judges without entries. — Dependency on TASK-27 added; decide link type before planning; add an AC naming the recipients.
---
<!-- COMMENTS:END -->
