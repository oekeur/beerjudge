# Plan: ABV entry property for tasting order

**Goal:** Let members enter an ABV when registering a competition entry; admins see it in Filament (sortable) to guide low-to-high tasting order; ABV is hidden during judging and visible on the results sheet after the competition closes.
**Out of scope:** ABV validation against the style's ABV range from the styles table; auto-sorting entries by ABV; displaying ABV on the judging screen in any form.
**Constraints:** Judging screen already excludes unlisted fields via the controller's explicit field map — no judging-screen changes needed.

---

## Phase 1: Migration and model

**Outcome:** `abv` column exists in the database; Entry model accepts and casts it.

Tasks:
- [x] Run `php artisan make:migration add_abv_to_entries_table` and add `$table->decimal('abv', 4, 1)->nullable();` (supports 0.0–999.9; realistic range enforced at validation layer).
- [x] Run `php artisan migrate`.
- [x] Add `'abv'` to `Entry::$fillable`.
- [x] Add `'abv' => 'float'` to `Entry::$casts`.
- [x] Run `php artisan test --compact --filter=Entry` to confirm no regressions.

Unknowns:
- Decimal precision: `decimal(4,1)` covers up to 999.9% which is overkill but avoids ever needing another migration. One decimal place (e.g. 5.2%) is sufficient for the use case. Confirm this is fine.

---

## Phase 2: Member entry form

**Outcome:** Members see an optional ABV field on `/competitions/{competition}/entries/create`. Submitted value is validated and stored.

Tasks:
- [x] In `StoreEntryRequest`, add rule: `'abv' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,1']`.
- [x] In `resources/views/entries/create.blade.php`, add a numeric input for ABV between the style combobox and the brewer notes field. Label: `__('entry.abv')` with `(optional)` hint. Placeholder: e.g. `5.2`. Step `0.1`.
- [x] Add translation keys `entry.abv` and optionally `entry.abv_hint` to `lang/en.json` and `lang/nl.json`.
- [x] In `EntryController::store()`, confirm `abv` flows through `$request->validated()` (it will automatically, since it's declared in the FormRequest).
- [x] Run entry tests; add assertion that ABV is stored when provided and null when omitted.

Unknowns:
- `decimal:0,1` validation rule requires Laravel 10.13+. Laravel 13 is in use — confirmed fine.
- [RISK] The `old('abv')` restore on validation failure needs a cast guard similar to the existing `old('brewer_notes')` pattern (use `is_numeric(old('abv'))` or just `old('abv')`). Check and apply the same approach.

---

## Phase 3: Filament admin view

**Outcome:** Admins see ABV in the entries table within the Competition edit page, can sort by it, and can set/edit it in the entry form.

Tasks:
- [x] In `EntriesRelationManager::form()`, add `TextInput::make('abv')->numeric()->step(0.1)->minValue(0)->maxValue(100)->nullable()->suffix('%')` after the `brewer_notes` field.
- [x] In `EntriesRelationManager::table()`, add `TextColumn::make('abv')->label('ABV')->suffix('%')->sortable()->placeholder('—')` after the `bottle_number` column.
- [x] Make the table sortable by `abv` by default (or leave default sort as `bottle_number` — admin can click the column header to sort).
- [x] Run `php artisan test --compact` to confirm no Filament regressions.

Unknowns:
- The table's current default sort is `bottle_number`. Changing it to `abv` would be a behaviour change for admins who rely on bottle-number order. Leaving it as-is (admin clicks ABV column header) is safer.

---

## Phase 4: Results sheet

**Outcome:** ABV appears in the member's own-entries section of the results sheet once the competition is closed.

Tasks:
- [x] In `resources/views/results/show.blade.php`, add ABV to the own-entries section alongside style and score. A simple inline label (`{{ $entry->abv ? $entry->abv . '% ABV' : '' }}`) is sufficient.
- [x] Also add ABV to the full standings section (decided: show everywhere post-close).
- [x] Smoke-test: create a closed competition with ABV-annotated entries and verify the results sheet renders correctly.

Unknowns:
- [RISK] The full standings section already reveals brewer names. Showing ABV there too is debatable — it adds information but is not sensitive post-close. Decision below.

---

## Open decisions

- [x] **ABV in full standings:** Show everywhere post-close — in both own-entries and full standings. (Decided 2026-06-24)
- [x] **ABV hint text:** Add hint below the ABV input: "Used to order tastings — not shown to judges." Translated in both locales. (Decided 2026-06-24)
