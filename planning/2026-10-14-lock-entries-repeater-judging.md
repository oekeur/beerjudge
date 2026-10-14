# Plan: Lock the entries repeater once judging starts

**File:** planning/2026-10-14-lock-entries-repeater-judging.md
**Goal:** TASK-83: from Judging on (including Closed and a reopened Judging), the entries repeater on the competition edit page cannot add, remove, edit or reorder entries, and Sort by ABV is unavailable, enforced on the server and not only hidden in the UI.
**Out of scope:** Any change to bottle-number assignment or to whether bottle numbers follow the tasting order (TASK-91). A way to correct a mistyped entry after Judging (the lock covers field edits too; no override is offered). The `DeleteAction` on the competition edit page (offered in every status; deleting a competition cascades to its entries and scoresheets). Edits to `docs/user-journeys.md`. The public member entry form (already `abort_unless($competition->isOpen(), 403)` in `EntryController`). The unregistered `EntriesRelationManager` (`CompetitionResource::getRelations()` returns `[]`). Any change to the TASK-82 late-entry handling beyond keeping it working while unlocked.
**Constraints:** Lang keys go in both `lang/en.json` and `lang/nl.json` in the same commit. No new dependency. PHPUnit, not Pest.

## Findings from the code (read 2026-10-14)

- The repeater is `Repeater::make('entries')->relationship()` at `app/Filament/Resources/Competitions/Schemas/CompetitionForm.php:112`, with `saveRelationshipsUsing(... self::saveEntries($component))` (TASK-82, commit 59c2b40). `saveEntries` calls `$component->saveToRelationship()` and reads `EditCompetition::$loadedEntryIds`.
- Filament v4 `disabled()` calls `saved(false)` (`vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php:25`); `Repeater::isAddable/isDeletable/isReorderable` return false when `isDisabled()` (`vendor/filament/forms/src/Components/Repeater.php:875-913`); `Component\BelongsToModel` skips `saveRelationships` for a disabled, unsaved component (`vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php:47`, `:78`). Hypothesis until the tamper tests in Task 1 pass: a disabled repeater neither shows the add/delete/reorder controls nor persists submitted state, so a tampered Livewire payload is ignored. `CompetitionStatusGuardTest::test_tampered_status_on_edit_does_not_change_status` proves the same mechanism for the status Select.
- Lock is evaluated per request from `$record` (Livewire rehydrates the model from the database), so a stale tab opened in Open and saved after Move to Judging is locked at Save time and drops its pending entry edits silently.
- `transitionAndRefreshHeader` (Move to Judging, Close) calls `$record->transitionTo()` on `$this->record` and only refreshes `status`; the form re-renders after the action, so a closure reading `$record->status` locks the repeater without a reload. Reopen redirects to the edit URL, so the lock is re-evaluated from the database.
- The Sort by ABV action (`Actions::make([...])->key('entriesActions')`, line ~97) writes the repeater state with `$set('entries', ...)`; with a disabled repeater the reordered state is not saved, but the button would still reorder the visible rows, so it is hidden when locked.
- Existing tests touching the repeater (`CompetitionEntriesSortTest`, `CompetitionEntriesStaleSaveTest`) use status `open`; nothing in them depends on Judging or Closed.

## Phase 1: Lock the entries repeater and Sort by ABV from Judging on

**Outcome:** On the competition edit page, when the competition is Judging or Closed, the entries repeater is read-only with an explanatory hint, Sort by ABV is hidden, and a tampered or stale Save cannot add, delete, edit or reorder entries. Draft and Open behave as before, including TASK-82's stale-save protection.

**Design sketch:** Entries tab unchanged when Draft/Open. When Judging/Closed: rows render as today but every field is disabled; no "Add", delete, drag or move controls; Sort by ABV button absent. The repeater shows a helper text under its label: en "Entries are locked once judging starts: bottle numbers and the tasting order are fixed." (nl equivalent, written in the Dutch register of the neighbouring `competition.*` keys). One key: `competition.entry.locked`.

