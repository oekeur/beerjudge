---
id: TASK-98
title: 'Add GitHub metadata, SECURITY.md and a v1.0.0 release tag'
status: Review
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 15:07'
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
- [ ] #4 Tag v1.0.0 sits on merged main, gh release view v1.0.0 succeeds, and the tag's image workflow outcome is reported (a code fix ships as v1.0.1; the tag is never moved)
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
<!-- SECTION:NOTES:END -->
