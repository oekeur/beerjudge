---
id: TASK-92
title: 'Fix two follow-ups from TASK-81: BA seeder docs and demo competition styles'
status: Done
assignee:
  - '@agent'
created_date: '2026-10-09 19:25'
updated_date: '2026-10-09 19:32'
labels:
  - multi-source-styles
dependencies: []
documentation:
  - planning/2026-10-09-scrape-ba-2026-styles.md
priority: low
type: chore
ordinal: 89000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Gate reviews of TASK-81 found: docs/admin/bjcp-seeder-reference.md still describes BJCP-only seeding (no BaStyleSeeder, ba_beer source or attribution), and DemoSeeder attaches every style (Style::pluck('id')) to Autumn Harvest 2024, so the demo competition now also offers all 168 BA styles. Oscar asked to fix both right away (2026-10-09).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 docs/admin/bjcp-seeder-reference.md (or a sibling page linked from it) documents BaStyleSeeder: data file, field mapping, upsert key, version from edition, attribution rule, and the production seed command
- [x] #2 DemoSeeder attaches only bjcp_beer styles to Autumn Harvest 2024; a PHPUnit test running DatabaseSeeder + DemoSeeder asserts that competition has no ba_beer styles and the same number of styles as bjcp_beer rows
- [x] #3 Full PHPUnit suite and scripts/journeys/run-all.sh (which seeds DemoSeeder) pass
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Landed with TASK-81 in the PR for feat/ba-style-seeder (commit fix(seeders): attach only BJCP styles to the demo competition, document the BA seeder #92).
AC1: docs/admin/ba-seeder-reference.md (data file, mapping from BaStyleSeeder.php, upsert key, version from edition, attribution rule, production seed command), sidebar entry in docs/.vitepress/config.js, linked from bjcp-seeder-reference.md (source table gains ba_beer).
AC2: DemoSeeder attaches only bjcp_beer styles to Autumn Harvest 2024; BaStyleSeederTest::test_demo_competition_gets_only_bjcp_styles fails on the old line (168 vs 0) and passes now.
AC3: PHPUnit 303/303, PHPStan 0, Pint; run-all.sh 100 PASS (it seeds DemoSeeder).
Gate: conformance read by the orchestrator; adversarial pass skipped (one-line demo-data scope plus docs, covered by the test and the journeys). As planned.
<!-- SECTION:FINAL_SUMMARY:END -->
