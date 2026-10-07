---
id: TASK-56
title: Fail loudly when a model discards unfillable attributes
status: Done
assignee: []
created_date: '2026-10-07 17:59'
updated_date: '2026-10-07 18:00'
labels:
  - descriptors
dependencies: []
priority: medium
type: bug
ordinal: 56000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
TASK-48 went unnoticed for four months because Eloquent silently dropped a field missing from $fillable. Enable Model::preventSilentlyDiscardingAttributes() outside production so tests and local use throw instead.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Outside production, mass-assigning an attribute that is not fillable throws MassAssignmentException
- [x] #2 Production behaviour is unchanged
- [x] #3 Full suite passes with the guard on
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AppServiceProvider enables Model::preventSilentlyDiscardingAttributes() outside production, next to preventLazyLoading. ModelStrictnessTest proves the throw; full suite (196) passes with the guard on. Filament form field names checked against each model's fillable: only relationship and repeater fields differ, which Filament does not mass-assign on the parent.
<!-- SECTION:FINAL_SUMMARY:END -->
