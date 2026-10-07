---
id: TASK-61
title: Remove stale publish and batch-printing text from docs
status: Done
assignee:
  - '@claude'
created_date: '2026-10-07 20:15'
updated_date: '2026-10-07 21:13'
labels:
  - docs
dependencies: []
priority: low
type: docs
ordinal: 61000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Found by the TASK-40 gate (2026-10-07). These still describe published_at, a publish step or batch printing: README.md:96, planning/requirements/homebrew-competition-prd.md (lines 9, 25, 57, 80), planning/specs/results.md:15, docs/competition/results-howto.md:44 ('Email-on-publish is a planned enhancement'). Results are visible once a competition is closed; the email goes out on close (TASK-29); the Print all button was removed (TASK-51).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 grep -rn -i 'published_at\|email-on-publish\|batch print' README.md planning/requirements planning/specs docs returns nothing that contradicts the code
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Removed stale publish-step, published_at and batch-print text from README.md, the competition PRD, planning/specs/results.md and docs/competition/results-howto.md; verified against ResultsController, Competition::booted and EditCompetition resendResults. Gate: Pass A CLEAN; Pass B skipped (docs only).
<!-- SECTION:FINAL_SUMMARY:END -->
