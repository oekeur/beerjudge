---
id: TASK-81
title: Seed Brewers Association 2026 styles with the BA attribution line
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-09 11:57'
updated_date: '2026-10-09 14:26'
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
- [ ] #6 npx playwright test tests/Browser/judging-style-guide-srm.spec.ts passes, and scripts/journeys/run-all.sh fails no step that passes on main (several runs at branch head and on main for any intermittent step)
- [ ] #7 Oscar has checked the guide modal and style-info sheet for a BA entry in a browser
- [ ] #8 At a 390x844 viewport, a BA entry with a 3000+ character guide text opens the guide sheet and the style-info sheet with their top and close button inside the viewport, the text scrollable within the sheet, and the attribution line not covered by the floating button (Playwright)
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

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Gate round 1 (Pass B, CONFIRMED): long BA text pushed the guide sheet above the viewport; at 390x844 sheet top y=-762, close button off-screen, taps did not close it. Sheet had no max height (pre-existing); BA text triggers it. Oscar: retry with the fix (AC 8). AC 6 reworded to no-new-failures-vs-main: 10 journey steps fail on main and branch alike (m1 password login, m1 magic link/reset x6, a1 invite register + approved login, a4 judges tile 0); m4 score entries 2 and 3 failed 1 of 4 at head, 0 of 1 at base.

Gate round 2 (d6413f3): Pass A no unmet AC. Pass B CONFIRMED: the guide sheet's new scroll body keeps scrollTop across opens (openGuide never resets it); BA-161 scrolled to bottom then BA-159 opened at scrollTop 476, first line hidden above the panel, no visual cue. Round-1 fix verified on real data at 390x844, 844x390, 1280x800 (top 127/59/120, x inside, scrolls, backdrop and Escape close); full Playwright 43 passed 1 skipped and journeys 100 PASS at d6413f3; real Sentry widget does not overlap the attribution; BJCP guide sheet unchanged; BJCP style-info sheet taller (60vh body cap -> 85dvh panel cap). Writer map: scroll position is touched only by user scroll; openGuide/openStyleInfo rebuild state, closeGuide/closeStyleInfo set open=false (x, backdrop, Escape). Style-info already kept scroll at the base.
<!-- SECTION:NOTES:END -->
