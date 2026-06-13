# Plan: Blind Guess Competition Type

## Context

The app currently supports one competition format: BJCP 50-point scoresheet judging. This adds a second format ("blind guess") where the brewer and style are hidden from the judge, who instead guesses the style, brewer, and five vital statistics (OG, FG, ABV, IBU, SRM). Points are awarded by comparing guesses to the actual entry data after the competition closes.

The lifecycle, status transitions (draft → open → judging → closed), and offline-first judging screen are all reused unchanged. The key additions are: a `type` discriminator on competitions, a `blind_guesses` table, a new controller mirroring `ScoresheetController`, and conditional branching in the judging screen (both server and client) to hide brewer/style identity.

---

## Database

### Migration 1 — add `type` to competitions
```php
$table->enum('type', ['bjcp', 'blind_guess'])->default('bjcp')->after('status');
```
Existing rows get default `bjcp`. `down()` drops the column.

### Migration 2 — create `blind_guesses` table
```
id
entry_id          FK → entries (cascade delete)
judge_id          FK → members (restrict delete)
guessed_style_id  FK → styles (null on delete), nullable
guessed_member_id FK → members (null on delete), nullable
guessed_og        decimal(5,3), nullable
guessed_fg        decimal(5,3), nullable
guessed_abv       decimal(4,2), nullable
guessed_ibu       decimal(5,1), nullable
guessed_srm       decimal(5,1), nullable
client_uuid       char(36), unique
status            enum('draft','submitted'), default 'draft'
submitted_at      timestamp, nullable
timestamps
unique(entry_id, judge_id)
```
Decimal precisions match the `styles` table (see `2026_06_10_200737_change_float_columns_to_decimal_in_styles_table.php`).

---

## Scoring

Extract a standalone `app/Services/BlindGuessScorer.php` class with one `static score(BlindGuess $guess): array` method. This keeps scoring testable without reflection.

Rules (max 45 pts):
- Correct `guessed_style_id === entry.style_id`: **10 pts**
- Correct `guessed_member_id === entry.member_id`: **10 pts**
- Each vital stat (OG, FG, ABV, IBU, SRM): **5 pts each** if the guess is ≥ `style.{stat}_min` AND ≤ `style.{stat}_max`; null guess or null style range = 0 pts

Scoring is computed on read (never stored), so rule changes don't require a backfill.

---

## Models

**`Competition`** — add `'type'` to `$fillable`; add helpers `isBlindGuess(): bool` and `isBjcp(): bool`.

**`BlindGuess`** (new) — fillable mirrors all `blind_guesses` columns; relationships: `entry()`, `judge()` (→ Member via judge_id), `guessedStyle()`, `guessedMember()`.

**`Entry`** — add `blindGuesses(): HasMany` relationship.

---

## Backend

### `StoreBlindGuessRequest` (new)
Validates: `client_uuid` (UUID), `entry_id` (exists:entries), style/member IDs (nullable exists), five nullable numeric stats with generous bounds, `status` (in:draft,submitted).

### `BlindGuessController` (new)
Single `upsert()` method mirroring `ScoresheetController::upsert()`:
1. Abort if competition not `judging`
2. Abort if competition type is not `blind_guess`
3. Abort if judge is the entry's brewer
4. Find existing row by `client_uuid` first, then `(entry_id, judge_id)` — create or update
5. Set `submitted_at` to `now()` on submission
6. Return JSON: `{ id, client_uuid, status, saved_at }`

### `JudgingController::show()` — branching
After the existing `abort_if` and entry query, branch on `$competition->isBlindGuess()`:

**BJCP path** — existing logic unchanged; pass `$descriptors` to view.

**Blind guess path** — builds `$serverData` with:
- `competitionType: 'blind_guess'`
- `entries`: bottle_number only — **no** `brewer_name`, `style_code`, `style_name`, or `style_guide`
- `existingGuesses`: keyed by entry_id (all guess fields)
- `styles`: competition's styles — `[{id, code, name}]` — for the dropdown
- `members`: members who have entries in this competition — `[{id, name}]` — for the dropdown

The omission of identity fields from the entry objects is the server-side enforcement. No client-side hiding is relied upon for security.

### `ResultsController` — branching
Add branch in `show()` and `index()` on `$competition->isBlindGuess()`:
- `show()` → call `buildBlindGuessRankedJudges()` and render `results.blind_guess_show`
- `index()` → pass `$competition->type` to the existing index view so it can link to the right show page

Add private method `buildBlindGuessRankedJudges(Competition $competition): Collection`:
- Loads submitted blind guesses for the competition (with `entry.style`, `entry.member`, `judge`)
- Groups by `judge_id`, calls `BlindGuessScorer::score()` per guess, sums totals
- Sorts descending by total points, adds `placement`

### Route (new)
Inside the existing `['auth', 'active-member']` middleware group in `routes/web.php`:
```php
Route::post('/blind-guesses', [BlindGuessController::class, 'upsert'])->name('blind-guesses.upsert');
```

---

## Filament

**`CompetitionForm`** — add a `Select::make('type')` with options `['bjcp' => 'BJCP Scoring', 'blind_guess' => 'Blind Guess']`, `->default('bjcp')->required()`, placed after the `status` select.

