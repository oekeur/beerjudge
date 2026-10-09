---
id: TASK-81
title: Seed Brewers Association 2026 styles with the BA attribution line
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-09 11:57'
updated_date: '2026-10-09 12:06'
labels:
  - multi-source-styles
  - style-guidelines
dependencies:
  - TASK-80
documentation:
  - planning/2026-10-09-scrape-ba-2026-styles.md
  - planning/specs/styles.md
priority: low
type: feature
ordinal: 80000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Seed the BA 2026 snapshot as source ba_beer next to BJCP, show the BA attribution line wherever BA guideline text appears (judging guide and style-info sheets, Filament edit page), keep BJCP first in the style picker, and document the source. Plan: planning/2026-10-09-scrape-ba-2026-styles.md (Phase 2).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A PHPUnit feature test runs BaStyleSeeder against a temp JSON fixture and asserts one row's code, source ba_beer, version 2026, category_name, mapped text columns, numeric ranges and nulls; a second run leaves the row count unchanged
- [ ] #2 Seeding via DatabaseSeeder on an empty database leaves the BJCP row count unchanged and adds 168 ba_beer rows
- [ ] #3 A judging page feature test asserts style_attribution is the BA attribution string for a BA entry and null for a BJCP entry
- [ ] #4 A Filament test asserts the style edit page shows the attribution for a BA style and not for a BJCP style
- [ ] #5 A test asserts StyleMultiSelect lists the BJCP Beer group before the Brewers Association group
- [ ] #6 npx playwright test tests/Browser/judging-style-guide-srm.spec.ts and the full scripts/journeys/run-all.sh pass
- [ ] #7 Oscar has checked the guide modal and style-info sheet for a BA entry in a browser
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-09-scrape-ba-2026-styles.md (Phase 2: Seed BA styles with attribution)
Goal: 168 ba_beer styles seeded next to BJCP, attribution wherever BA guideline text shows, BJCP first in the picker.
Phase 2 - Seeder: BaStyleSeeder with overridable path, SOURCE_LABELS ba_beer, registered in DatabaseSeeder [AC 1,2]
Phase 2 - Attribution: style_source/style_attribution in the judging payload, line in guide and style-info sheets, pre-line text, aria-label without BJCP, Filament edit page helper text [AC 3,4]
Phase 2 - Picker order: StyleMultiSelect groups in SOURCE_LABELS order [AC 5]
Phase 2 - Selectors and docs: Playwright spec, m4 journey, judging docs, styles spec, CLAUDE.md, README [AC 6]
Phase 2 - Browser review by Oscar [AC 7]
[RISK] Licence scope for name-only surfaces - Accepted: attribution only where BA guideline text is shown (Oscar, 2026-10-09)
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
