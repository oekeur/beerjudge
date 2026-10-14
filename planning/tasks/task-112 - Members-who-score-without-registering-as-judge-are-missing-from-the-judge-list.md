---
id: TASK-112
title: Only registered judges can score; reword registration to participate
status: To Do
assignee: []
created_date: '2026-10-14 08:24'
updated_date: '2026-10-14 08:40'
labels:
  - judging-progress
dependencies: []
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
- Leave as judge becomes unavailable once the member has submitted their first sheet.

The server enforces the same rule: ScoresheetController rejects a sheet from a member who is not a judge of the competition, so a stale page or a queued offline save cannot bypass the gate. Decide during implementation how judging.js surfaces that rejection (it is already reported to Sentry since TASK-110).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A member not in competition_judge sees the participate prompt on the judging screen and no scoresheet form
- [ ] #2 POST /scoresheets from a member who is not a judge of the competition is rejected and stores nothing
- [ ] #3 After choosing participate, the member sees the entry list and can score
- [ ] #4 Registration copy says participate (EN) and deelnemen (NL) instead of register as judge, in banner, button and leave link
- [ ] #5 Leave is not offered, and the leave route refuses, once the member has a submitted sheet in the competition
- [ ] #6 Feature tests cover the gate, the server rejection and the leave rule
<!-- AC:END -->
