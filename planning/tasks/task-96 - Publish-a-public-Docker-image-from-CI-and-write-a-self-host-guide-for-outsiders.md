---
id: TASK-96
title: >-
  Publish a public Docker image from CI and write a self-host guide for
  outsiders
status: Done
assignee:
  - '@claude'
created_date: '2026-10-12 12:37'
updated_date: '2026-10-12 14:04'
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
- [x] #1 release-image.yml builds the Dockerfile on pull requests without pushing and on v* tags pushes ghcr.io/oekeur/beerjudge:{version} and :latest with GITHUB_TOKEN (packages: write); its PR build is green
- [x] #2 docker-compose.selfhost.yaml, run alone in an empty directory with a filled .env, serves the login page on localhost:8080; the base, override and prod compose files are unchanged
- [x] #3 docs/docker-howto.md and docs/docker-reference.md describe the selfhost file and member:create, and git grep -n "Watchtower\|laptop\|ssh " docs/docker-*.md is empty
- [x] #4 The Watchtower, laptop-push and ssh-path specifics live in docs/private/deploy-notes.md
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-12-public-launch.md (Phase 3: Self-hosting and README)
Goal: make the repo publishable (license, credits, generic branding, self-host image, working README, v1.0.0).
Phase 3 - Public image and generic deploy guide: image workflow (PR build, tag push) [AC 1]; standalone selfhost compose file proven in a scratch dir [AC 2]; generic docker docs [AC 3]; private specifics moved [AC 4]
Open (non-blocking): tag push to the existing private package is first proven by TASK-98
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Selfhost stack proven 2026-10-12: image built locally as :selftest, docker-compose.selfhost.yaml run alone in an empty scratch dir with a filled .env (port 18080): /login 200, db:seed --force loaded BJCP+BA, member:create --admin via piped stdin, HTTP login -> /admin/competitions 200. Stack torn down with down -v. actionlint 1.7.7 clean on release-image.yml and ci.yml. Original deploy guide moved verbatim to docs/private/deploy-notes.md (gitignored by TASK-95).
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
AC 1: release-image.yml builds on PRs touching Docker/dependency files and pushes version, major.minor and latest tags on v* tags; actionlint clean; the PR #47 image job built the Dockerfile green in 3m43s. The tag push itself is first exercised by TASK-98's v1.0.0. AC 2: docker-compose.selfhost.yaml run alone in an empty scratch dir with a filled .env and a locally built :selftest image: /login 200, db:seed loaded BJCP+BA, member:create --admin, HTTP login reached /admin/competitions 200; base/override/prod compose files untouched. AC 3: docker-howto.md rewritten as a generic self-host guide and docker-reference.md describes the selfhost file and the workflow; git grep for Watchtower/laptop/ssh in docs/docker-*.md is empty. AC 4: the previous guide moved verbatim to the gitignored docs/private/deploy-notes.md. Departure: a new standalone compose file instead of changing the base file (planned after the red-team found the base file unusable alone).
<!-- SECTION:FINAL_SUMMARY:END -->
