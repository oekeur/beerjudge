# BeerJudge V1 — Full Scaffold Plan

## Context

Greenfield Laravel app for a 10-person homebrew club. Runs four competitions per year. No existing code; build from scratch against the PRD at `planning/requirements/homebrew-competition-prd.md`. The goal is a fully working V1: members registered by admin, beers entered, blind judging on phones, results published. Two frontends: Filament for admin, a custom Blade+Alpine screen for judging.

---

## Implementation Phases

Work through these in order. Phases 1–4 are backend-only and can be verified without a browser. Phases 5–6 are frontend. Phase 7 is seeding and tests.

---

### Phase 1 — Laravel Scaffold + Auth Foundation

**Why first:** Filament's generated code references the authenticatable model. Swap User for Member before `filament:install` or you'll find-replace throughout.

Commands:
```bash
composer create-project laravel/laravel beerjudge
cd beerjudge
composer require filament/filament:"^3.0" -W
npm install alpinejs dexie
```

Set in `.env`:
```
SESSION_LIFETIME=10080   # 7 days in minutes
```

Files to create:
- `database/migrations/0001_01_01_000000_create_members_table.php` — id, name, email (unique), password, bjcp_rank (nullable), is_admin (bool default false), is_active (bool default true), remember_token, timestamps
- `app/Models/Member.php` — extends `Authenticatable`, implements `FilamentUser`. `canAccessPanel(Panel $panel): bool` returns `$this->is_admin`.
- Delete default `app/Models/User.php` and the default users migration.
- Update `config/auth.php` providers.users.model → `App\Models\Member::class`.
- Run `php artisan filament:install --panels` with panel id `admin`. Update `AdminPanelProvider` to use `->authGuard('web')`.
- `app/Http/Controllers/AuthController.php` — standard `Auth::attempt()` login/logout for non-admin members. Redirects admins to `/admin`, others to `/`.
- `resources/views/auth/login.blade.php` — minimal login form.
- `app/Http/Middleware/RequireActiveMember.php` — redirect to login if `!auth()->check() || !auth()->user()->is_active`.
- `app/Http/Middleware/RequireAdmin.php` — abort 403 if not admin.
- Register middleware aliases in `bootstrap/app.php`.

---

### Phase 2 — Migrations and Models

Migration files, in dependency order (name them so they run sequentially):

| File prefix | Table | FK dependencies |
|---|---|---|
| `2024_01_01_000002` | `styles` | none |
| `2024_01_01_000003` | `competitions` | none |
| `2024_01_01_000004` | `competition_style` | styles, competitions |
| `2024_01_01_000005` | `entries` | competitions, members, styles |
| `2024_01_01_000006` | `scoresheets` | entries, members |

**styles schema:** id, code VARCHAR(10) UNIQUE, category_name, name, version VARCHAR(10) default '2021', og_min/max, fg_min/max, abv_min/max, ibu_min/max, srm_min/max (all nullable float), timestamps.

**competitions schema:** id, name, year SMALLINT UNSIGNED, judging_date DATE nullable, status ENUM('draft','open','judging','closed') default 'draft', published_at TIMESTAMP nullable, timestamps.

**competition_style schema:** competition_id FK ON DELETE CASCADE, style_id FK ON DELETE CASCADE, PRIMARY KEY(competition_id, style_id). No id, no timestamps.

**entries schema:** id, competition_id FK ON DELETE CASCADE, member_id FK ON DELETE RESTRICT, style_id FK ON DELETE RESTRICT, name, bottle_number UNSIGNED SMALLINT nullable, brewer_notes TEXT nullable, timestamps. Unique index on (competition_id, bottle_number) — NULLs are excluded by default in Postgres/MySQL so pre-assignment doesn't violate it.

**scoresheets schema:** id, entry_id FK ON DELETE CASCADE, judge_id FK members ON DELETE RESTRICT, aroma TINYINT UNSIGNED, appearance TINYINT UNSIGNED, flavour TINYINT UNSIGNED, mouthfeel TINYINT UNSIGNED, overall TINYINT UNSIGNED, total TINYINT UNSIGNED, aroma_notes/appearance_notes/flavour_notes/mouthfeel_notes/overall_notes TEXT nullable, client_uuid CHAR(36) UNIQUE, status ENUM('draft','submitted') default 'draft', submitted_at TIMESTAMP nullable, timestamps. UNIQUE(entry_id, judge_id).

Models to create: `Style`, `Competition`, `Entry`, `Scoresheet`. Key behaviours:
- `Scoresheet::booting()` sets `$sheet->total = aroma + appearance + flavour + mouthfeel + overall` on every save.
- `Competition` has constants for statuses and a `belongsToMany(Style::class, 'competition_style')`.
- `Entry` has `hasMany(Scoresheet::class)` and `belongsTo` for competition/member/style.

