---
id: TASK-78
title: Keep the member nav on one line at phone width
status: Done
assignee:
  - '@agent'
created_date: '2026-10-08 11:39'
updated_date: '2026-10-08 14:22'
labels:
  - v1
dependencies: []
documentation:
  - docs/user-journeys.md
priority: low
type: bug
ordinal: 78000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
At 390 px the member nav wraps the app name and 'Log out' onto two lines (entry page, 2026-10-08 walkthrough, docs/user-journeys.md, finding 10).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 At 360-390 px the member nav fits one line in en and nl
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-08-journey-walkthrough-fixes.md (task 4.2)

Review: worktree <dev-checkout>/.claude/worktrees/fix+task-78-nav-phone-width. Run: cd <dev-checkout>/.claude/worktrees/fix+task-78-nav-phone-width && APP_ENV=browser-test php artisan serve --host=localhost --port=8024, then open http://localhost:8024/login (judge@browser-test.local / password; seeded after 'APP_URL=http://localhost:8024 npx playwright test tests/Browser/nav-phone-width.spec.ts' runs) at 360 and 390 px; /, /profile, /competitions/2/entries/create. Screenshots: <dev-checkout>/.claude/worktrees/fix+task-78-nav-phone-width/storage/logs/nav-phone-width/. Admin+nl at 360 truncates the brand to 'Bee...' by design.

Review (2026-10-08): gates Pass A (after retry: CLUB_NAME=Foebar so the spec renders the real brand; spec fails on old markup with a 2-line wrap, 56 vs <=42) and Pass B CLEAN (widths 320-700, en/nl members and admins, tap sizes, full browser suite 18/18 x2). Known tradeoff: admin brand truncates at phone widths (nl admin 'Fo…' at 320-360). Check: cd <dev-checkout>/.claude/worktrees/fix+task-78-nav-phone-width && APP_URL=http://localhost:8024 npx playwright test tests/Browser/nav-phone-width.spec.ts --workers=1 (seeds), then APP_ENV=browser-test php artisan serve --host=localhost --port=8024; log in at http://localhost:8024/login as judge@browser-test.local / password (nl: nav-nl@browser-test.local, admin: nav-admin@browser-test.local); devtools at 360/390 px on /, /profile, /competitions/2/entries/create. Screenshots: storage/logs/nav-phone-width/.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Member nav stays on one line from 360 px: tighter padding/gaps on phones, brand truncates (min 48 px) instead of wrapping, links group does not shrink. Verified: tests/Browser/nav-phone-width.spec.ts (360/390 px, en/nl, /, /profile, entry page; fails on old markup with a 2-line wrap now that the browser-test env renders the real 'Foebar BeerJudge' brand via CLUB_NAME); full browser suite 18/18 and PHP suite 259 after rebase; Pass A (after retry) and Pass B CLEAN; Oscar confirmed in the browser 2026-10-08. Tradeoff accepted: admin brand truncates on phones. Plan task 4.2.
<!-- SECTION:FINAL_SUMMARY:END -->
