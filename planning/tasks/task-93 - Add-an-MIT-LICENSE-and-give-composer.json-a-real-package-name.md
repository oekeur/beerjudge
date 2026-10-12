---
id: TASK-93
title: Add an MIT LICENSE and give composer.json a real package name
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:38'
labels:
  - public-launch
dependencies: []
priority: high
type: chore
ordinal: 90000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Open-source readiness audit, item 1 (decided 2026-10-12: MIT). The repo has no LICENSE file; composer.json still carries the Laravel skeleton name and description.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 LICENSE at the repo root holds the MIT text, copyright 2026 Oscar Keur
- [x] #2 composer.json name is oekeur/beerjudge with a one-line description, and composer validate --no-check-publish passes
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 1: License and credits)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 1 - License and credits: MIT LICENSE, composer name and description [AC 1,2]
README license link and Rob credit moved to TASK-97 AC 3.
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-10-12: Rob (rob518183, 10 commits) agreed to publishing their contributions under MIT.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: LICENSE added with the MIT text, copyright 2026 Oscar Keur. AC 2: composer.json name oekeur/beerjudge, project description and keywords; composer validate --no-check-publish reports valid after composer update --lock (lock diff: content-hash plus a phpdotenv source-URL metadata refresh, same commit reference). As planned.
<!-- SECTION:FINAL_SUMMARY:END -->
