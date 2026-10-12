---
id: TASK-98
title: 'Add GitHub metadata, SECURITY.md and a v1.0.0 release tag'
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 17:26'
labels:
  - public-launch
dependencies: []
priority: medium
type: chore
ordinal: 95000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 10. Repo has no description, topics, tags or SECURITY.md.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 SECURITY.md points to GitHub private vulnerability reporting and names 1.x as supported
- [x] #2 CHANGELOG.md has a 1.0.0 section
- [x] #3 gh repo view shows the description and the topics homebrew, bjcp, laravel, filament, pwa
- [x] #4 Tag v1.0.0 sits on merged main, gh release view v1.0.0 succeeds, and the tag's image workflow outcome is reported (a code fix ships as v1.0.1; the tag is never moved)
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 4: Cleanup and release)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 4 - Release, runs last: SECURITY.md [AC 1]; CHANGELOG 1.0.0 [AC 2]; description and topics via gh [AC 3]; v1.0.0 tag, release, image run reported [AC 4]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-10-12: description and topics set; tag v1.0.0 on da2d832 (main CI green), release published. Image run 38267449232 built the image but the push failed: 'denied: permission_denied: read_package' on ghcr.io/oekeur/beerjudge:1.0.0. The package predates the workflow and was pushed from the laptop, so GITHUB_TOKEN has no access. Waiting on Oscar: package settings -> Manage Actions access -> add oekeur/beerjudge with Write; then gh run rerun 38267449232 (same tag, never moved).

2026-10-12: Oscar granted the repo Write access on the ghcr package. Re-ran run 38267449232 on the unmoved v1.0.0 tag: green. ghcr.io/oekeur/beerjudge:1.0.0, :1.0 and :latest all resolve; 1.0.0 and latest share digest sha256:9a61e3fb96472e610b3630e07d660ffc03b86a51180f036197ece902f39624ad.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC1: SECURITY.md (1.x supported, GitHub private vulnerability reporting), PR #50. AC2: CHANGELOG.md 1.0.0 section, PR #50. AC3: description and topics set with gh repo edit; checked with gh repo view. AC4: annotated tag v1.0.0 on da2d832 (merged main), release published; image run 38267449232 first failed at push (read_package denied: the package predates the workflow), passed on re-run after Oscar granted package access; 1.0.0/1.0/latest verified on ghcr. Departures: none; the settings-fix path was the one the plan named, and the tag was not moved.
<!-- SECTION:FINAL_SUMMARY:END -->
