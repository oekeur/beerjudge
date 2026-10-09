---
id: TASK-67
title: Remove status from Competition mass assignment
status: Done
assignee: []
created_date: '2026-10-07 21:40'
updated_date: '2026-10-08 15:42'
labels:
  - security-hardening
dependencies: []
priority: low
type: chore
ordinal: 67000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Competition::$fillable includes status, so the read-only Status field (TASK-54) is a form-level guard only; any create() or update() with status bypasses the lifecycle actions. No such path is reachable from the UI today. Found by the TASK-54 conformance gate, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 status is set only through explicit lifecycle methods or forceFill; existing writers and tests still pass
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 2.4)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
status removed from Competition::$fillable; Competition::transitionTo() (forceFill + save, updated hook still mails once) is the only writer, used by the EditCompetition lifecycle actions, seeders (forceCreate), take-screenshots.js and docs. The CreateCompetition forced-Draft override was removed: status can no longer be mass assigned at all, the disabled select is not dehydrated and the model/migration default is draft; the TASK-54 tamper test still passes. Verified: create/update with status throw MassAssignmentException (tests fail at base); full suite and phpstan clean; DemoSeeder and BrowserTestSeeder seed; journey 101/101; Pass A and Pass B CLEAN; docs updated (lifecycle explanation/reference, specs/competitions.md). Plan task 2.4.
<!-- SECTION:FINAL_SUMMARY:END -->
