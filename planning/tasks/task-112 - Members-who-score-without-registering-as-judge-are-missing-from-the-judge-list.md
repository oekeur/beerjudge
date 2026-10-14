---
id: TASK-112
title: Members who score without registering as judge are missing from the judge list
status: To Do
assignee: []
created_date: '2026-10-14 08:24'
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

Cause (from code, 2026-10-14): JudgingController::show renders the full scoresheet for any logged-in member while the competition is judging. Registration happens only through the banner button (POST competitions.judges.store) or the ?register=1 invite link; the banner is a prompt, not a gate. ScoresheetController::upsert checks judging status, own entry and UUID ownership, never registration. So a member who opens the judging screen from the home page link (no ?register=1) and ignores the banner scores as an unregistered judge.

Effects: ResultsSummaryWidget and JudgingController::progress take the eligible-judge total from competition_judge but count submitted sheets from every judge, so an entry can show more submitted sheets than judges and missing-judge names omit these members. ResultsController::index and JudgeReportController require a competition_judge row, so these members get no judge report until an admin adds them.

Second possible cause for the same symptom: the member did register, and a later save of a stale Filament edit page removed them (see the stale judge-list ticket). The production probe cannot tell the two apart; there is no audit trail of pivot changes.

Recommended fix: register the judge server-side when ScoresheetController accepts a sheet (syncWithoutDetaching), so everyone who scored is a judge. Open question for Oscar: should Leave as judge stay available once the member has submitted sheets?
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A member who saves a scoresheet in a judging competition is in competition_judge afterwards, whether or not they used the banner or the invite link
- [ ] #2 The admin progress counts (ResultsSummaryWidget and the judging-screen progress panel) never show more submitted sheets than eligible judges for an entry
- [ ] #3 A member who scored gets the judge report button on the results index once the competition closes
- [ ] #4 Feature tests cover scoring without prior registration
<!-- AC:END -->
