# Plan: Judge registration gate, safe judge-list edits, judging-screen bottom clearance

**File:** planning/2026-10-14-judge-registration-fixes.md
**Goal:** Only registered judges can score, the admin edit page can no longer overwrite the judge list, and the last entry on the judging screen is tappable.
**Out of scope:** Special client handling when the server refuses a sheet from a non-judge (the existing red Retry badge and Sentry report cover it; Oscar, 2026-10-14). Judge reports for members who left (the report still needs a judge-list row; Oscar, 2026-10-14). TASK-114 (parked). Re-joining through the `?register=1` invite link after leaving stays silent and allowed.
**Constraints:** Judges may leave mid-way, also after submitting; their submitted sheets keep counting in results (Oscar, 2026-10-14, reversing the earlier "no leave after first submission"). Judges select is hidden on the Create page (Oscar, 2026-10-14).

---

## Phase 1: TASK-113, judge-list edits apply one change at a time (High)

Runs first: once Phase 2 makes registration the only way to score, a stale save that drops a judge would lock them out (red-team, 2026-10-14).

**Outcome:** Adding or removing a judge on the competition edit page writes that one change on the change itself; Save never touches `competition_judge`.

Tasks:
- [x] TASK-113: replace sync-on-save with per-change attach/detach on a live select. Done when: [AC 1-5] pass; Livewire feature tests cover the stale-page cases; the full PHPUnit suite, PHPStan and `npx playwright test` pass.
  Context (verified 2026-10-14): `CompetitionForm.php` Dashboard tab, `Select::make('judges')->multiple()->relationship('judges','name')`. Filament injects `$old` into `afterStateUpdated` (`vendor/filament/schemas/src/Components/Concerns/HasState.php:225`). `Select::relationship()` installs its own `saveRelationshipsUsing` (`vendor/filament/forms/src/Components/Select.php:955`), so `->saveRelationshipsUsing(null)` must come after `->relationship()`; hydration comes from `loadStateFromRelationshipsUsing` (Select.php:798) and is unaffected. Without `->live()` the state is deferred and the change only reaches the server on the next request; a Livewire `->set()` test cannot tell the difference, hence AC 5's browser check. TASK-111 (plan `2026-10-13-fix-edit-competition-n1.md`) also edits `CompetitionForm.php` and pins EditCompetition save query counts; whichever lands second rebases and re-runs those tests.

Unknowns:
- None blocking.

---

## Phase 2: TASK-112, registration gate and progress counts (High)

**Outcome:** An unregistered member sees a "participate" prompt instead of the scoresheets; the server refuses sheets from non-judges; existing competitions have every sheet author on their judge list; admin progress counts handle judges who left.

**Design sketch:** Judging screen for a non-judge: header, then for admins the progress panel listing every entry as today, then the participate banner with one button, and no `<main>` entry list. After "Participate" (POST, redirect back) the normal screen. A judge sees a small "Stop participating" link where "Leave as judge" is now, available during open and judging.

**Progress rule (judging-screen panel, widget table and widget tiles):** for each entry, eligible = (current judges ∪ members with a submitted sheet on that entry) minus the brewer; submitted = submitted sheets on that entry; missing = current judges (minus brewer) without a submitted sheet. The widget's Sheets tile is sum(submitted) / sum(eligible) over entries and Progress is that ratio as a percentage, so it never exceeds 100%.

