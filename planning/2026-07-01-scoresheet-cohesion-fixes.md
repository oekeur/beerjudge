# Plan: Scoresheet cohesion fixes — per-entry results vs. judge report

**Goal:** Bring `results/show.blade.php` and `results/judge-report.blade.php` to a consistent shared visual/structural vocabulary, and fix a real print bug in the judge report, without redesigning either view.

**Out of scope:** Adding a hero number to the judge report (confirmed: dashboard-style stat tiles stay, documented as intentional). Adding outlier comparison bars to the per-entry view. Any spec/doc updates to `planning/specs/results.md`. PDF generation.

**Constraints:** Container width converges on `max-w-4xl`. No markup-asserting tests exist for these views (`ResultsAccessTest`, `JudgeReportAccessTest` are access-control only), so styling changes are low-risk to the test suite — verification is manual/visual.

## Background

A review agent compared the two views after their recent redesigns (#5, #8, #9). Several of its claims about the "Full Standings" table (shared verbatim by both files) turned out to be stale on direct inspection — that table is already byte-for-byte identical (header classes, row padding, border, band pill markup). No fix needed there beyond extracting it into a shared partial to lock in the consistency.

What's real, confirmed by reading both files line-by-line:

1. **Print stylesheet gap** (functional bug): `show.blade.php`'s `@media print` block has three rules `judge-report.blade.php`'s lacks: `.page-break`, `.notes-row { display: table-row !important }`, `.chevron { display: none !important }`. judge-report.blade.php's delta table has its own expandable rows (`x-show="open" x-cloak`) and chevron cell but neither carries the classes needed to behave correctly on print — the outlier comparison bars (the most useful content for a printed audit) silently disappear, and the chevron glyph/hint text print uselessly.
2. **"Your Scores vs. the Field" table header/row style** doesn't match the pattern used everywhere else — plain header instead of uppercase/xs/tracking-wide, denser row padding, different border shade.
3. **Outlier tag** uses inconsistent padding scale and shade pairing (`-700` text on `-100` bg instead of the `-800` pattern used everywhere else).
4. **Missing `tabular-nums`** on the delta table's numeric cells.
5. **Container width**: `max-w-4xl` vs `max-w-5xl` — no functional reason for the difference.
6. **Hint text under table**: missing `no-print` class in judge-report (so it prints next to a dropped table) and wrong shade.
7. **Hidden-row background opacity**: `bg-gray-50/70` vs `bg-gray-50/60` — cosmetic, unify.

Not acting on: the review's claim about a "proper minus sign" convention worth preserving — the code just uses `number_format()`, no special character exists to preserve. Also not building a reusable outlier-bar component now — no current use case in show.blade.php.

Decisions confirmed with the user before drafting:
- Judge report keeps its current dashboard-style profile panel (no added hero number) — documented as intentional via a one-line comment.
- Both views converge on `max-w-4xl` (the per-entry view's width, since it's the more frequently shared/printed page).

---

## Phase 1: Extract shared partials

**Outcome:** Both views render identically (no visual change yet) via two new shared partials, eliminating duplicated markup that would otherwise drift again.

Tasks:
- [x] Create `resources/views/results/partials/full-standings.blade.php`, extracted from the (already-identical) Full Standings table in both files; accept `$rankedEntries` and optional `$currentMemberId`.
- [x] Replace both inline copies with `@include('results.partials.full-standings', ['currentMemberId' => $memberId ?? null])`.
- [x] Create `resources/views/results/partials/print-styles.blade.php` containing the union of both `@media print` blocks (all 5 rules currently only in show.blade.php).
- [x] Include the shared print partial in both files' `<x-slot:head>`.

Unknowns:
- Whether `<x-page>`'s head slot has any existing convention for including partials inside `<style>` vs. a dedicated prop — check `resources/views/components/page.blade.php` before writing the include (low risk, `@include` inside `<style>` is plain PHP output, should just work).

---

## Phase 2: Align judge-report's own table styling

**Outcome:** judge-report.blade.php's "Your Scores vs. the Field" table, outlier tag, and hint text match the shared visual pattern already used in Full Standings and show.blade.php's judge table.

Tasks:
- [x] `judge-report.blade.php:14` — `max-w-5xl` → `max-w-4xl`.
- [x] `judge-report.blade.php:78` — header row → `bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide`. Also bumped header `<th>` padding `py-1.5 px-2` → `py-2 px-3` to match the row padding change below (not in original task list, caught during verification).
- [x] `judge-report.blade.php:88` — row border `border-gray-200` → `border-gray-100`; padding `py-1.5 px-2` → `py-2.5 px-3`.
- [x] `judge-report.blade.php:93` — outlier tag padding `px-1.5 py-0.5` → `px-2 py-0.5`; text shade `-700` → `-800` (red/blue).
- [x] `judge-report.blade.php:98-100` — add `tabular-nums` to the three numeric `<td>`s.
- [x] `judge-report.blade.php:127` — hint paragraph → add `no-print`, light-mode `text-gray-500` → `text-gray-400` (kept dark-mode at `text-gray-500` to match show.blade.php's equivalent hint, fixed after subagent flattened both modes to the same shade).
- [x] `judge-report.blade.php:103` — `bg-gray-50/70` → `bg-gray-50/60`.
- [x] Add a one-line HTML comment on the Judging Profile panel (`judge-report.blade.php:37`) noting the stat-tile layout is a deliberate calmer tone, not accidental drift from show.blade.php's hero number.

Unknowns:
- None significant — these are direct class swaps on lines already confirmed in this session.

---

## Phase 3: Fix judge-report print correctness

**Outcome:** Printing the judge report shows the outlier comparison bars (currently silently dropped) and hides interactive-only chrome (chevron, click hint) — matching how show.blade.php already prints correctly.

Tasks:
- [x] `judge-report.blade.php:103` — add `notes-row` class to the expandable `<tr x-show="open">`.
- [x] `judge-report.blade.php:101` — add `chevron` class to the chevron `<td>`.
- [ ] Manually verify via browser print preview (Ctrl+P) on a competition with submitted scoresheets: outlier bars appear, chevron and "click to expand" hint disappear. **Not done — no browser available in this environment; deferred to user.**
- [x] Run `./vendor/bin/pint --dirty` and `php artisan test --compact tests/Feature/ResultsAccessTest.php tests/Feature/JudgeReportAccessTest.php`. 16 tests, 16 passed.

Unknowns:
- [RISK] Whether Alpine's `x-cloak`/`x-show` inline `style="display:none"` on the row (set by Alpine at runtime, not present in raw server HTML before hydration) interferes with the print media query — `x-show` sets `display: none` via JS after Alpine initializes; on a page opened fresh for printing (no prior interaction), the row should already be in its default closed state, so the `.notes-row { display: table-row !important }` print override should win. Needs the manual print-preview check in this phase to confirm, since it can't be verified by a feature test (no headless browser/Alpine execution in PHPUnit).

## Open decisions

None outstanding — hero moment and container width were resolved with the user before drafting this plan.
