# Plan: Merge judging tab into dashboard

**Goal:** Move the judge select and invite link from the Judging tab into the Dashboard tab, discard the progress matrix, and remove the Judging tab entirely.
**Out of scope:** Changing the `ResultsSummaryWidget` content, altering any routes, modifying the judging screen itself.
**Constraints:** The judge select must remain a form field (it writes to the database); it cannot live inside the Livewire widget.

---

## Phase 1: Restructure `CompetitionForm`

**Outcome:** The Dashboard tab contains the judge select, the invite link, and the results summary widget. The Judging tab is gone.

Tasks:
- [x] Move `Select::make('judges')` from the Judging tab schema to the top of the Dashboard tab schema
- [x] Move the judge invite `Html::make()` (the copyable link) from the Judging tab to the Dashboard tab, below the select
- [x] Remove `Livewire::make(JudgingProgressWidget::class)` from the form entirely
- [x] Delete the entire `Tab::make(__('competition.tab.judging'))` block
- [x] Run `vendor/bin/pint --dirty` and `php artisan test --compact`

Unknowns:
- [RISK] Filament form fields (`Select`) and Livewire schema components (`Livewire::make`) in the same tab — the select must stay outside the widget because it's bound to form state. Verify the ordering renders correctly (select → invite link → widget).
- The `competition.tab.judging` translation key becomes unused; decide whether to delete it now or leave it.

---

## Phase 2: Delete unused code

**Outcome:** `JudgingProgressWidget`, its blade view, and the orphaned translation keys are removed.

Tasks:
- [x] Delete `app/Filament/Resources/Competitions/Widgets/JudgingProgressWidget.php`
- [x] Delete `resources/views/filament/competition/judging-progress.blade.php`
- [x] Remove unused `competition.tab.judging` translation key from all lang files
- [x] Remove unused `competition.judging_progress.*` translation keys from all lang files
- [x] Remove the `JudgingProgressWidget` import from `CompetitionForm.php`
- [x] Run `php artisan test --compact` to confirm no regressions

Unknowns:
- Are the `judging_progress.*` translation keys used anywhere else (e.g. other views, emails)? Grep before deleting.

---

## Open decisions

- [ ] Rename the "Dashboard" tab now that it absorbs judging management — or keep the name?