Tasks:
- [ ] TASK-83 (Task 1): Add `Competition::entriesAreLocked(): bool` (true for `isJudging() || isClosed()`), then in `CompetitionForm` apply `->disabled(fn (?Competition $record): bool => (bool) $record?->entriesAreLocked())` and `->helperText(fn (...) => locked ? __('competition.entry.locked') : null)` to the `entries` Repeater and `->visible(fn (...) => ! locked)` to the `sortByAbv` action; add `competition.entry.locked` to `lang/en.json` and `lang/nl.json`; write ONE new test file (run Judging and Closed through a PHPUnit 12 `#[DataProvider]` attribute, not a `@dataProvider` docblock) `tests/Feature/CompetitionEntriesLockTest.php` (no other task touches it) — done when: `php artisan test --compact --filter=CompetitionEntriesLockTest` passes and covers, each for Judging and for Closed: (a) `assertFormFieldIsDisabled('entries')`; (b) a Livewire test that sets `data.entries` to add a new row, then `call('save')`, leaves `Entry::count()` unchanged; (c) setting `data.entries` to remove a row (unset a record key) then `save` keeps the entry; (d) editing a row's `brewer_notes` or reordering the `data.entries` keys then `save` leaves `brewer_notes` and `sort_order` unchanged in the database; (e) `assertActionDoesNotExist(TestAction::make('sortByAbv')->schemaComponent('entriesActions'))` in Judging and Closed, and `assertActionVisible(...)` with the same target in Open (a hidden schema action is not resolvable, so `assertActionHidden` and the bare action name both fail; see `CompetitionEntriesSortTest` for the target form); (f) in-page lock: mount in Open, call the `moveToJudging` header action, then `set('data.entries', <added row>)` and `save` leaves `Entry::count()` unchanged and `assertFormFieldIsDisabled('entries')` passes without remounting (stale-state case); (g) Reopen keeps the lock: start Closed, call the `reopenJudging` header action, mount the page again, assert `assertFormFieldIsDisabled('entries')` and the (b) add-attempt is rejected; and `vendor/bin/pint --dirty --format agent`, `./vendor/bin/phpstan analyse` (the helperText closure must not declare `?string`: `__()` returns array|string|null) and `php artisan test --compact --filter='CompetitionEntries|CompetitionLifecycle|CompetitionStatusGuard'` all pass with the existing stale-save and sort tests unchanged [AC 1,2]
  Context:
Unknowns:
- Resolved 2026-10-14 by the red-team probe: a disabled `->relationship()` Repeater skips `saveRelationships` and drops tampered state (implemented and run in a scratch copy: tests (b)-(d), (f), (g) pass with the lock and all fail without it). Task 1's tests stay as the regression guard; no explicit guard in `saveEntries` is needed.
- A stale Open-tab Save after the admin moved to Judging in another tab silently drops the entry edits (no notification). Accepted for a ~10-person club; not covered by an AC.
- TASK-91 (bottle numbers vs tasting order, Backlog, unplanned) may later want Sort by ABV to stay available until bottle numbers are assigned; this plan ties the lock to status, which matches today's assignment at Move to Judging. Assumption, no sibling dependency.

---

## Phase 2: Update the specs

**Outcome:** The specs describe the lock.

Tasks:
- [ ] TASK-83 (Task 2): Update the specs that still describe an always-editable repeater: `planning/specs/entries.md` (~line 44, admins edit fields and reorder; also lists a bottle number field the repeater does not have) and `planning/specs/competitions.md` (~line 50, drag-to-reorder repeater) so each states that the entries repeater is read-only from Judging on (Judging, Closed, reopened Judging) and editable in Draft and Open; leave `docs/user-journeys.md` untouched (a dated record; sibling plans edit adjacent lines and TASK-89 treats it as out of scope) — done when: both spec files state the lock, `git diff --stat` shows only those two files, and `rg -n 'no status lock' docs` still matches only the dated journey record [AC 3]
  Context:

---

## Open decisions

**Decided by Oscar, 2026-10-14:** keep the full lock (field edits included) from Judging on.


- [x] Lock field edits as well as add/remove/reorder (planner's call, 2026-10-14): AC 1 says "read-only repeater or equivalent", and a style or member change after judging starts would alter what judges already scored. Oscar can overrule; the cost is that a mistyped entry cannot be fixed from the admin once judging starts.
