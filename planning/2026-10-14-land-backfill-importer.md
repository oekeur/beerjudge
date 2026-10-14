# Plan: Land the historical competition backfill importer

**File:** planning/2026-10-14-land-backfill-importer.md
**Goal:** `php artisan backfill:import` (command, test and the one-off Google Forms extract script) is on main through a PR, working against current main models and passing the full gate.
**Out of scope:** Completing the names/beers mapping CSVs (needs Oscar and production member IDs). Running the import against production. The `c487d4a` stash changes to `README.md`, `docker-compose*.yaml`, `.env.docker.example` and `planning/ops-launch-checklist.md` (infra edits unrelated to the importer, made against a base 335 commits old; not ported, stash left in place). Dropping stash@{0} or deleting the old branch (hard to reverse; offered in the report). Changing `Competition::booted` or the results mail.
**Constraints:** Never commit to main; work on a branch and PR (CLAUDE.md). New branch name `feat/backfill-importer`, cut from main, because `feat/backfill-historical-competitions` is 335 commits behind and keeps the only copy of the data until Phase 1 copies it out. Raw exports and mappings with personal data stay out of git (see Open decisions). Any new user-facing strings: none expected (artisan output is developer-facing English like `member:create`).

Verified 2026-10-14 by read: branch `feat/backfill-historical-competitions` holds one commit `489a17e` on merge-base `c8ee1d5`; `720d7dc` and `2ad56a3` are stash helper commits, `c487d4a` the stash itself. `489a17e` touches `app/Console/Commands/BackfillImportCommand.php`, `tests/Feature/BackfillImportCommandTest.php`, `backfill/extract_responses.py`, two `.xlsx` exports, two JSON files, `backfill/names_to_map.csv` (20 names), `backfill/beers_to_map.csv`. The stash's only importer-related change is a partly filled `backfill/beers_to_map.csv` (bjcp_code column), the rest is infra. `docs/backfill-queue.md` is the unrelated doc-backfill queue, not this importer. Repo `oekeur/beerjudge` is PRIVATE.

---

## Phase 1: Port the importer onto current main (Medium)

**Outcome:** On a branch cut from main, `backfill:import` creates a closed, already-mailed competition with entries, numbered bottles, and submitted scoresheets from the extracted JSON plus filled CSVs, and `--dry-run` writes nothing. The data files are preserved locally and untracked.