---

## Judging screen

### `judging/show.blade.php`
1. **Entry header** — wrap `brewer_name` + `style_name` display in `x-show="competitionType === 'bjcp'"`. For blind guess, show only "Bottle #N".
2. **Score display** (`/ 50`, score band) — gate with `x-show="competitionType === 'bjcp'"`.
3. **Editable section** — add a second `<template x-if>` block for `competitionType === 'blind_guess'` containing: style dropdown (`x-for="s in styles"`), brewer dropdown (`x-for="m in members"`), five numeric inputs for vital stats, same save/submit controls. Keep existing BJCP block gated on `competitionType === 'bjcp'`.
4. **Submitted read-only** — for blind guess, show "Submitted — results available after competition closes" instead of score breakdown.
5. The `$descriptors` variable is only passed in the BJCP path; guard the descriptors section accordingly.

### `judging.js`
1. Expose top-level state: `competitionType`, `styles`, `members` from `serverData`.
2. Add `emptyGuess(entryId)` factory returning the blind guess field structure (all guess fields null, `client_uuid` via `crypto.randomUUID()`).
3. In `init()`, pick the right factory based on `competitionType`.
4. In `syncToServer()`, branch on `competitionType` to pick endpoint (`/blind-guesses` vs `/scoresheets`) and build the correct request body.
5. IndexedDB store (`scoresheets` via Dexie) can be reused — schema is index-only, Dexie stores arbitrary objects. No version bump needed.

---

## Results view

**`resources/views/results/blind_guess_show.blade.php`** (new Blade file):
- Header: competition name, member name
- "Your guesses" table (only shown if the viewer is the judge): per entry — Bottle # | Style guess vs actual | Brewer guess vs actual | OG/FG/ABV/IBU/SRM guess vs range | Points per category | Row total
- "Leaderboard": ranked table — Place | Judge name | Total points
- Print button (same pattern as `results/show.blade.php`)

---

## Tests

### `tests/Unit/BlindGuessScorerTest.php`
- Correct style → 10 pts; wrong → 0
- Correct brewer → 10 pts; wrong → 0
- Stat within range (inclusive boundaries) → 5 pts; outside → 0
- Null guess → 0; null style range → 0
- All correct → 45 pts total
- Total equals sum of categories

### `tests/Feature/BlindGuessUpsertTest.php`
- Judge can save draft and submit
- Idempotent on `client_uuid`
- Judge cannot guess own entry (422)
- Rejected when competition not in `judging` (422)
- Rejected for BJCP competition type (422)
- Unauthenticated → redirect
- Nullable fields accepted (only uuid/entry_id/status required)
- Draft does not set `submitted_at`; submitted does

### `tests/Feature/BlindGuessJudgingScreenTest.php`
- Screen loads for blind_guess competition
- Response HTML does not contain `"brewer_name"` (security regression guard)
- Response HTML does not contain `"style_name"` (security regression guard)
- Response contains `"styles"` and `"members"` arrays in serverData
- BJCP competition still includes brewer_name (regression guard)

### `tests/Feature/BlindGuessResultsTest.php`
- Results show correct point totals per judge
- Results hidden before `published_at` for non-admin
- Admin can view results before publish

---

## Files changed

| | File |
|---|---|
| New migration | `database/migrations/..._add_type_to_competitions_table.php` |
| New migration | `database/migrations/..._create_blind_guesses_table.php` |
| Modify | `app/Models/Competition.php` |
| New | `app/Models/BlindGuess.php` |
| Modify | `app/Models/Entry.php` |
| New | `app/Services/BlindGuessScorer.php` |
| New | `app/Http/Requests/StoreBlindGuessRequest.php` |
| New | `app/Http/Controllers/BlindGuessController.php` |
| Modify | `app/Http/Controllers/JudgingController.php` |
| Modify | `app/Http/Controllers/ResultsController.php` |
| Modify | `routes/web.php` |
| Modify | `app/Filament/Resources/Competitions/Schemas/CompetitionForm.php` |
| Modify | `resources/views/judging/show.blade.php` |
| Modify | `resources/js/judging.js` |
| New | `resources/views/results/blind_guess_show.blade.php` |
| New | `tests/Unit/BlindGuessScorerTest.php` |
| New | `tests/Feature/BlindGuessUpsertTest.php` |
| New | `tests/Feature/BlindGuessJudgingScreenTest.php` |
| New | `tests/Feature/BlindGuessResultsTest.php` |

---

## Verification

1. `php artisan test` — all green on baseline before any changes
2. `php artisan migrate` + `php artisan migrate:rollback` — both directions clean
3. `php artisan test --filter BlindGuess` — all new tests pass
4. Manual: create blind_guess competition in Filament, set to judging, log in as judge, confirm dropdowns populate, sync works, submit locks form
5. DevTools: confirm no `brewer_name`/`style_name` keys appear anywhere in the page HTML source for blind_guess competitions
6. Manual: close competition, view results — check point totals are correct
7. `php artisan test` — full suite green (BJCP path unaffected)
