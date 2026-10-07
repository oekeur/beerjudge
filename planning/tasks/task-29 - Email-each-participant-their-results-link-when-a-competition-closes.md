---
id: TASK-29
title: Email each participant their results link when a competition closes
status: To Do
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-07 18:09'
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
- [ ] #1 Closing a competition queues one email per member with an entry
- [ ] #2 Re-closing or a status bounce does not send twice
- [ ] #3 Admins can resend to one member from Filament
<!-- AC:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] [RISK] Needs a real MAIL_FROM_ADDRESS (TASK-27) but had no dependency; link type (login-gated vs signed URL) undecided; ACs say 'member with an entry', which excludes judges without entries. — Dependency on TASK-27 added; decide link type before planning; add an AC naming the recipients.
---
<!-- COMMENTS:END -->
