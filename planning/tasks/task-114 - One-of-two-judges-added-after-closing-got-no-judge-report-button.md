---
id: TASK-114
title: One of two judges added after closing got no judge report button
status: To Do
assignee: []
created_date: '2026-10-14 08:25'
updated_date: '2026-10-14 08:40'
labels:
  - results
dependencies: []
priority: medium
type: bug
ordinal: 111000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
After the first club evening (2026-10-13), Oscar added the two unregistered judges (TASK-112) to the closed competition so they could see their judge report. One got the View judge report button, the other did not.

What the code says (2026-10-14): the button exists only on the results index (results/index.blade.php) and shows when competition_judge has a row for the viewer (ResultsController::index, isJudge). The home page and the results email link to results.show, the member sheet, which reaches the index only through the All results link. No other condition separates two judges, so the likely difference is data: the second member had no competition_judge row when they looked. Candidate causes: the add was never saved, or a stale save of the edit page removed it (TASK-113). Unconfirmed; a production read was denied by auto mode on 2026-10-14.

Probe to run first: list competition_judge rows for the competition and compare with the scoresheet judge_ids. If the second member has a row now, ask whether they opened the results index or only their sheet.

Separate UX gap: a judge without a sheet link on the home page has no direct path to their judge report.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 The cause is confirmed from production data (competition_judge rows vs scoresheet judges) and recorded in this task
- [ ] #2 Every member in competition_judge for a closed competition sees the judge report button on the results index
- [ ] #3 A judge can reach their judge report from the home page or the results email without going through the member sheet
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-10-14: Oscar used one device and one tab, so a save from a second, older copy of the edit page (TASK-113) did not drop the second judge. An add that was never saved is now the leading candidate; the production probe still decides.
<!-- SECTION:NOTES:END -->
