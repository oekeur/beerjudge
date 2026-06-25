# Plan: Live-polling competition dashboard widgets

**Goal:** Replace the three static `Html::make()` views on the competition edit page with auto-refreshing Livewire widgets so admins see scoresheet submissions in real time.
**Out of scope:** WebSockets / broadcasting, changing any data-model or route, altering the judging screen itself.
**Constraints:** Must stay inside the existing tab structure in `CompetitionForm`. Polling interval can be configurable per widget.

---

## Phase 1: Create `JudgingProgressWidget`

**Outcome:** The "Judging" tab's progress matrix auto-refreshes every 10 seconds via a Livewire widget instead of a static `Html::make()` render.

Tasks:
- [ ] Run `php artisan make:livewire Filament/Resources/Competitions/Widgets/JudgingProgressWidget --no-interaction` (or equivalent Filament command) to scaffold the class
- [ ] Move the data query logic from `judging-progress.blade.php` into the widget's `render()` method (or a `getViewData()` helper)
- [ ] Set `protected int $pollingInterval = 10` on the widget class
- [ ] Create / repurpose `resources/views/filament/competition/judging-progress-widget.blade.php` as the widget view — keep the existing table markup unchanged
- [ ] Add `public int $competitionId` property; populate it from the `$competition->id` passed via `->properties()`

Unknowns:
- [RISK] Does `\Filament\Schemas\Components\Livewire::make()` exist in Filament v4, and what is the exact API for passing properties? Verify with `search-docs` before coding.
- Does Filament's polling mechanism use `wire:poll` under the hood, or does it require a dedicated `$pollingInterval` constant? Check widget base class.

---

## Phase 2: Create `ResultsSummaryWidget`

**Outcome:** The "Dashboard" tab's stats grid and per-entry progress table auto-refreshes every 10 seconds.

Tasks:
- [ ] Scaffold `ResultsSummaryWidget` (same pattern as Phase 1)
- [ ] Move query from `results-summary.blade.php` into the widget
- [ ] Set polling interval
- [ ] Add `public int $competitionId` property
- [ ] Create widget blade view from existing `results-summary.blade.php` markup

Unknowns:
- None beyond the Phase 1 Livewire embedding risk (resolved by then).

---

## Phase 3: Add polling to `JudgeVarianceWidget`

**Outcome:** The "Results" tab variance table also auto-refreshes. The duplicate `variance-tab.blade.php` static render is replaced by the existing widget class.

Tasks:
- [ ] Add `protected int $pollingInterval = 10` to `JudgeVarianceWidget`
- [ ] Replace the `Html::make()` call in the Results tab with `Livewire::make(JudgeVarianceWidget::class)->properties([...])`
- [ ] Delete (or archive) `resources/views/filament/forms/components/variance-tab.blade.php` once confirmed unused

Unknowns:
- `JudgeVarianceWidget` extends `TableWidget` — verify `$pollingInterval` works on `TableWidget` subclasses the same way as view widgets.

---

## Phase 4: Wire up widgets in `CompetitionForm`

**Outcome:** All three tabs use `Livewire::make()` components; `Html::make()` is removed from these tabs entirely.

Tasks:
- [ ] Import `\Filament\Schemas\Components\Livewire` in `CompetitionForm`
- [ ] Replace the `Html::make(fn (?Competition $record) => ...)` closures in the Judging, Dashboard, and Results tabs with `Livewire::make(WidgetClass::class)->key('unique-key-{$record->id}')->properties(['competitionId' => $record->id])`
- [ ] Guard the `->visibleOn('edit')` equivalent — only show when `$record->exists`
- [ ] Run `php artisan test --compact` to confirm no regressions
- [ ] Run `vendor/bin/pint --dirty` to clean up formatting

Unknowns:
- [RISK] `Livewire::make()` inside a Filament `Tab` may need a stable `->key()` to avoid Livewire re-mounting on tab switch. Verify behaviour in browser.

---

## Open decisions

- [ ] Polling interval: 10 seconds proposed — confirm with user or make it configurable per widget.
- [ ] Whether to keep the old blade templates around as fallback views or delete them after extraction.
