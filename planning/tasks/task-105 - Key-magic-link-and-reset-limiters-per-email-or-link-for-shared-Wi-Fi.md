---
id: TASK-105
title: Key magic-link and reset limiters per email or link for shared Wi-Fi
status: Done
assignee: []
created_date: '2026-10-13 12:28'
updated_date: '2026-10-13 12:28'
labels:
  - auth
dependencies: []
priority: high
type: bug
ordinal: 102000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Magic-link request and token limiters were per IP, so one shared club Wi-Fi IP throttled the room after 3 requests or 2 link opens. Found in the 2026-10-13 pre-launch walkthrough (docs/user-journeys.md).
<!-- SECTION:DESCRIPTION:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
magic-link-request 3/5 min per email+IP and 45/5 min per IP; magic-link-token 5/min per link+IP and 60/min per IP; password-reset-confirm 5/min per email+IP and 30/min per IP. Sized for ~15 members on one IP. Commit 2480609 (PR #57).
<!-- SECTION:FINAL_SUMMARY:END -->
