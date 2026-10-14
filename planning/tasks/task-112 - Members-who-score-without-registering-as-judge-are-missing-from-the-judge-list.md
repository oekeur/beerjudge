---
id: TASK-112
title: Only registered judges can score; reword registration to participate
status: Done
assignee:
  - '@agent'
created_date: '2026-10-14 08:24'
updated_date: '2026-10-14 11:28'
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
- [x] #1 A member not in competition_judge gets a judging page whose server data has an empty entries list, without the <main> entry list, and with the participate button
- [x] #2 An admin who is not a judge still sees the progress panel listing every entry
- [x] #3 POST /scoresheets from a member who is not a judge of the entry competition returns 403 and stores nothing
- [x] #4 After choosing participate, the member sees the entry list and can save a sheet
- [x] #5 Registration copy says participate (EN) and deelnemen (NL) instead of register as judge, in the banner, the button and the leave link
- [x] #6 A judge can stop participating while the competition is open or judging, also after submitting; their submitted sheets stay stored and still count in the ranking; leaving a closed competition is refused
- [x] #7 The judging-screen progress panel and ResultsSummaryWidget (table and tiles) follow the plan progress rule; a test with a judge who submitted and then left shows no entry above its eligible count, Progress at most 100%, and the leaver not listed as missing
- [x] #8 A migration adds every member with a submitted sheet to that competition judge list; run on data that already complies, it changes nothing
- [x] #9 planning/specs/judging.md and competitions.md describe the gate, the leave rule and the pivot as competition_judge, and drop the claim that the screen auto-registers on first load
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

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Landed in eb57ecb (PR #71).
AC1: JudgingController::show sends empty entries/ownEntries to a non-judge, <main> only for judges, participate-prompt testid; JudgeParticipationGateTest parses the data-server JSON.
AC2: admin-only progressEntries (all entries) drive the progress panel; test_admin_who_is_not_a_judge_still_sees_every_entry_in_the_progress_panel.
AC3: ScoresheetController 403 for non-judges (after the not-judging 422, so a non-judge posting to a non-judging competition gets 422; nothing stored either way); test_scoresheet_post_from_non_judge_is_forbidden_and_stores_nothing.
AC4: test_participating_unlocks_the_entry_list_and_saving, Playwright judge-registration.spec.ts.
AC5: judging.participate / judging.stop_participating (Participate / Deelnemen, Stop participating / Stoppen met deelnemen) in banner, button, leave link; EN and NL tests in CompetitionJudgeAssignmentTest.
AC6: JudgeRegistrationController::destroy 403 unless open or judging; no sheet check; ranking test keeps the leaver's sheet.
AC7: JudgingProgressService feeds JudgingController::progress and results-summary (table and tiles, capped at 100%); ResultsSummaryWidgetTest leaver case.
AC8: migration 2026_10_14_110000_backfill_competition_judge_from_submitted_scoresheets; BackfillCompetitionJudgeMigrationTest (idempotent, drafts ignored); Pass B also ran it on Postgres.
AC9: specs/judging.md and competitions.md updated; user docs and the M4 journey script follow too.
Gates: Probe 2 discriminating; Pass A one note (422 before 403, accepted as reasonable); Pass B confirmed the M4 journey script was stale (fixed in one retry, journey m4 passes) and flagged that a leaver loses their judge report (Oscar's decision, unchanged). CI 4/4 green.
Departures: the admin progress panel now lists the admin's own entries too. Docs and the journey script were added to scope by the gate.
<!-- SECTION:FINAL_SUMMARY:END -->
