---
id: TASK-92
title: 'Fix two follow-ups from TASK-81: BA seeder docs and demo competition styles'
status: In Progress
assignee:
  - '@agent'
created_date: '2026-10-09 19:25'
updated_date: '2026-10-09 19:25'
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
- [ ] #1 docs/admin/bjcp-seeder-reference.md (or a sibling page linked from it) documents BaStyleSeeder: data file, field mapping, upsert key, version from edition, attribution rule, and the production seed command
- [ ] #2 DemoSeeder attaches only bjcp_beer styles to Autumn Harvest 2024; a PHPUnit test running DatabaseSeeder + DemoSeeder asserts that competition has no ba_beer styles and the same number of styles as bjcp_beer rows
- [ ] #3 Full PHPUnit suite and scripts/journeys/run-all.sh (which seeds DemoSeeder) pass
<!-- AC:END -->
