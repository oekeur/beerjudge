---
id: TASK-94
title: Credit beerjson/bjcp-json for the BJCP JSON and remove the scoresheet PDF
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:40'
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
- [x] #1 SCP_BeerScoreSheet.pdf is removed from the tree
- [x] #2 A third-party notice (README section or THIRD_PARTY_NOTICES) names beerjson/bjcp-json, includes its MIT notice, and states the file was modified
- [x] #3 The same notice lists the Brewers Association 2026 guidelines with the Style::SOURCE_ATTRIBUTIONS line
- [x] #4 docs/admin/bjcp-seeder-reference.md names the data source
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 1: License and credits)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 1 - License and credits: delete the PDF; THIRD_PARTY_NOTICES.md with beerjson MIT notice (modified) and the BA attribution string; source named in bjcp-seeder-reference.md [AC 1,2,3,4]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: SCP_BeerScoreSheet.pdf removed (git grep finds no reference outside planning). AC 2: THIRD_PARTY_NOTICES.md names beerjson/bjcp-json with its full MIT notice and lists the modifications, verified by diffing against upstream with jq: ~330 text fixes, X1/X2/X4 Spanish/Portuguese keys renamed to English, stray 24C key removed, X2 OG normalised; codes and count (110) identical. AC 3: the BA section quotes Style::SOURCE_ATTRIBUTIONS['ba_beer'] verbatim (grep -F of the constant's value matches) and the BA terms from the 2026-10-09 scrape plan. AC 4: bjcp-seeder-reference.md names the source repo and points to the notices. Departure: the modification list is longer than the plan's 'text fixes' because the diff showed key renames and one value normalisation.
<!-- SECTION:FINAL_SUMMARY:END -->
