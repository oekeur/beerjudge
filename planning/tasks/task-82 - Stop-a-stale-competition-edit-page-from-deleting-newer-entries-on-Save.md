---
id: TASK-82
title: Stop a stale competition edit page from deleting newer entries on Save
status: Done
assignee:
  - '@agent'
created_date: '2026-10-09 13:16'
updated_date: '2026-10-14 13:14'
labels:
  - v1
dependencies: []
documentation:
  - docs/user-journeys.md
priority: high
type: bug
ordinal: 79000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The competition edit page's entries Repeater is relationship-bound: on Save, Filament deletes every entry row that is missing from the page's form state. An admin who opened the page before members registered more entries deletes those entries silently when they press Save (any field, any tab). Reproduced by gate probes on 2026-10-08/09 (docs/user-journeys.md, 2026-10-09 log, 'Found while fixing' a). Most likely during registration, when entries arrive while an admin has the page open.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 Saving a competition edit page that was opened before another entry was registered keeps that entry, covered by a feature test that fails on main
- [x] #2 An admin who removed an entry on the page still deletes it on Save (intended deletes keep working)
- [x] #3 Adding an entry on the page and editing an existing entry fields still save as before
- [x] #4 After a Save, a second Save on the same page without changes deletes nothing and duplicates nothing
- [x] #5 A row added on the page, saved, then removed and saved again is deleted
- [x] #6 An entry registered while the page is open survives two consecutive Saves from that page and ends up after the page rows in the tasting order (sort_order)
- [x] #7 Saving the edit page refuses to remove a style that any entry of the competition still uses (late entries included), with a form error naming the style, and nothing is saved
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-14-stale-entries-save.md (Phase 1: scope Repeater deletions to rows the page loaded)
Goal: Save deletes only entries the admin removed on that page; late entries are kept and placed after the page rows.
Phase 1 - remember loaded entry ids across Livewire requests, delete only loaded-but-removed, refresh the loaded set from the post-Save form state, append late entries after the page rows; Livewire tests plus Playwright and journey A3 [AC 1,2,3,4,5,6]
Open (non-blocking): override via saveRelationshipsUsing or a narrower hook, decided in implementation
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Landed in 59c2b40 (PR #73).
AC1: EditCompetition::loadedEntryIds (set in afterFill) plus saveEntries() wrapping Repeater::saveToRelationship(): entries not loaded by the page are set aside and kept; test_late_entry_survives_save_and_goes_after_page_rows (fails on main).
AC2: test_removed_row_is_still_deleted_while_late_entry_is_kept.
AC3: test_added_row_and_edited_fields_save.
AC4: test_second_save_without_changes_deletes_and_duplicates_nothing.
AC5: loaded set refreshed from the post-Save collection; test_row_added_saved_then_removed_and_saved_is_deleted (regression guard; passes on main by design).
AC6: late entries get sort_order after the page rows, in id order; test_late_entry_survives_two_consecutive_saves_and_stays_after_page_rows, test_several_late_entries_keep_registration_order.
AC7: Styles field rule refuses removing a style any entry of the competition uses (DB check), form error competition.styles_in_use EN/NL; three tests.
Gates: Probe 2 discriminating; Pass A raised an unprobed concern about a second Save, refuted by running the two-save test at head; Pass B confirmed the style-orphan sequence (fixed as AC7 in one retry, Oscar's call) and cleared tampered loadedEntryIds (admin-only, no more than row removal), failed validation, Sort by ABV, status actions, Create page, add-row-then-two-saves. CI 4/4 green; journeys 101/101 incl. A2/A3.
Departures: plan said AC5 fails on main; it cannot (stock Filament deletes such rows). AC7 added mid-task. Side effect: deleting the only entry using a style and unticking that style in one Save is refused (two Saves needed).
<!-- SECTION:FINAL_SUMMARY:END -->