---

### Phase 3 — Filament Resources

All resources live under `app/Filament/Resources/`.

**MemberResource** — full CRUD. Password field: show only on Create; use a separate "Reset password" action on Edit. Columns: name, email, bjcp_rank, is_admin badge, is_active badge.

**StyleResource** — read-only. Override `canCreate/canEdit/canDelete` → false. Searchable table with code, category_name, name columns.

**CompetitionResource** — full CRUD plus lifecycle actions on the detail page:

| Action | Visible when | Effect |
|---|---|---|
| Move to Open | status = draft | status = 'open' |
| Move to Judging | status = open | status = 'judging', triggers bottle number assignment |
| Move to Closed | status = judging | status = 'closed' |
| Publish Results | status = closed, published_at null | published_at = now() |
| View Results | status = closed | links to /competitions/{id}/results |

"Move to Judging" shows a confirmation dialog: "This will lock registration and assign bottle numbers. Continue?"

`app/Services/BottleNumberAssignmentService.php`:
```php
public function assign(Competition $competition): void
{
    DB::transaction(function () use ($competition) {
        $competition->entries()->orderBy('id')->each(
            fn($entry, $i) => $entry->update(['bottle_number' => $i + 1])
        );
    });
}
```
Note: `each` index starts at 0; add 1 for bottle number.