Tasks:
- [ ] TASK-38 (a): cut `feat/backfill-importer` from main and bring over only `BackfillImportCommand.php`, `BackfillImportCommandTest.php` and `backfill/extract_responses.py` from `489a17e` (`git checkout 489a17e -- <paths>`). Copy the `.xlsx`, JSON and CSV files (CSV version: the stash's `beers_to_map.csv`, `git show c487d4a:backfill/beers_to_map.csv`) into the gitignored `backfill/data/` and add that path to `.gitignore`; adjust the default paths in `extract_responses.py` to read and write there. Done when: [AC 1] `git ls-files backfill` lists only `extract_responses.py`, `git check-ignore backfill/data/lager-2025.json` succeeds, and `git diff main --stat` shows no file outside `app/Console/Commands/BackfillImportCommand.php`, `tests/Feature/BackfillImportCommandTest.php`, `backfill/extract_responses.py` and `.gitignore`.
  Context:
- [ ] TASK-38 (b): adapt the command to main's models (same file as (a); one writer). Verified 2026-10-14 by adversary probe on a scratch copy of main: the old `Competition::create([... 'status' => Closed])` throws under `Model::preventSilentlyDiscardingAttributes(! app()->isProduction())` (2 of the 3 old tests fail; in production it would silently save a draft). Fixes: (1) `Competition::forceCreate` with `status => closed` and `results_mailed_at => now()`; never `transitionTo()` or a later `save()` on a status change, because the `Competition::booted` `updated` hook mails every participant. (2) Call `BottleNumberAssignmentService::assign` after entry creation (results views print `#{{ bottle_number }}`; column nullable). (3) Fix the total-mismatch check: the extracted JSON stores section scores and `sheet_total` as numbers that decode as float vs int, so `(int) $sheetTotal !== $sheet->total` flags every row; compare numerically (`(float)` both sides). (4) Add a re-run guard: abort before writing, exit FAILURE, when a competition with the same name and year already exists (a second run otherwise duplicates the competition, entries and scoresheets, verified by probe). (5) Catch the `(competition_id, member_id, style_id)` unique constraint in validation: a brewer with two beers of the same style aborts with a message naming the beer. Done when: [AC 1] the test class, extended, passes with: closed status persisted and `results_mailed_at` not null; `Notification::fake()` shows no `CompetitionResultsNotification` after the import, and after `transitionTo(Draft)` then `transitionTo(Closed)` on the imported competition still none (a copy created without `results_mailed_at` must send one, so the test can fail); every entry has a non-null unique `bottle_number`; int-vs-float totals (fixture with `10.0` scores and int `sheet_total`) report zero mismatches and a genuinely different total reports one; a second import of the same JSON aborts with exit 1 and leaves one competition; the same-brewer-same-style case aborts readably; the original three tests still pass.
  Context: verified 2026-10-14 (adversary): Entry's saving hook needs the style in `competition_style` (the command attaches styles first, keep that order); `Scoresheet` `total` is computed in its saving hook; no other model break found.
- [ ] TASK-38 (c): run the importer end to end on a scratch SQLite database with `BjcpStyleSeeder` seeded, against the real extracted JSON and a throwaway, fully filled copy of the CSVs (fake member IDs created in the scratch DB). Known data facts (adversary, 2026-10-14): the stash's `beers_to_map.csv` uses codes absent from the seeded BJCP 2021 set (`7C`, `8C`, `27A`; ESB is `11C`), so dry-run aborts on them; in the throwaway copy map those rows to a valid code or `custom:<name>`; do not edit the real CSVs. `rarely-found-beers-2025.json` `sheet_total` differs from the section sum on all 124 rows (that form used another total, some above 50), so its mismatch list is expected noise. Done when: [AC 1] `--dry-run` then the real run exit 0 for `lager-2025.json` with 7 entries, 49 scoresheets and zero total mismatches, and for `rarely-found-beers-2025.json` with 8 entries and 124 scoresheets (its 124 mismatch lines expected); re-running either aborts without writing; the scratch database and any copied personal data are deleted afterwards and `git status` shows no stray file.
  Context:

Unknowns:
- The real `beers_to_map.csv` has rows with empty `bjcp_code` and `brewer_member_id` (Oscar's input, and member IDs are the production DB's), and some filled codes (`7C`, `8C`, `27A`) are BJCP 2015 codes missing from the seeded 2021 set. Not needed to land the code; needed before the production run. Recorded in Open decisions.
- The extract script needs `openpyxl`, which is not a project dependency. Local Python check not done; the script is documented as one-off. Not adding a dependency; the script's docstring states the requirement. Task (c) does not run it (it uses the already-extracted JSON).
- Whether the `Closed` + `results_mailed_at` pair is what TASK-89 (mark the demo's closed competition as mailed) also uses: sibling in the same planning wave; this plan assumes it sets `results_mailed_at` directly and does not touch `Competition`, so no overlap on files.

---

## Phase 2: Gate, PR, merge (Medium)

**Outcome:** The importer is on main.

Tasks:
- [ ] TASK-38 (d): run `vendor/bin/pint --dirty`, `vendor/bin/phpstan analyse` (level 10 over `app/`; invoke the `phpstan-fix` skill on errors) and `php artisan test --compact`; push the branch; open a PR with `.github/PULL_REQUEST_TEMPLATE.md` (Why and How sections, how to run the test and the dry run); wait for the 3 CI jobs (lint/phpstan, tests, tests-postgres); squash-merge (auto-merge is disabled in this repo, merge by hand once CI is green). Done when: [AC 1, 2] `gh pr view --json state,mergedAt` shows MERGED, all three CI jobs passed on the merged head, and the PR diff lists only the files named in task (a).
  Context:

Unknowns:
- `tests-postgres` runs the new test on Postgres; `LazilyRefreshDatabase` plus `forceCreate` should behave the same, unprobed until CI.

---

## Task acceptance criteria (TASK-38)

1. The importer is rebuilt on a branch from current main and the full test suite, PHPStan and Pint pass (replaces "Branch rebased on main and tests pass": the branch is rebuilt, not rebased, because the old branch carries unrelated data and stash content)
2. The branch is merged to main via a PR with all CI jobs green

## Open decisions

**Decided by Oscar, 2026-10-14:** raw exports and mapping CSVs stay out of git under the gitignored backfill/data/.


- [ ] Raw Google Forms `.xlsx` exports, extracted JSON (judges' free-text notes) and the names/beers mapping CSVs (first names, production member IDs). Recommended: keep out of git, under gitignored `backfill/data/`; the plan does this by default (the old branch keeps them as well). Alternative: commit them (repo is private). Oscar's call.
- [ ] Oscar completes (and corrects the 2015-era codes 7C, 8C, 27A to 2021 codes or `custom:`) `backfill/data/names_to_map.csv` and `beers_to_map.csv` (member IDs from production) before the production run; that run is a separate task.
- [ ] Drop `stash@{0}` and delete `feat/backfill-historical-competitions` once the PR is merged and the data is confirmed copied (offered, not done).
