---
id: TASK-96
title: >-
  Publish a public Docker image from CI and write a self-host guide for
  outsiders
status: To Do
assignee: []
created_date: '2026-10-12 12:37'
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
- [ ] #1 A GitHub Actions workflow builds and pushes a public image on a version tag
- [ ] #2 docker-compose.yaml references the public image
- [ ] #3 A self-host guide walks a stranger from zero to a first admin login
- [ ] #4 Oscar's own VPS runbook is moved out of the public docs
<!-- AC:END -->
