# Plan: Stale competition edit page no longer deletes newer entries

**File:** planning/2026-10-14-stale-entries-save.md
**Goal:** Saving the competition edit page deletes only entries the admin removed on that page, never entries registered after the page loaded.
**Out of scope:** TASK-83 (lock the Entries list from Judging on) and TASK-111 (per-entry queries on save), both on the same Repeater; whichever lands second rebases (Oscar, 2026-10-14). Last-write-wins on fields of entries the page did load. A stale page re-creating an entry another admin deleted after it loaded (pre-existing, unchanged).
**Constraints:** Approach chosen by Oscar 2026-10-14: the page remembers which entries it loaded; Save deletes only loaded entries the admin removed; removing a row and leaving without Save still deletes nothing. Late entries go after the page's rows in the tasting order (Oscar, 2026-10-14).

---

## Phase 1: TASK-82, scope Repeater deletions to rows the page loaded (High)

**Outcome:** A Save from an edit page opened before a member registered an entry keeps that entry and places it after the page's rows; removing a row on the page and saving still deletes it; adding and editing rows still work; repeated Saves stay correct.

Tasks:
- [ ] TASK-82: replace the Entries Repeater's delete-missing-rows behaviour with delete-loaded-but-removed. Done when: [AC 1-7] pass as Livewire feature tests (AC 1, 5 and 6 fail on main), and the full PHPUnit suite, PHPStan, Pint, `npx playwright test --workers=1` and journey A3 (`scripts/journeys/a3-manage-entries.mjs`) pass.
  Context (verified 2026-10-14 by planning red-team probes): `CompetitionForm.php` `Repeater::make('entries')->relationship()` (line ~88), `->orderColumn('sort_order')`. Filament `Repeater::saveToRelationship()` (`vendor/filament/forms/src/Components/Repeater.php:965-1050`) deletes every record from `getCachedExistingRecords()` (re-queried from the DB on the save request, :1179) whose `record-{id}` key is absent from the state, then saves the page's items renumbering `sort_order` 1..n, and ends with `setRelation('entries', $existingRecords)`. The post-Save refill of the form state comes from that in-memory relation (`relationLoaded` branch, ~:1265-1273), not a fresh DB query; probe counted one entries select per Save. So: keep the loaded set across Livewire round trips (form state or a Livewire property), refresh it after each Save from the post-Save form state keys (never from a DB query), and keep late entries in `$existingRecords` so they join the refilled state. Late entries (registered after load) get `sort_order` after the page's rows, in registration order. A minimal variant that skips the post-Save refresh passes AC 1-4 but fails AC 5. If a later change adds `relationship(modifyQueryUsing: ...)` (a tried TASK-111 idea), the refill re-queries the DB; re-run AC 6 then. Sort by ABV uses `uasort` + `$set`, keeps item keys, does not save. Lifecycle actions call `refreshFormData(['status'])` only. EntriesRelationManager is not registered; EntryController only creates. TASK-113's judges Select in the same file must stay untouched. `once()` memos in configure() closures persist across Livewire calls in one test: create every member and style before the first mount; create the late entry with `Entry::create` between mount and save.

Unknowns:
- Whether to override via `->saveRelationshipsUsing()` (copying Filament's item create/update/order loop) or a narrower hook; decide in implementation, keeping Filament's create/update/order behaviour for the page's own items. A copied loop needs re-checking on Filament upgrades; say so in a code comment.

---

## Task acceptance criteria (TASK-82)

1. Saving a competition edit page that was opened before another entry was registered keeps that entry, covered by a feature test that fails on main
2. An admin who removed an entry on the page still deletes it on Save (intended deletes keep working)
3. Adding an entry on the page and editing an existing entry's fields still save as before
4. After a Save, a second Save on the same page without changes deletes nothing and duplicates nothing
5. A row added on the page, saved, then removed and saved again is deleted
6. An entry registered while the page is open survives two consecutive Saves from that page and ends up after the page's rows in the tasting order (sort_order)
7. Saving the edit page refuses to remove a style that any entry of the competition still uses (late entries included), with a form error naming the style, and nothing is saved (added 2026-10-14 after Pass B found a late entry could keep a style the same Save detached; Oscar chose to fix it in this task)

## Open decisions

- None.
