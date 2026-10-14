---
id: TASK-112
title: Only registered judges can score; reword registration to participate
status: Planned
assignee: []
created_date: '2026-10-14 08:24'
updated_date: '2026-10-14 09:37'
labels:
  - judging-progress
dependencies:
  - TASK-113
priority: high
type: bug
ordinal: 109000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Observed at the first club evening (2026-10-13): two members scored entries but were not in the competition judge list, so the admin progress counts were wrong.

Cause (from code, 2026-10-14): JudgingController::show renders the full scoresheet for any logged-in member while the competition is judging. Registration happens only through the banner button (POST competitions.judges.store) or the ?register=1 invite link; the banner is a prompt, not a gate. ScoresheetController::upsert checks judging status, own entry and UUID ownership, never registration. A member who opens the judging screen from the home page link (no ?register=1) and ignores the banner scores as an unregistered judge.

Effects: ResultsSummaryWidget and JudgingController::progress take the eligible-judge total from competition_judge but count submitted sheets from every judge, so an entry can show more submitted sheets than judges and missing-judge names omit these members. ResultsController::index and JudgeReportController require a competition_judge row, so these members get no judge report until an admin adds them.

Decided by Oscar (2026-10-14):
- Judges must register before they can score. The scoresheet form is only available to members in competition_judge; an unregistered member sees the participate prompt instead of the entry list.
- Reword the registration from "register as judge" to "participate" (Dutch: deelnemen), in the banner, the button and the leave link.
- Judges may stop participating at any time while the competition is open or judging, also after submitting; their submitted sheets keep counting (Oscar, 2026-10-14, reversing the earlier "no leave after first submission"). A judge who left gets no judge report.

The server enforces the same rule: ScoresheetController rejects a sheet from a member who is not a judge of the competition, so a stale page or a queued offline save cannot bypass the gate. Decide during implementation how judging.js surfaces that rejection (it is already reported to Sentry since TASK-110).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A member not in competition_judge gets a judging page whose server data has an empty entries list, without the <main> entry list, and with the participate button
- [ ] #2 An admin who is not a judge still sees the progress panel listing every entry
- [ ] #3 POST /scoresheets from a member who is not a judge of the entry competition returns 403 and stores nothing
- [ ] #4 After choosing participate, the member sees the entry list and can save a sheet
- [ ] #5 Registration copy says participate (EN) and deelnemen (NL) instead of register as judge, in the banner, the button and the leave link
- [ ] #6 A judge can stop participating while the competition is open or judging, also after submitting; their submitted sheets stay stored and still count in the ranking; leaving a closed competition is refused
- [ ] #7 The judging-screen progress panel and ResultsSummaryWidget (table and tiles) follow the plan progress rule; a test with a judge who submitted and then left shows no entry above its eligible count, Progress at most 100%, and the leaver not listed as missing
- [ ] #8 A migration adds every member with a submitted sheet to that competition judge list; run on data that already complies, it changes nothing
- [ ] #9 planning/specs/judging.md and competitions.md describe the gate, the leave rule and the pivot as competition_judge, and drop the claim that the screen auto-registers on first load
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-judge-registration-fixes.md (Phase 2: registration gate and progress counts)
Goal: Only registered judges score; leaving stays possible; progress counts handle leavers; existing sheet authors backfilled.
Phase 2a - gate: non-judge gets empty entries and the participate button, admin panel keeps its own entry list, POST /scoresheets 403 for non-judges [AC 1,2,3,4]
Phase 2b - copy and leave: participate/deelnemen wording, leave allowed while open or judging, refused when closed, sheets keep counting [AC 5,6]
Phase 2c - progress rule in the judging-screen panel and ResultsSummaryWidget table and tiles [AC 7]
Phase 2d - idempotent backfill migration; spec fixes (judging.md, competitions.md) [AC 8,9]
Includes PHPUnit rework (about ten files) and BrowserTestSeeder plus Playwright spec updates; done when PHPUnit, PHPStan and Playwright pass. Depends on TASK-113 landing first.
Open (non-blocking): none
<!-- SECTION:PLAN:END -->
