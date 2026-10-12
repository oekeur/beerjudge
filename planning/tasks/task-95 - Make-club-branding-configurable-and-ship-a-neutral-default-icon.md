---
id: TASK-95
title: Make club branding configurable and ship a neutral default icon
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
labels:
  - public-launch
dependencies: []
priority: high
type: feature
ordinal: 92000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 5. public/manifest.json hardcodes Foebar BeerJudge and the Foebar Amsterdam description although CLUB_NAME exists; icon and favicon carry the Amsterdam coat of arms.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The web manifest name and description derive from CLUB_NAME
- [ ] #2 The default icon set carries no club or city insignia
- [ ] #3 A club can supply its own icon without editing tracked files, documented in docs/env-reference.md or docs/pwa-reference.md
- [ ] #4 The Foebar deployment keeps its current name and icon through configuration
<!-- AC:END -->
