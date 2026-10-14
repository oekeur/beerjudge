---
id: TASK-113
title: Saving the competition edit page overwrites the judge list with stale state
status: Done
assignee:
  - '@agent'
created_date: '2026-10-14 08:25'
updated_date: '2026-10-14 10:56'
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
- [x] #1 A judge who registers while an admin has the edit page open is still a judge after the admin saves
- [x] #2 Removing a judge on one copy of the edit page is not undone by a save from a second copy loaded earlier
- [x] #3 Saving the edit page with no change to the judges field leaves competition_judge unchanged
- [x] #4 The judges field is not shown on the Create page
- [x] #5 In a browser, adding or removing a judge and then reloading without pressing Save shows the change persisted (Playwright, or handed to Oscar with the exact steps)
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-judge-registration-fixes.md (Phase 1: judge-list edits apply one change at a time)
Goal: Adding or removing a judge on the edit page writes that one change immediately; Save never touches competition_judge.
Phase 1 - live select with per-change attach/detach from $old vs new, saveRelationshipsUsing(null) after relationship(), hidden on Create; Livewire stale-page tests plus a browser reload check [AC 1,2,3,4,5]
Runs before TASK-112. Shares CompetitionForm.php with TASK-111; the second to land rebases and re-runs its query-count tests.
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
2026-10-14: Oscar had the edit page open on one device and one tab only that evening, so the two-copies explanation for the judge removal that took several tries is ruled out. That removal stays unexplained. The stale-save bug itself is confirmed and matters more once TASK-112 makes self-registration the only way to become a judge: any admin save from a page opened before a judge registered drops that judge.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Landed in 411e340 (PR #70).
AC1: judges Select no longer saves via relationship (saveRelationshipsUsing(null) after relationship()); test_judge_registered_while_page_is_open_survives_save.
AC2: live select attaches/detaches only the diff against $old; test_removal_on_one_page_is_not_undone_by_save_from_stale_page (two page instances).
AC3: test_save_without_judge_change_leaves_judges_unchanged.
AC4: field visible only when the record exists; test_judges_field_is_hidden_on_create_and_shown_on_edit.
AC5: Playwright spec admin-competition-judges.spec.ts adds and removes a judge and reloads without Save; passed locally and in CI browser-tests.
Gates: Probe 2 4/5 new tests fail at base; Pass A CLEAN; Pass B CLEAN (7 extra probes: stale page, validation error, lifecycle action, inactive judge, nested-key client updates, create page). CI 4/4 green.
Notes: a judge added by the admin at the same millisecond as a self-registration could 500 on the composite key (pre-existing race). A live judge edit may trigger Filament's unsaved-changes warning (unverified).
As planned.
<!-- SECTION:FINAL_SUMMARY:END -->
