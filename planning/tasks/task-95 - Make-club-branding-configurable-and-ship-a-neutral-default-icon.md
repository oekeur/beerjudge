---
id: TASK-95
title: Make club branding configurable and ship a neutral default icon
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:32'
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
- [ ] #1 public/manifest.json is removed; GET /manifest.json returns name "{CLUB_NAME} BeerJudge" ("BeerJudge" when CLUB_NAME is empty) and a description that names CLUB_NAME when set and no club when empty
- [ ] #2 Default icon.svg, favicon.svg and icon-192/512.png show the pint glass without the Amsterdam shield or XXX, and Oscar approved them visually
- [ ] #3 With CLUB_ICON_DIR set, the manifest icon URLs and the x-app-head links return the override files (feature test asserts 200 and identical bytes)
- [ ] #4 Feature tests cover empty name, set name, default icons and override icons
- [ ] #5 The Foebar arms icons and the root logo.png live under resources/branding/foebar/
- [ ] #6 .gitignore ignores docs/private/, and docs/private/deploy-notes.md says how Foebar production sets CLUB_ICON_DIR and mounts resources/branding/foebar/ before deploying
- [ ] #7 docs/pwa-reference.md and docs/env-reference.md describe the override
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 2: Generalize the app)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 2 - Configurable branding: manifest route from CLUB_NAME [AC 1]; neutral pint-glass icons, Oscar's visual check [AC 2]; CLUB_ICON_DIR override served by routes [AC 3]; feature tests [AC 4]; Foebar assets kept in resources/branding/foebar [AC 5]; private deploy note and gitignore [AC 6]; docs [AC 7]
[RISK] Foebar production shows the neutral icon after the next deploy - Resolved: AC 6 documents the mount before deploy
Open (non-blocking): errors/500 keeps static default icon paths
<!-- SECTION:PLAN:END -->
