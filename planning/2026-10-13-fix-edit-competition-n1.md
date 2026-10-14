# Plan: Remove the per-entry queries from the competition edit form

**File:** planning/2026-10-13-fix-edit-competition-n1.md
**Goal:** Saving or loading the competition edit page runs a fixed number of members and styles queries, independent of the number of entries (Sentry issue 152596637).
**Out of scope:** the `Entry::saving` competition_style exists guard (one query per saved entry, kept as the style-belongs-to-competition invariant); Filament's per-row `update entries set sort_order` writes (expected when rows are reordered). Both confirmed by Oscar 2026-10-13.
**Constraints:** no new dependencies; Filament v4 internals are not overridden.

---

## Phase 1: Memoize the entry row Select options

**Outcome:** the entries Repeater's `member_id` and `style_id` Selects read their options from one per-request memoized query each. A save with N entries runs zero `members where id = ?` lookups and one styles options query, and a regression test pins the counts.

Measured baseline (probe, 2026-10-13, 6 entries): save runs 24 `select * from "styles" where "id" in (...) order by "code" asc` (4 per entry) and 18 `select * from "members" where "members"."id" = ? limit 1` (3 per entry); mount runs 12 of the styles query (2 per entry).

Tasks:
- [ ] TASK-111: In `CompetitionForm.php`, replace `Select::make('member_id')->relationship('member', 'name')` with `->options()` from a `once()`-memoized query of all members (inactive included, so existing entries still validate) ordered by name, and wrap the `style_id` options query in a `once()`-memoized helper keyed on the style-id array. Add a feature test that mounts and saves EditCompetition and counts queries via `DB::listen`. Done when (task ACs 1-7):
  - the test's competition has styles attached and every member and entry exists before the first mount;
  - each scenario (2 entries, 6 entries) is its own test method, and `Once::flush()` runs immediately before the measured mount and again before the measured `call('save')`, so each phase is measured cold;
  - on save, the count of queries containing `from "members" where "members"."id" = ?` is 0, and the count of queries matching `from "styles" where "id" in (` with `order by "code"` is exactly 1 in both scenarios;
  - on mount, the same styles count is exactly 1 in both scenarios;
  - the count of `competition_style` exists queries on save equals the entry count (guard untouched);
  - `CompetitionEntriesSortTest`, `CompetitionStatusGuardTest`, `CompetitionResultsEmailTest` and the new test pass; phpstan and pint are clean;
  - journey A3 (`scripts/journeys/a3-manage-entries.mjs`) passes, which shows the member picker still opens on "Select an option", filters on typed "Member" and saves the picked option.
  Context: verified 2026-10-13:
  - The per-entry queries come from `Repeater::saveToRelationship` (vendor/filament/forms/src/Components/Repeater.php:1004) calling `$item->getState()`, which runs `validate()` per row. The relationship Select's `in` rule calls `getOptionLabel()` -> `getSelectedRecord()` (Select.php:803-859), which skips its query only when the row record has `member` loaded. During the save loop it does not, even with `->with('member')` eager-loaded on the Repeater (probe: 18 -> 6). With `->options()` and no `getOptionLabelUsing`, the label comes from `getOptions()`, so no per-row record query remains; a value missing from the options gives a blank label and fails the `in` rule.
  - `once()` keys on file + class + function + line plus the closure's `use` variables (`Onceable::hashFromTrace`). Capture only the style-id array. An object (`$get`, a query builder, a component) is keyed by `spl_object_hash` and defeats the memo, and an arrow fn auto-captures every variable it uses, so `once(fn () => $query->get())` fails silently. Build the query inside the memoized closure.
  - The closures in `configure()` are static, so the memo sits on the global `Once` instance and persists across a Livewire test's mount and `call('save')` in one process. Only test teardown calls `Once::flush()`. In production every HTTP request is a fresh memo.

Unknowns:
- Member search on the entry row becomes client-side over all members. Fine at ~10 members. Journey A3 is the check that the picker behaves the same; manual browser verification of the picker is still required if A3 cannot run.
- The style options closure reads `$get('../../styles')`, which reflects unsaved form state during a save request. The memo key includes that array, so a changed style set gives a fresh key. Nothing else on this form creates styles or members mid-request.

---

## Open decisions

None. Memoized options over eager-loading for the member Select: chosen by Oscar 2026-10-13.