**EntriesRelationManager** on CompetitionResource — shows/manages entries in the competition detail page. Form fields: member_id (Select searchable), style_id (Select searchable, ideally filtered to competition's accepted styles), name, bottle_number (admin-only, nullable), brewer_notes.

**JudgeVarianceWidget** on CompetitionResource — Filament TableWidget. Query per entry: `MAX(total) - MIN(total)` over submitted sheets. Flag if spread > 7 with a warning badge. Only entries with ≥ 2 sheets are included.

---

### Phase 4 — POST /scoresheets (Upsert Endpoint)

File: `app/Http/Controllers/ScoresheetController.php`

Route: `POST /scoresheets` behind `auth` + `active-member` middleware. No separate API token; the session cookie covers this.

Upsert logic (guard order matters):
1. Validate all fields (aroma 0-12, appearance 0-3, flavour 0-20, mouthfeel 0-5, overall 0-10, client_uuid uuid format)
2. Load entry with competition; abort 422 if `competition->status !== 'judging'`
3. Abort 422 if `entry->member_id === auth()->id()`
4. Find existing row: first try by `client_uuid`, fall back to `(entry_id, judge_id)` — handles reopen-from-new-device case
5. Update or create; `total` is set by the model's saving hook
6. Return JSON 200: `{ id, client_uuid, total, status, saved_at }`

Always return 200 (not 201) — the client can't know if it was insert or update, and consistency simplifies retry logic.

CSRF: judging Blade page includes `<meta name="csrf-token">`. Alpine reads it and sets `X-CSRF-TOKEN` header on all fetches.

---

### Phase 5 — Judging Screen (Blade + Alpine + Dexie)

Route: `GET /judging/{competition}` behind `auth` + `active-member`.

`JudgingController::show()`: abort 403 if status ≠ 'judging'. Load entries (excluding judge's own) with style, eager-load judge's existing scoresheets keyed by entry_id. Server-render both into a JSON blob in the Blade page.

**Design spec** — see section below.

**Vite:** Add `resources/js/judging.js` as a separate entry point in `vite.config.js`. Do not import or start Alpine in `app.js` — Filament's Livewire starts its own Alpine instance. On the judging page, `judging.js` starts Alpine independently.

**Dexie schema** in `resources/js/judging.js`:
```js
const db = new Dexie('beerjudge');
db.version(1).stores({ scoresheets: 'client_uuid, entry_id, syncStatus' });
```

**Alpine state shape** per entry in `sheets[entryId]`:
```
{ client_uuid, entry_id, aroma, appearance, flavour, mouthfeel, overall,
  [section]_notes × 5, status ('draft'|'submitted'), syncStatus ('idle'|'pending'|'syncing'|'synced'|'error'), expanded }
```

**Sync flow:**
1. On any input change (debounce 500ms): write to IndexedDB with `syncStatus='pending'`, then POST to server
2. On success: set `syncStatus='synced'`, update status from response
3. On failure: set `syncStatus='error'`, add entryId to retryQueue, schedule retry in 5s (only one timer at a time)
4. On page load: merge IndexedDB state — prefer IDB over server data for entries with `syncStatus='pending'|'error'`, queue them for retry

**Submit:** sets `status='submitted'`, calls save. After server confirm, show read-only summary + Reopen button.

**Reopen:** sets `status='draft'` (same client_uuid), calls save. Server overwrites in place.

**Computed total** (Alpine only, for display):
```js
total: (id) => ['aroma','appearance','flavour','mouthfeel','overall'].reduce((s,f) => s + (this.sheets[id][f]||0), 0)
```

**Band helper:**
```js
band: (t) => t>=45?'Outstanding':t>=38?'Excellent':t>=30?'Very Good':t>=21?'Good':t>=14?'Fair':'Problematic'
```

---

### Phase 6 — Results Views

`ResultsController::show(Competition $competition, Member $member)`:
- Abort 403 if competition status ≠ 'closed'
- If not admin: abort 403 if not published, abort 403 if viewer ≠ member
- Build ranked entries using:
  ```php
  ->sort(fn($a, $b) => $b['avg_total'] <=> $a['avg_total'] ?: $b['avg_overall'] <=> $a['avg_overall'])
  ```
- Assign placement after sort

`ResultsController::index(Competition $competition)` — admin only, lists links to per-member sheets, plus a print-all wrapper (all partials with `page-break-after: always`).

Views:
- `resources/views/results/show.blade.php` — Part 1: member's own entries with per-judge section breakdowns + notes + placement. Part 2: full standings with brewer names revealed. Print-optimized with Tailwind `print:` variants.
- `resources/views/results/index.blade.php` — admin batch view.

---

### Phase 7 — BJCP Seeder and Tests

`database/seeders/BjcpStyleSeeder.php` — all ~110 BJCP 2021 styles hardcoded. Use `Style::upsert($styles, ['code'], [...columns])` for idempotency. Covers categories 1–34 + X1-X4 experimental.

`database/seeders/DemoSeeder.php` — dev-only: 2 members (1 admin, 1 judge), 1 competition in 'judging' state, 4 entries, a few scoresheets. Run via `php artisan db:seed --class=DemoSeeder`.

**Feature tests** (hit real DB, use `RefreshDatabase`):

| Test class | Covers |
|---|---|
| `ScoresheetUpsertTest` | happy path insert, idempotent update, reopen, own-entry guard, closed-competition guard, validation |
| `JudgingScreenTest` | 403 when not judging, excludes own entries, page renders |
| `ResultsAccessTest` | admin immediate access, member blocked pre-publish, member can't see others' sheet, ranking + tiebreak |
| `CompetitionLifecycleTest` | status transitions, bottle number assignment, duplicate bottle number constraint |
| `FilamentMemberResourceTest` | admin CRUD, non-admin redirected |

**Unit tests:**

| Test class | Covers |
|---|---|
| `ScoresheetTotalTest` | saving hook: all-zero, all-max, mixed values |
| `RankingTest` | sort order, tiebreak, single-entry edge case |
| `BandLabelTest` | all band boundaries |

---

## Judging Screen Design Spec

Mobile-first. Judges use their phones at the bar.

### Layout overview

```
┌─────────────────────────────────────┐
│  BeerJudge · Spring 2025            │
│  [name]                  [log out]  │
├─────────────────────────────────────┤
│  Bottle #1 · 10A · "Hop Cannon IPA" │  ← entry row, tappable
│  [●●●●●●●●○○ 8/12]  [Synced ✓]     │  ← score preview + sync badge
├─────────────────────────────────────┤
│  Bottle #2 · 4C · "Munich Dunkel"   │  ← collapsed
│  [Not started]          [—]         │
├─────────────────────────────────────┤
│  ▼ Bottle #3 · 21A · "West Coast"   │  ← EXPANDED
│  ┌───────────────────────────────┐  │
│  │ Aroma           ___/ 12       │  │
│  │ [slider 0–12]                 │  │
│  │ Notes: ___________________   │  │
│  │                               │  │
│  │ Appearance      ___/ 3        │  │
│  │ [slider 0–3]                  │  │
│  │ Notes: ___________________   │  │
│  │                               │  │
│  │ Flavour         ___/ 20       │  │
│  │ [slider 0–20]                 │  │
│  │ Notes: ___________________   │  │
│  │                               │  │
│  │ Mouthfeel       ___/ 5        │  │
│  │ [slider 0–5]                  │  │
│  │ Notes: ___________________   │  │
│  │                               │  │
│  │ Overall         ___/ 10       │  │
│  │ Notes: ___________________   │  │
│  │                               │  │
│  │ ─────────────────────────   │  │
│  │ Total: 38 / 50  · Excellent   │  │
│  │                               │  │
│  │ [Saving…]  [Submit Sheet ▶]  │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

### Entry row states

| State | Preview | Sync badge |
|---|---|---|
| Not started | — | — |
| In progress (draft) | section dots or partial score | Saving… / Saved / ⚠ Retry |
| Submitted | total score + band | Submitted ✓ |

### Score inputs

Use `<input type="number" min="0" max="N">` with increment/decrement buttons (+ / −) on mobile, not sliders. Sliders are imprecise on small screens. Each section:

```
┌──────────────────────────────────┐
│ Aroma                    [−][8][+]  /12  │
│ ┌────────────────────────────┐   │
│ │ Notes (optional)           │   │
│ └────────────────────────────┘   │
└──────────────────────────────────┘
```

The number input is large (min 48px tap target). Max is shown next to it. Notes textarea is below, collapsible or always shown (always shown is simpler and friendlier for detailed feedback).

### Sync status indicators

Inline with each entry row and at the top of an open scoresheet:

- **Synced** — grey check. No action needed.
- **Saving…** — animated spinner. Auto-save in progress.
- **Saved locally, retrying…** — yellow dot. Server unreachable; IDB has data.
- **Error** — red dot + "Tap to retry" link. Manual trigger calls `syncToServer(entryId)`.

### Submitted state

After submission, the expanded panel switches to read-only display:

```
┌────────────────────────────────────────┐
│ ✓ Submitted                            │
│                                        │
│ Aroma 8/12 · Appearance 2/3           │
│ Flavour 14/20 · Mouthfeel 3/5         │
│ Overall 8/10                          │
│                                        │
│ Total: 35 / 50 · Very Good            │
│                                        │
│ Your notes are saved.                  │
│                                        │
│ [Reopen to edit]                       │
└────────────────────────────────────────┘
```

Reopen re-enables the form. Server sets status back to 'draft' on save (same client_uuid).

### Typography and colour

- System font stack. Body 16px minimum.
- Score band colours: Outstanding/Excellent = green-600, Very Good = teal-600, Good = yellow-600, Fair/Problematic = red-600.
- Sync badges: synced = gray-400, saving = blue-500 (animated), error = red-500.
- Tailwind CSS. Mobile breakpoint only; no desktop-specific layout needed (but should not break on desktop).

### Accessibility

- All inputs have associated `<label>` elements.
- Score inputs have `aria-label` including the max value ("Aroma score, 0 to 12").
- Sync status changes use `aria-live="polite"`.

---

## Task Delegation Strategy for Implementation

When implementing, spawn sub-agents per phase. Each phase is independent once the previous is complete.

**Agent 1 (Phase 1+2):** Scaffold Laravel, configure Member model, run Filament install, write all migrations and models. Verify with `php artisan migrate` and `php artisan tinker` to confirm relationships.

**Agent 2 (Phase 3):** Build all Filament resources. Depends on Phase 2 models existing. Verify by loading `/admin` in a browser and testing each resource.

**Agent 3 (Phase 4):** Build `ScoresheetController` and write `ScoresheetUpsertTest`. The test can run without a browser. Verify with `php artisan test --filter=ScoresheetUpsertTest`.

**Agent 4 (Phase 5):** Build judging screen. Depends on Phase 4 endpoint. Use the design spec above — do not deviate from it without flagging. Run `npm run dev` and open the judging route in a browser to verify the sync flow manually.

**Agent 5 (Phase 6):** Build results controller and views. Verify access control gates and ranking order with `ResultsAccessTest`.

**Agent 6 (Phase 7):** Write the BJCP seeder (all 110 styles) and the demo seeder. Write remaining tests. Run `php artisan test` to confirm full suite green.

**Frontend design skill:** Invoke `/frontend-design` at the start of Phase 5 to generate an HTML prototype of the judging screen before writing the Blade template. Use the design spec above as input. Commit the prototype to `resources/views/judging/prototype.html` for reference; do not discard it.

---

## Verification Checklist

- [ ] `php artisan migrate --seed` completes without error; ~110 styles seeded
- [ ] `/admin/login` — admin member can log in, reach Filament panel
- [ ] Create competition, add styles, add entries via Filament
- [ ] Move competition to 'judging'; bottle numbers assigned sequentially
- [ ] `/judging/{id}` — non-admin member can reach judging screen; own entries excluded
- [ ] Fill a scoresheet; IndexedDB entry created; POST /scoresheets returns 200 with total
- [ ] Disable network; continue filling — IDB saves, retry queues, sync on reconnect
- [ ] Submit a sheet; status becomes 'submitted'; reopen works
- [ ] Move competition to 'closed' in Filament
- [ ] `/competitions/{id}/results/{member_id}` — accessible to admin; blocked for member until published
- [ ] Publish results; member can now access their own sheet; cannot access other members'
- [ ] Judge variance widget shows spread for entries with multiple sheets
- [ ] `php artisan test` — all tests pass
