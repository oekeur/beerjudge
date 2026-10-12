---
id: TASK-94
title: Credit beerjson/bjcp-json for the BJCP JSON and remove the scoresheet PDF
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 12:38'
labels:
  - public-launch
dependencies: []
priority: high
type: chore
ordinal: 91000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 3 (decided 2026-10-12). bjcp_styleguide-2021.json is beerjson/bjcp-json styles/bjcp_styleguide-2021.json (MIT, (c) 2022 BeerJSON) with ~300 text fixes (joined words split). Keep the committed copy; MIT requires the notice. SCP_BeerScoreSheet.pdf is unused by code; delete it from the tree, history stays.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 SCP_BeerScoreSheet.pdf is removed from the tree
- [ ] #2 A third-party notice (README section or THIRD_PARTY_NOTICES) names beerjson/bjcp-json, includes its MIT notice, and states the file was modified
- [ ] #3 The same notice lists the Brewers Association 2026 guidelines with the Style::SOURCE_ATTRIBUTIONS line
- [ ] #4 docs/admin/bjcp-seeder-reference.md names the data source
<!-- AC:END -->
