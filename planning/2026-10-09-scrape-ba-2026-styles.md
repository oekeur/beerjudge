# Plan: Seed the Brewers Association 2026 beer styles

**File:** planning/2026-10-09-scrape-ba-2026-styles.md
**Tasks:** TASK-80 (Phase 1), TASK-81 (Phase 2). Integration branch: `main` (Oscar, 2026-10-09).
**Goal:** Admins can pick Brewers Association 2026 styles for a competition, and judges see BA guideline text with the BA attribution line, seeded from a committed JSON snapshot that an artisan command scraped from the BA website.
**Out of scope:** BA editions other than 2026 (and any year-over-year diffing); translating BA text; BA's own GABF/WBC competition category numbering; parsing the BA PDF; running the seeder on production (Oscar's deploy step; this plan only documents the command).
**Constraints:** No new Composer or npm dependency (HTML parsing uses PHP 8.5's built-in `Dom\HTMLDocument`). The seeder never touches the network. Upsert key stays `['code', 'source']` (unique index confirmed in `2026_06_16_182456_add_source_to_styles_table.php`), `source` stays out of the update columns.

## Source facts (probed 2026-10-09)

- URL: `https://www.brewersassociation.org/edu/brewers-association-beer-style-guidelines/` ("Download 2026 Edition"). robots.txt allows it (only `/*.pdf` is disallowed). `curl` with a browser User-Agent from this machine returns the full page (~513 KB), so a local executing agent has the network access it needs.
- Each style is `<div class="beer-style">` containing `<ul class="list-with-heading" id="<anchor>">` (first `<li>` = style name, then `<li><strong>Label:</strong> text</li>`) and `<ul class="horizontal wider">` with the vital stats.
- 168 styles. Every style has: Color, Clarity, Perceived Malt Aroma & Flavor, Perceived Hop Aroma & Flavor, Perceived Bitterness, Fermentation Characteristics, Body. 135 also have "Additional notes".
- Groups: 12 `<h2 class='origin block-heading'>` headings after `<section id="beer-styles">` (e.g. "British Origin Ale Styles", 17 styles; "All Origin Hybrid/Mixed Lagers or Ale", 32 styles). Every style sits under one of them.
- Stats formats: OG `1.033-1.038 (8.3-9.5 °Plato)`; FG `1.006-1.012 (1.5-3.1 °Plato)`; ABV `2.40%-3.30% (3.00%-4.20%)` (weight first, volume in brackets); IBU `20-35`; SRM `5-12(10-24 EBC)`. Other shapes listed under the parsing rules below.
- Anchor ids are not stable across editions (2024: `#4`, `#5`, `#6`; 2026: `#941`, `#972`, `#892`), so they are not used as codes.
- Licence (source: "Proper Use of Guidelines" on https://www.brewersassociation.org/educational-publications/beer-styles/): free use requires an attribution line "in all print, web, app or other instances that reference the beer style guidelines". The text is reorganised into our sections, so the "with changes" line applies: "Based on Brewers Association 2026 Beer Style Guidelines with changes. Used with permission of Brewers Association." Permission is not granted when the end use is for a cost; the club app is free.

## Decisions taken with Oscar (2026-10-09)

- Codes: `BA-001` ... `BA-168` in page order.
- Fields map into the existing guide columns (no migration), as labelled parts:
  - `appearance` = "Color: ... Clarity: ..."
  - `aroma` = "Malt: <Perceived Malt Aroma & Flavor> Hops: <Perceived Hop Aroma & Flavor>"
  - `flavor` = the same malt and hop parts plus "Bitterness: <Perceived Bitterness>"
  - `mouthfeel` = "Body: ..."
  - `overall_impression` = "Fermentation: <Fermentation Characteristics>" plus "Notes: <Additional notes>" when present
  - Parts are joined with `\n`. `category_name` = the h2 group heading; `version` = `2026`; `source` = `ba_beer`; `examples`, `history`, `category_description` and the other BJCP-only columns null.
- Scraper is a committed artisan command writing a committed `ba_styleguide-2026.json`; `BaStyleSeeder` reads that file and is registered in `DatabaseSeeder`.
- The attribution line shows on the judging screen and in Filament on BA styles (Oscar kept Filament attribution in scope).

---

## Phase 1: Scraper and JSON snapshot

**Outcome:** `php artisan styles:scrape-ba` writes `ba_styleguide-2026.json` in the repo root, and that file is committed. Nothing reads it yet, so this lands on its own without user-visible change.

**Interface contract** (the JSON the seeder in Phase 2 reads):
```json
{
  "edition": "2026",
  "source_url": "https://www.brewersassociation.org/edu/brewers-association-beer-style-guidelines/",
  "scraped_at": "2026-10-09",
  "styles": [
    {
      "code": "BA-001",
      "name": "Ordinary Bitter",
      "category": "British Origin Ale Styles",
      "appearance": "Color: ...\nClarity: ...",
      "aroma": "Malt: ...\nHops: ...",
      "flavor": "Malt: ...\nHops: ...\nBitterness: ...",
      "mouthfeel": "Body: ...",
      "overall_impression": "Fermentation: ...\nNotes: ...",
      "og_min": 1.033, "og_max": 1.038,
      "fg_min": 1.006, "fg_max": 1.012,
      "abv_min": 3.00, "abv_max": 4.20,
      "ibu_min": 20, "ibu_max": 35,
      "srm_min": 5, "srm_max": 12
    }
  ]
}
```
Numeric fields are numbers or `null`. Parsing rules, applied to the leading token of each stat (for ABV, to the bracketed volume part only, never the weight part). Text after the leading range is ignored: `5-30; may differ significantly from perceived bitterness` (IBU, 4 styles) gives 5/30, and `5-50 or color of fruit` / `2-10, or color of fruit` (SRM, 2 styles) give 5/50 and 2/10.
- `A-B`, with or without `%` on either end: `min = A`, `max = B`.
- `A-B+` or `A%-B%+` (open top, e.g. `7%-13%+`): `min = A`, `max = null`.
- `A+` (e.g. `8%+`, `40+`): `min = A`, `max = null`.
- `<A` (e.g. `<0.5% abv`): `min = null`, `max = A`.
- Anything else, including empty, `Varies...`, `May vary widely`, `N/A`, and an ABV with no bracketed volume part (the one 2026 style showing just `>4.0%`): both `null`.

HTML entities are decoded and whitespace collapsed in all text.

Tasks:
- [x] TASK-80: Add a `styles:scrape-ba` artisan command with an `--output=` option (default `base_path('ba_styleguide-2026.json')`) that fetches the BA page with the Laravel HTTP client (browser User-Agent), parses it with `Dom\HTMLDocument`, writes the JSON above, and prints the code and name of every style where a stat parses to null on both ends; run it once and commit the generated file. Done when [TASK-80 AC 1-4]: (1) a PHPUnit feature test fakes the HTTP response with a fixture of five styles in two h2 groups (a normal one; one with `Varies with style` stats; one with SRM `40+(80+ EBC)`, ABV `6.4%+ (8%+)` and no "Additional notes"; one with ABV `<0.4% abw (<0.5% abv)`; one with IBU `5-30; may differ significantly from perceived bitterness`), runs the command with `--output` pointing at a temp file, and asserts the exact JSON produced for each, including codes BA-001..BA-005, the h2 category and ABV taken from the bracketed volume range; (2) with `--output` at a temp path that does not exist, the command exits non-zero with a message and the path still does not exist when the faked page yields zero styles, or when one style lacks one of the seven always-present labels; (3) after the test suite runs, `git status` shows `ba_styleguide-2026.json` unmodified; (4) the committed file holds 168 styles, codes `BA-001`..`BA-168` with no gaps, 12 distinct `category` values, every non-null `og_min` between 1.000 and 1.200, and at least 140 styles each with non-null `ibu_min` and `srm_min`.
  Context: app/Console/Commands/ScrapeBaStyles.php (styles:scrape-ba, writes the contract JSON); ba_styleguide-2026.json (repo root, the seeder input); tests/Feature/ScrapeBaStylesCommandTest.php + tests/Fixtures/ba-style-guidelines.html (Http::fake pattern, --output to temp).

Unknowns:
- The BA page could change markup before execution runs. The fixture test and the zero-style / missing-label failure make that loud rather than silent; the executing agent re-profiles against the live page before committing the JSON.
- ABV formats probed on all 168 styles: 141 match `A%-B% (C%-D%)`; the other 27 are `3.15-7.10% (4.0%-9.0%)`, `5.50%-10.25%+ (7%-13%+)`, `6.4%+ (8%+)`, `<0.4% abw (<0.5% abv)`, `>4.0%`, empty, and four "varies" texts. The rules above cover each. Column precision checked: ABV decimal(4,1), OG/FG decimal(4,3), SRM decimal(5,1); no 2026 value loses precision.

---

## Phase 2: Seed BA styles with attribution

**Outcome:** `php artisan db:seed` loads 168 BA styles next to BJCP; every surface that shows BA guideline text carries the attribution line; the style picker still lists BJCP first. This is one task, because the seeder must not reach main before the attribution does (BA text without the line breaks the licence terms) and both are checked in one browser review.

**Design sketch:**
- Judging payload (`JudgingController`): each entry gains `style_source` and `style_attribution` (the attribution string for `ba_beer`, `null` otherwise).
- Judging style-guide bottom sheet and style-info sheet (`resources/views/judging/show.blade.php`, state in `resources/js/judging.js`): below the content, a small muted line with `style_attribution` when it is not null. The section text `<p>` renders `\n` as line breaks (`whitespace-pre-line`). BJCP entries look unchanged.
- Guide button `aria-label`s drop the hardcoded "BJCP": `View <section> guideline for <style name>`.
- Filament style edit page: for a `ba_beer` record, the attribution line appears as helper or placeholder text. Other sources unchanged.
- Both judging sheets get a max height with their own scroll and keep the close button on screen (added after gate round 1: BA guide text runs to 3,215 characters, against 434 for BJCP, and pushed the sheet above the viewport on a phone).
- `StyleMultiSelect` orders source groups by the key order of `Style::SOURCE_LABELS` (BJCP Beer first) instead of alphabetically by `source`, so `ba_beer` does not jump ahead of `bjcp_beer`. `ba_beer` is appended after `bjcp_cider` in `SOURCE_LABELS`, before `custom`.

Tasks:
- [ ] TASK-81: Add `BaStyleSeeder` (reads `base_path('ba_styleguide-2026.json')` by default; the path is a public property tests can override), add `'ba_beer' => 'Brewers Association'` to `Style::SOURCE_LABELS`, register the seeder in `DatabaseSeeder` after `BjcpStyleSeeder`, implement the design sketch above, update the two selectors that match the old `aria-label` (`tests/Browser/judging-style-guide-srm.spec.ts:14`, `scripts/journeys/m4-judge.mjs:47`) and the docs that say "BJCP guideline" (`docs/judging/reference.md`, `docs/judging/howto-judge.md`, `docs/user-journeys.md`), and update `planning/specs/styles.md` (BA source, code scheme, field mapping, attribution rule, the re-seed hazard below, and remove the drifted "other versions" and "custom styles" exclusions), the project `CLAUDE.md` "Styles and style sources" list, and `README.md` (the seed command, plus `php artisan db:seed --class=BaStyleSeeder --force` for an existing production database). Done when [TASK-81 AC 1-8]: (1) a PHPUnit feature test runs the seeder against a temp JSON fixture and asserts one row's code, source `ba_beer`, version `2026`, category_name, mapped text columns, numeric ranges and nulls, and that a second run leaves the row count unchanged; (2) a test seeding via `DatabaseSeeder` on an empty database finds the BJCP row count unchanged from before and 168 `ba_beer` rows; (3) a judging page feature test asserts `style_attribution` is the attribution string for a BA entry and null for a BJCP entry; (4) a Filament test asserts the edit page shows the attribution for a BA style and not for a BJCP style; (5) a test asserts `StyleMultiSelect` lists the BJCP Beer group before the Brewers Association group; (6) `npx playwright test tests/Browser/judging-style-guide-srm.spec.ts` passes, and `scripts/journeys/run-all.sh` fails no step that passes on main (reworded with Oscar, 2026-10-09: 10 journey steps already fail on main); (7) Oscar has checked the guide modal and style-info sheet for a BA entry in a browser (status Review until then); (8) at 390x844, a BA entry with a 3000+ character guide text opens both sheets with their top and close button inside the viewport, the text scrollable within the sheet, and the attribution not covered by the floating button (Playwright; added after gate round 1). Depends on Phase 1.
  Context:

Unknowns:
- Re-seeding a later BA edition would upsert by `(code, source)` and rewrite the styles that existing entries point to. Recorded in `planning/specs/styles.md` as a hazard; out of scope here.
- `DemoSeeder` attaches every style to "Autumn Harvest 2024" (`Style::pluck('id')`); demo entries stay BJCP (it filters `source = 'bjcp_beer'`). Journey a2 searches "Stout" and ticks the first match; with BJCP ordered first (design sketch) it keeps picking a BJCP stout. Criterion (6) confirms.
- [RISK] Licence scope for name-only surfaces. The plan puts the attribution wherever BA guideline text is shown (judging guide and style-info sheets, Filament edit page). BA style names and codes also appear without guideline text on the results sheet, judge report, entry form and Filament pickers. Whether a style name alone "references the beer style guidelines" is a reading of the licence, not a fact the code can settle. Accepted (Oscar, 2026-10-09): the attribution goes only where BA guideline text is shown; `planning/specs/styles.md` records this reading.

---

## Open decisions

None.
