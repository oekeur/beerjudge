---
id: TASK-48
title: Persist off-flavour descriptors on scoresheets
status: In Progress
assignee: []
created_date: '2026-10-07 15:09'
updated_date: '2026-10-07 17:40'
labels:
  - descriptors
dependencies: []
priority: high
type: bug
ordinal: 48000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Since 3e9be27 (2026-06-10) Scoresheet has no 'descriptors' in $fillable and no array cast, so ScoresheetController's create/update silently drops the ticked descriptors; JudgingController reads back nothing. Confirmed: (new Scoresheet)->fill(['descriptors'=>[...]]) keeps no descriptors attribute; local DB has 0 of 30 sheets with descriptors. Production judging data is affected. No test covers persistence.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Restored 'descriptors' in Scoresheet::$fillable and the 'array' cast removed by 3e9be27. Two tests in ScoresheetUpsertTest: POST stores and replaces descriptors; the judging screen returns them in serverData.existingSheets. Descriptors ticked between 2026-06-10 and this fix were never written, so they cannot be recovered.
<!-- SECTION:NOTES:END -->
