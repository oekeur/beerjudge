---
id: TASK-95
title: Make club branding configurable and ship a neutral default icon
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 14:50'
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
- [x] #1 public/manifest.json is removed; GET /manifest.json returns name "{CLUB_NAME} BeerJudge" ("BeerJudge" when CLUB_NAME is empty) and a description that names CLUB_NAME when set and no club when empty
- [x] #2 Default icon.svg, favicon.svg and icon-192/512.png show the pint glass without the Amsterdam shield or XXX, and Oscar approved them visually
- [x] #3 With CLUB_ICON_DIR set, the manifest icon URLs and the x-app-head links return the override files (feature test asserts 200 and identical bytes)
- [x] #4 Feature tests cover empty name, set name, default icons and override icons
- [x] #5 The Foebar arms icons and the root logo.png live under resources/branding/foebar/
- [x] #6 .gitignore ignores docs/private/, and docs/private/deploy-notes.md says how Foebar production sets CLUB_NAME and CLUB_ICON_DIR (the icons ship in the image at /var/www/resources/branding/foebar) before deploying
- [x] #7 docs/pwa-reference.md and docs/env-reference.md describe the override
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 2: Generalize the app)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 2 - Configurable branding: manifest route from CLUB_NAME [AC 1]; neutral pint-glass icons, Oscar's visual check [AC 2]; CLUB_ICON_DIR override served by routes [AC 3]; feature tests [AC 4]; Foebar assets kept in resources/branding/foebar [AC 5]; private deploy note and gitignore [AC 6]; docs [AC 7]
[RISK] Foebar production shows the neutral icon after the next deploy - Resolved: AC 6 documents the mount before deploy
Open (non-blocking): errors/500 keeps static default icon paths
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Review: icon check. Default (neutral) icon: public/icons/icon-512.png and public/favicon.svg on branch improve/task-95-branding in the main checkout. Foebar override set: resources/branding/foebar/. Tests: tests/Feature/BrandingTest.php (7 tests), full suite 312/312.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: /manifest.json route (BrandingController) returns {CLUB_NAME} BeerJudge or BeerJudge, description with or without the club; public/manifest.json removed (nginx would shadow the route). AC 2: default icons are the pint glass without shield/XXX, regenerated with convert from resources/images/icon.svg; Oscar approved 2026-10-12 ('Run 1-4'). AC 3: /branding/{file} serves CLUB_ICON_DIR files when present, defaults otherwise; BrandingTest asserts 200 and identical bytes for both. AC 4: BrandingTest (7 tests) covers empty/set name, defaults, override, per-file fallback, unknown file 404, head links; full suite 312/312. AC 5: arms icons, source SVG and logo.png in resources/branding/foebar/. AC 6: /docs/private/ gitignored and excluded from VitePress; deploy-notes.md gives CLUB_NAME and CLUB_ICON_DIR=/var/www/resources/branding/foebar. AC 7: pwa-reference and env-reference describe the override. Departure: no volume mount needed (icons ship in the image), AC 6 and the plan updated accordingly.
<!-- SECTION:FINAL_SUMMARY:END -->
