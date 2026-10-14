---
id: TASK-31
title: Add history and stats pages
status: Planned
assignee: []
created_date: '2026-10-07 14:39'
updated_date: '2026-10-14 14:34'
labels:
  - history-stats
dependencies: []
documentation:
  - planning/roadmap.md
  - planning/requirements/homebrew-competition-enhancements-prd.md
priority: medium
type: feature
ordinal: 31000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Per-member history, club-wide stats and per-style distributions as Filament pages and chart widgets. Reads existing tables only.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 HistoryStatsService returns the club, member and style aggregates (as defined in the plan) from closed competitions' submitted scoresheets only, covered by passing tests
- [ ] #2 An admin-only Club stats Filament page shows winner per competition, participation, score distribution and most-entered styles, with chart widgets and an empty state
- [ ] #3 An admin-only Member history Filament page shows a chosen member's entries across competitions, score trend, best results and average by style
- [ ] #4 An admin-only Style stats Filament page shows per-style entry counts and score average/min/max, plus a per-style trend across competitions
- [ ] #5 An active non-admin member gets 403 on each new page
- [ ] #6 Every new translation key exists in both lang/en.json and lang/nl.json, asserted in each page's test
- [ ] #7 The specs and roadmap no longer list per-member history or style statistics as unbuilt, and document the winner, scored-entry and average definitions
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-history-and-stats-pages.md
Goal: three read-only admin Filament pages with chart widgets (club stats, member history, style stats) from existing tables; member-facing page stays TASK-32.
Phase 1 - Stats service: HistoryStatsService with the settled definitions (closed competitions, scored entries, mean of entry avg_total, winner = placement 1 with tie flag) plus tests [AC 1]
Phase 2 - Club stats page: winners table, participation, distribution, most-entered styles, nav group, lang keys en+nl [AC 2,5,6]
Phase 3 - Style stats page: per-style table plus trend chart driven by a page filter (HasFiltersForm) [AC 4,5,6]
Phase 4 - Member history page and spec updates: member picker with entries, trend, best results, average by style; specs/roadmap updated [AC 3,5,6,7]
Risks: none open.
Open (non-blocking): tied first place shown as badge only; assumes lang JSON conflicts with sibling tasks are resolved on rebase; does not depend on the TASK-107 parity test (not found in tests/).
<!-- SECTION:PLAN:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] No acceptance criteria; three surfaces (member, club, per-style) in one task, so 'done' is undefined. — Split into three tasks or add one AC per surface.
---
<!-- COMMENTS:END -->
