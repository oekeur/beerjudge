---
id: TASK-113
title: Saving the competition edit page overwrites the judge list with stale state
status: To Do
assignee: []
created_date: '2026-10-14 08:25'
labels:
  - judging-progress
dependencies: []
priority: high
type: bug
ordinal: 110000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The judges multi-select on the competition edit page (CompetitionForm, Dashboard tab) is a Select with ->relationship("judges"). On Save, Filament syncs competition_judge to the form state captured when the page loaded. Judges who registered themselves after that (banner or ?register=1 link) are detached; judges removed elsewhere are re-attached. Nothing refreshes the select while the page is open; the 5s poll only re-renders ResultsSummaryWidget.

Confirmed 2026-10-14 with a throwaway feature test: admin opens EditCompetition with judges [A], judge B attaches through the registration route, admin calls save, competition_judge holds [A] only.

Likely link to the first club evening (2026-10-13), not yet confirmed: Oscar removed an absent judge from the list and it took several tries. That matches two open copies of the edit page (phone and laptop, or two tabs) where the second save puts the judge back. It may also explain the TASK-112 symptom and the missing judge report button (separate ticket), if a stale save dropped those rows. Ask Oscar whether the edit page was open on more than one device or tab that evening.

Recommended fix: stop syncing the full list on form save. Apply each change as its own attach or detach (diff against the state the select last showed, or a dedicated add/remove judge action), so a save can only change judges the admin touched.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A judge who registers while an admin has the edit page open is still a judge after the admin saves
- [ ] #2 Removing a judge from the edit page is not undone by a save from a second, older copy of the page
- [ ] #3 Adding and removing judges from the edit page still works and is covered by Livewire feature tests, including the stale-page case
<!-- AC:END -->