Tasks:
- [ ] TASK-112: gate the judging screen and the save endpoint, reword to participate, progress rule, backfill, spec. Done when: [AC 1-9] pass; the full PHPUnit suite, PHPStan and `npx playwright test` pass.
  Context (verified 2026-10-14): `JudgingController::show` builds the payload for any member and attaches only with `?register=1`. The admin progress panel iterates `entries` from `serverData` (`judging/show.blade.php:76`), so withholding `entries` for a non-judge needs a separate entry list for the panel. `ScoresheetController::upsert` has no registration check. `ResultsSummaryWidget` view (`resources/views/filament/competition/results-summary.blade.php`, tiles around lines 13-18 and the per-entry table) and `JudgingController::progress` take eligible judges from `competition_judge` but count all submitted sheets. `CompetitionRankingService` uses every submitted sheet; keep that. `JudgeRegistrationController::destroy` has no status check. `planning/specs/judging.md` line 68 wrongly says the screen auto-registers; lines 65 and `competitions.md:20` call the pivot `competition_member` (it is `competition_judge`). Copy keys `judging.not_registered`, `judging.register_as_judge`, `judging.leave_as_judge`. Test rework: about ten PHPUnit files visit the judging screen or POST /scoresheets without attaching the judge (ScoresheetUpsertTest, ScoresheetRevisionTest, JudgingScreenTest, ScoresheetValidationTest, SentryMetaTagTest, BaStyleAttributionTest, JudgingScreenLocaleTest, HomeTest, MiddlewareTest, ErrorPagesTest). `database/seeders/BrowserTestSeeder.php` attaches no judges and the Playwright judging specs open `/judging/1` without `?register=1`; `tests/Browser/judge-registration.spec.ts` asserts the "Register as judge" button and needs one member who starts unregistered.

Unknowns:
- None blocking.

---

## Phase 3: TASK-115, bottom clearance for the Sentry button (Medium)

**Outcome:** The last entry on the judging screen scrolls clear of the floating feedback button.

Tasks:
- [ ] TASK-115: bottom padding on the entry list including the safe-area inset. Done when: [AC 1-2] pass.
  Context (verified 2026-10-14): `resources/views/judging/show.blade.php:116` `<main ... py-4>`; the style sheet at line 516 uses `pb-24` for the same button. Playwright reads `.env.browser-test`, which deliberately has no Sentry DSN, so the button is not injected by default; `tests/Browser/admin-sentry.spec.ts` shows how to run with a fake DSN. Same file as Phase 2, so it runs after Phase 2 lands.

Unknowns:
- None.

---

## Task acceptance criteria after sync (Step 9)

TASK-113 (replaces current ACs):
1. A judge who registers while an admin has the edit page open is still a judge after the admin saves
2. Removing a judge on one copy of the edit page is not undone by a save from a second copy loaded earlier
3. Saving the edit page with no change to the judges field leaves competition_judge unchanged
4. The judges field is not shown on the Create page
5. In a browser, adding or removing a judge and then reloading without pressing Save shows the change persisted (Playwright, or handed to Oscar with the exact steps)

TASK-112 (replaces current ACs):
1. A member not in competition_judge gets a judging page whose server data has an empty entries list, without the `<main>` entry list, and with the participate button
2. An admin who is not a judge still sees the progress panel listing every entry
3. POST /scoresheets from a member who is not a judge of the entry's competition returns 403 and stores nothing
4. After choosing participate, the member sees the entry list and can save a sheet
5. Registration copy says participate (EN) and deelnemen (NL) instead of register as judge, in the banner, the button and the leave link
6. A judge can stop participating while the competition is open or judging, also after submitting; their submitted sheets stay stored and still count in the ranking; leaving a closed competition is refused
7. The judging-screen progress panel and ResultsSummaryWidget (table and tiles) follow the plan's progress rule; a test with a judge who submitted and then left shows no entry above its eligible count, Progress at most 100%, and the leaver not listed as missing
8. A migration adds every member with a submitted sheet to that competition's judge list; run on data that already complies, it changes nothing
9. planning/specs/judging.md and competitions.md describe the gate, the leave rule and the pivot as competition_judge, and drop the claim that the screen auto-registers on first load

TASK-115 (replaces current ACs):
1. With a fake Sentry DSN at a 390x844 viewport, the feedback button is present and, scrolled to the end, the last entry header's bounding box does not intersect it
2. Checked in a browser (the Playwright check of AC 1 counts), not only by reading the markup

## Dependencies

Phase 1 before Phase 2 (the gate makes a stale-save drop lock judges out). Phase 3 after Phase 2 (same Blade file). TASK-111 and Phase 1 share `CompetitionForm.php`; no order required, the second rebases.

## Open decisions

- None.

## Red-team notes (2026-10-14)

All findings folded in: phase order, Playwright seeder and spec rework, admin panel entry list, widget tiles, PHPUnit rework, `saveRelationshipsUsing` order, `->live()`, the DSN in TASK-115, TASK-111 overlap, spec pivot name. Added beyond the tickets: leaving a closed competition is refused (the adversary noted a member could otherwise drop their own judge report).
