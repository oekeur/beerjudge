---
id: TASK-96
title: >-
  Publish a public Docker image from CI and write a self-host guide for
  outsiders
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 13:32'
labels:
  - public-launch
dependencies: []
priority: high
type: feature
ordinal: 93000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Audit item 6. docker-compose.yaml pulls the private ghcr.io/oekeur/beerjudge:latest; README and docs/docker-howto.md describe Oscar's laptop-to-VPS flow, so a stranger gets unauthorized.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 release-image.yml builds the Dockerfile on pull requests without pushing and on v* tags pushes ghcr.io/oekeur/beerjudge:{version} and :latest with GITHUB_TOKEN (packages: write); its PR build is green
- [ ] #2 docker-compose.selfhost.yaml, run alone in an empty directory with a filled .env, serves the login page on localhost:8080; the base, override and prod compose files are unchanged
- [ ] #3 docs/docker-howto.md and docs/docker-reference.md describe the selfhost file and member:create, and git grep -n "Watchtower\|laptop\|ssh " docs/docker-*.md is empty
- [ ] #4 The Watchtower, laptop-push and ssh-path specifics live in docs/private/deploy-notes.md
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 3: Self-hosting and README)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 3 - Public image and generic deploy guide: image workflow (PR build, tag push) [AC 1]; standalone selfhost compose file proven in a scratch dir [AC 2]; generic docker docs [AC 3]; private specifics moved [AC 4]
Open (non-blocking): tag push to the existing private package is first proven by TASK-98
<!-- SECTION:PLAN:END -->
