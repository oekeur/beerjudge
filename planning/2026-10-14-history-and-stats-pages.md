# Plan: Add history and stats pages (TASK-31)

**File:** planning/2026-10-14-history-and-stats-pages.md
**Goal:** Give admins three read-only Filament pages with chart widgets (club-wide stats, per-member history, per-style distributions) computed from the existing tables.
**Out of scope:**
- The member-facing "my history" page outside Filament: tracked as TASK-32 (roadmap calls it optional; backlog already splits it out).
- Any schema change, cache layer or materialized table: the PRD says plain queries are fine at this scale and caching waits until something feels slow.
- Stats for competitions that are not `closed` (see Definitions).
- Judge-side stats (judge drift, judge averages): TASK-34/35/36 own those.
- Per-style placement or category rankings (`planning/specs/results.md` Out of scope stays true: nothing here ranks within a style).
**Constraints:**
- Reads existing tables only (`competitions`, `entries`, `scoresheets`, `styles`, `members`).
- Every new translation key goes into both `lang/en.json` and `lang/nl.json` in the same commit (dotted, area-grouped: `stats.*`). The shared rules say TASK-107 adds a parity test (identical key sets); verified 2026-10-14 that no such test exists in `tests/` yet and TASK-107's own ACs do not mention it. This plan does not depend on it: each page test asserts its own new keys exist in both files (see AC 6), and none writes a general parity test. Band labels reuse existing `judging.band.<key>.name` keys.
- Filament v4 namespaces, static `make()`, PHPUnit tests (no Pest), `vendor/bin/pint --dirty --format agent`, PHPStan clean on `app/`.
- Admin-only: `Member::canAccessPanel()` returns `is_admin`; Filament's `Authenticate` aborts 403 for an active non-admin (verified 2026-10-14), so pages inherit that gate.
- This panel has no dashboard: `->pages([])`, no `app/Filament/Pages` yet, and `/admin` redirects to `/admin/competitions` (`AdminPanelAccessTest`). Do not register `Filament\Pages\Dashboard`; the new pages stay in a navigation group so the redirect target does not change.
- Date ordering: `judging_date` is nullable. Sort closed competitions in PHP by `judging_date` ascending, nulls last, then `id`, so SQLite and Postgres agree.

**Assumptions about siblings (wave of 2026-10-14):**
- TASK-107/108/109 and others write `lang/en.json` and `lang/nl.json`. This plan appends keys only, in its own `stats.*` block, and assumes it is rebased after them. Expect a textual merge conflict in both JSON files if they land in another order.
- TASK-38 (historical backfill importer) may add old competitions with entries and scoresheets but possibly no `bottle_number` and no `competition_judge` rows. The stats queries must not depend on either.
- TASK-32 (my history) would reuse the service from Phase 1; this plan does not wait for it and does not build for it beyond keeping the service methods member-parameterised.
- TASK-34/36 (judge drift, variance threshold) may add `CompetitionRankingService` methods; this plan reads `rank()` only and does not edit that class.

## Acceptance criteria (mirrored on TASK-31)

1. `HistoryStatsService` returns the club, member and style aggregates defined below from closed competitions' submitted scoresheets only, covered by passing tests.
2. An admin-only "Club stats" Filament page shows winner per competition, participation, score distribution and most-entered styles, with chart widgets and an empty state.
3. An admin-only "Member history" Filament page shows a chosen member's entries across competitions, score trend, best results and average by style.
4. An admin-only "Style stats" Filament page shows per-style entry counts and score average/min/max, plus a per-style trend across competitions.
5. An active non-admin member gets 403 on each new page.
6. Every new translation key exists in both `lang/en.json` and `lang/nl.json`, asserted in each page's test.
7. The specs and roadmap no longer list per-member history or style statistics as unbuilt, and document the winner, scored-entry and average definitions.

## Definitions (settled here, used by every phase)

- **Counted competition:** `status = closed`. Results are final and members may see them; admins see live results on the existing Results tab already.
- **Scored entry:** an entry with at least one `submitted` scoresheet. Its score is `avg_total` from `CompetitionRankingService::rank()` (average of submitted totals, rounded to 2). Every average, min and max in this plan is taken over scored entries' `avg_total` values (mean of entry scores, not of individual sheet totals); min/max are per entry. `entrants` (the denominator of "3 / 12") = scored entries in that competition. Entries with no submitted sheet are excluded from every average, distribution and placement (the ranking service would otherwise give them a 0), but still count toward "entries registered" and "most-entered styles".
- **Placement and winner:** `placement` from `rank()` (descending `avg_total`, then `avg_overall`, competition-wide). Winner = placement 1, the top overall entry, consistent with v1 ranking. `rank()` assigns placement by sort index, so a full tie on both averages gets distinct placements; the stats expose `is_tied_for_first` (placement 1 and 2 have equal `avg_total` and `avg_overall`) so the UI can show a "tied" badge instead of hiding it.
- **Distribution buckets:** the six score bands from `rank()` (`band_key`), counted over scored entries.
- **Participation per competition:** distinct members with at least one entry; entry count; distinct judges with at least one submitted sheet.
- **Style identity:** `styles.id` (same name under different `source` is a different style); labels show `code name`.

