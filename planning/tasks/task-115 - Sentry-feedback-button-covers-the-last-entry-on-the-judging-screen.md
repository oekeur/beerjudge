---
id: TASK-115
title: Sentry feedback button covers the last entry on the judging screen
status: Done
assignee:
  - '@agent'
created_date: '2026-10-14 08:25'
updated_date: '2026-10-14 11:58'
labels:
  - sentry
dependencies:
  - TASK-112
priority: medium
type: bug
ordinal: 112000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
On the judging screen the floating Sentry feedback button (feedbackIntegration autoInject, resources/js/sentry.js) covers the last entry in the list, which made it hard to tap at the first club evening (2026-10-13). The entry list <main> in judging/show.blade.php has only py-4 at the bottom. The style-guide sheet already reserves pb-24 for the same button (comment at show.blade.php line 516).

Fix: add bottom padding to the entry list so the last entry scrolls clear of the button, including with the iOS home-indicator safe area.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 With a fake Sentry DSN at a 390x844 viewport, the feedback button is present and, scrolled to the end, the last entry header bounding box does not intersect it
- [x] #2 Checked in a browser (the Playwright check of AC 1 counts), not only by reading the markup
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-judge-registration-fixes.md (Phase 3: bottom clearance for the Sentry button)
Goal: The last entry on the judging screen scrolls clear of the floating feedback button.
Phase 3 - bottom padding plus safe-area inset on <main> in judging/show.blade.php; Playwright check with a fake DSN (recipe in admin-sentry.spec.ts) [AC 1,2]
Runs after TASK-112 (same Blade file).
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Landed in 59e96a6 (PR #72).
AC1: <main> padding pt-4 pb-[calc(6rem+env(safe-area-inset-bottom))]; Playwright judging-bottom-clearance.spec.ts asserts #sentry-feedback present, expands an entry, scrolls to the end and checks no overlap at 390x844; skips without a DSN (CI).
AC2: run in Chromium with a fake DSN: fails on the old padding (entry y=757 h=70 vs button y=778, orchestrator re-ran at the merge base), passes with the fix.
Gates: Probe 2 by the orchestrator (Playwright at base); claim check done inline on the one-hunk diff instead of a Pass A dispatch; Pass B CLEAN, including the expanded last entry (Submit 61px above the button, elementFromPoint hits Submit). CI 4/4 green.
Note: viewport meta has no viewport-fit=cover, so the safe-area term is 0 today.
As planned.
<!-- SECTION:FINAL_SUMMARY:END -->