## Phase 1: Stats service

**Outcome:** `App\Services\HistoryStatsService` returns plain arrays for all three surfaces, tested against fixtures, no UI yet. Shippable alone (inert, no routes).

**Interface contract:** (all methods return arrays, no Eloquent models, so widgets and tests assert plain values)
- `competitionSummaries()` (ordered as in Constraints): Collection<array{competition_id:int, name:string, year:int, judging_date:?string, entries:int, scored_entries:int, participants:int, judges:int, average_total:?float, winner:?array{entry_id:int, member_id:int, member_name:string, style_label:string, avg_total:float, is_tied_for_first:bool}}>`  (closed only; `average_total` is the mean of scored entries' `avg_total`).
- `scoreDistribution(): array<string,int>` keyed by band key, all six keys always present.
- `mostEnteredStyles(int $limit = 10): Collection<array{style_id:int, label:string, entries:int}>` (counts all entries in closed competitions).
- `memberHistory(Member $member): array{rows: list<array{competition_id:int, competition_name:string, judging_date:?string, entry_id:int, style_label:string, avg_total:?float, placement:?int, entrants:int}>, trend: list<array{competition_id:int, name:string, average_total:float}>, best: list<row> (top 5 by avg_total), by_style: list<array{style_id:int, label:string, entries:int, average_total:float}>}`.
- `styleStats(): Collection<array{style_id:int, label:string, scored_entries:int, competitions:int, average_total:float, min_total:float, max_total:float}>` ordered by `scored_entries` desc, and `styleTrend(Style $style): list<array{competition_id:int, name:string, average_total:float, scored_entries:int}>`.
- Implementation reuses `CompetitionRankingService::rank()` once per closed competition (4 competitions a year; no caching). Constructor-inject the ranking service.

Tasks:
- [ ] TASK-new-1 Build `HistoryStatsService` with the methods above and `tests/Feature/HistoryStatsServiceTest.php` — done when: `php artisan test --compact --filter=HistoryStatsServiceTest` passes with cases for: a non-closed competition contributes nothing; an entry without submitted sheets is excluded from averages and distribution but counted in entries and most-entered styles; a draft-status sheet is ignored; winner is the highest `avg_total` and a full tie sets `is_tied_for_first`; `scoreDistribution()` always has six keys; `memberHistory` returns `best` sorted desc and `by_style` averages per style; `styleStats` separates two styles with the same name and different `source`; a closed competition with zero scored entries gives `winner = null` and `average_total = null`; backfilled-shaped data (null `bottle_number`, no `competition_judge` rows) still counts. [AC 1]
  Context: verified 2026-10-14: `CompetitionRankingService::rank()` sets `placement` by sort index (ties get distinct placements) and returns every entry including those with `sheet_count` 0 (avg 0); `Competition` status is `App\Enums\CompetitionStatus`; scoresheet status is `App\Enums\ScoresheetStatus`.

Unknowns:
- None blocking. Ties for first are surfaced, not resolved (the v1 ranking has no co-winner concept).

---

## Phase 2: Club stats page

**Outcome:** Admins open "Club stats" in the Filament panel and see winner per competition, participation, score distribution and most-entered styles. Shippable alone.

**Design sketch:**
- Nav group "Statistics" (`stats.nav_group`; created here, reused by Phases 3 and 4, which therefore run after this phase), page "Club stats" at `/admin/club-stats`.
- Header widgets row: `StatsOverviewWidget` with four tiles (closed competitions, total entries, distinct brewers, club average score).
- Charts: bar chart "Score distribution" (six bands, labels from the existing band translation keys if present, else new `stats.band.*`), bar chart "Most entered styles" (top 10, horizontal), line chart "Participation" (entries and brewers per competition over time).
- Table widget "Winners": competition, date, winner, style, score, "tied" badge when `is_tied_for_first`.
- Empty state: with no closed competition each widget renders an empty state line (`stats.empty`, created here, reused later), no exception.
- Widgets set `protected static bool $isDiscovered = false;` so `discoverWidgets(app_path('Filament/Widgets'))` does not auto-place them (verified 2026-10-14: the flag exists and undiscovered widgets are still registered with Livewire, so page-embedded use works). There is no dashboard to pollute.

Tasks:
- [ ] TASK-new-2 Add `app/Filament/Pages/ClubStats.php` and widgets under `app/Filament/Widgets/Stats/` (overview, winners table, distribution, most-entered styles, participation), all `$isDiscovered = false`, plus `lang/en.json` and `lang/nl.json` keys and `tests/Feature/ClubStatsPageTest.php` — done when: `php artisan test --compact --filter=ClubStatsPageTest` passes: an admin GET `/admin/club-stats` returns 200 and sees the winner's name for a seeded closed competition; an active non-admin member gets exactly 403; with zero closed competitions the page returns 200; the test asserts every new `stats.*` key it introduces exists in both `lang/nl.json` and `lang/en.json`. [AC 2,5,6]
  Context:

Unknowns:
- None open. Verified 2026-10-14: Chart.js ships with `filament/widgets` (`public/js/filament/widgets/components/chart.js` present, `filament:upgrade` runs in composer post-autoload), so no new dependency or deploy step.

---

## Phase 3: Per-style stats page

**Outcome:** Admins see how each style scores across competitions, and drill into one style's trend. Shippable alone.

**Design sketch:**
- Page "Style stats" at `/admin/style-stats`, same nav group.
- Table widget of all styles with at least one scored entry: style, scored entries, competitions, average, min, max (sortable, default by scored entries desc).
- Page filter (a `Select` of those styles, optional) drives a line chart "Average score per competition" for the chosen style. Mechanism, verified 2026-10-14 by reading vendor: a plain `Page` using `Filament\Pages\Dashboard\Concerns\HasFiltersForm` gets a `filters` property, and `Page::getWidgetsSchemaComponents()` passes it to widgets as `pageFilters` (widgets use `InteractsWithPageFilters`). The page must render the filter form itself (as `Dashboard` does, `EmbeddedSchema::make('filtersForm')` in `content()`). Set `$persistsFiltersInSession = false` so a bare visit shows the unfiltered state. No style selected: chart shows the club-wide average per competition.

Tasks:
- [ ] TASK-new-3 Add `app/Filament/Pages/StyleStats.php`, its table and chart widgets (`$isDiscovered = false`), lang keys in both files and `tests/Feature/StyleStatsPageTest.php` — done when: `php artisan test --compact --filter=StyleStatsPageTest` passes: table lists only styles with scored entries and shows the correct average for a seeded style; a trend widget given `pageFilters = ['style' => <id>]` renders that style's per-competition averages (mean of scored entries' `avg_total`; a closed competition with a null `judging_date` sorts last); fixtures attach each style to the competition via `competition_style` or `Entry::saving` throws (verified 2026-10-14); two same-name styles from different sources appear as two rows; non-admin cannot reach the page; the test asserts its new keys exist in both lang files. [AC 4,5,6]
  Context:

Unknowns:
- None open (filter mechanism resolved above). `ChartWidget::getData()` is protected: tests assert through a rendered Livewire widget test with `pageFilters` set, not by widening visibility.

---

## Phase 4: Per-member history page

**Outcome:** Admins pick a member and see their entries across competitions, score trend, best results and average by style. Shippable alone.

**Design sketch:**
- Page "Member history" at `/admin/member-history`, same nav group.
- Page filter: `Select` of members (searchable, those with at least one entry in a closed competition), same `HasFiltersForm` mechanism as Phase 3, `$persistsFiltersInSession = false`. Preselect link form is `?filters[member]=<id>` (the `$filters` property is `#[Url]`), so a later task can link from the Members resource (not built here).
- Widgets: table "Entries" (competition, date, style, score, placement as `3 / 12`), line chart "Score trend" (average of the member's scored entries per competition), table "Best results" (top 5), bar chart or table "Average by style".
- No member selected: prompt `stats.select_member`, no queries run.

Tasks:
- [ ] TASK-new-4 Add `app/Filament/Pages/MemberHistory.php` with its widgets (`$isDiscovered = false`), lang keys in both files and `tests/Feature/MemberHistoryPageTest.php` — done when: `php artisan test --compact --filter=MemberHistoryPageTest` passes: with a member selected, the entries table shows one row per entry in closed competitions only (an entry in an `open` competition is absent); trend widget dataset equals the per-competition mean of that member's scored entries; best results are sorted desc and capped at 5; no member selected renders the prompt and 200; `/admin/member-history?filters[member]=<id>` preselects the member; a member with no entries yields empty states; an active non-admin gets 403; the test asserts its new keys exist in both lang files. [AC 3,5,6]
  Context: fixtures need `competition_style` rows for every entry's style (`Entry::saving` throws otherwise; verified 2026-10-14).
- [ ] TASK-new-5 Update the specs that list this feature as unbuilt: `planning/specs/members.md` (Out of scope: per-member history), `planning/specs/results.md` (Out of scope: per-member score history; add a short "History and stats" section with the Definitions above), `planning/specs/styles.md` (Out of scope: style statistics), and mark `planning/roadmap.md` section 2 built for the admin pages (member-facing stays TASK-32) — done when: `grep -n "see roadmap" planning/specs/members.md planning/specs/results.md planning/specs/styles.md` shows no line claiming per-member history or style statistics is unbuilt, and results.md states the winner, scored-entry and average definitions. [AC 7]
  Context: docs-only; CLAUDE.md commits docs-only changes straight to main.
  Context:

Unknowns:
- Reuses Phase 3's page-filter pattern, so it runs after Phase 3.

---

## Open decisions

- [ ] Whether TASK-32 (member-facing "my history") should ship soon after; this plan keeps `memberHistory()` member-parameterised so it can reuse it. No action needed here.
- [ ] Tied first place: shown as a badge only. A co-winner rule would be a ranking-service change, out of scope.
