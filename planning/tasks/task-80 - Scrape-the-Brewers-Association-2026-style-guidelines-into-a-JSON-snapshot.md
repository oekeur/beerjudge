---
id: TASK-80
title: Scrape the Brewers Association 2026 style guidelines into a JSON snapshot
status: Done
assignee:
  - '@agent'
created_date: '2026-10-09 11:57'
updated_date: '2026-10-09 12:05'
labels:
  - multi-source-styles
  - style-guidelines
dependencies: []
documentation:
  - planning/2026-10-09-scrape-ba-2026-styles.md
  - planning/specs/styles.md
priority: low
type: feature
ordinal: 79000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
No structured BA dataset exists. Add an artisan command that scrapes the BA 2026 style page into ba_styleguide-2026.json (BA-001..BA-168, fields mapped onto our guide sections), and commit the file. Plan: planning/2026-10-09-scrape-ba-2026-styles.md (Phase 1).
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 A PHPUnit feature test fakes the HTTP response with a five-style, two-group fixture (normal; 'Varies with style' stats; SRM '40+(80+ EBC)' + ABV '6.4%+ (8%+)' + no Additional notes; ABV '<0.4% abw (<0.5% abv)'; IBU '5-30; may differ significantly from perceived bitterness'), runs styles:scrape-ba with --output at a temp file, and asserts the exact JSON per style, including codes BA-001..BA-005, the h2 category and ABV from the bracketed volume range
- [x] #2 With --output at a temp path that does not exist, the command exits non-zero with a message and the path still does not exist when the faked page yields zero styles, or when a style lacks one of the seven always-present labels
- [x] #3 After the test suite runs, git status shows ba_styleguide-2026.json unmodified
- [x] #4 The committed ba_styleguide-2026.json holds 168 styles, codes BA-001..BA-168 with no gaps, 12 distinct category values, every non-null og_min between 1.000 and 1.200, and at least 140 styles each with non-null ibu_min and srm_min
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
Full plan: planning/2026-10-09-scrape-ba-2026-styles.md (Phase 1: Scraper and JSON snapshot)
Goal: committed ba_styleguide-2026.json scraped from the BA 2026 page by styles:scrape-ba.
Phase 1 - Scraper: artisan command with --output (default repo root), Laravel HTTP client + Dom\HTMLDocument, codes BA-001.. in page order, h2 group as category, labelled parts mapped to appearance/aroma/flavor/mouthfeel/overall_impression, leading-token range parsing (ABV from bracketed volume), fail loudly on zero styles or a missing label; fixture test writes to a temp path [AC 1,2,3]
Phase 1 - Snapshot: run once against the live page and commit the JSON [AC 4]
Open (non-blocking): none
<!-- SECTION:PLAN:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
Landed in 9864c11 (squash of PR #35, feat(styles): scrape the Brewers Association 2026 style guidelines #80).
AC1: tests/Feature/ScrapeBaStylesCommandTest.php fakes the page with tests/Fixtures/ba-style-guidelines.html (5 styles, 2 h2 groups, all five shapes) and assertSame on the JSON per style; 4 tests pass on the branch and all 4 error at the merge base (command missing).
AC2: zero-style and missing-label tests assert failure, message and assertFileDoesNotExist on a temp --output; an HTTP-error test also asserts failure and no file (it does not assert the message).
AC3: after the test file ran in the task worktree, git status --short was empty.
AC4: committed JSON: 168 styles, BA-001..BA-168 contiguous, 12 categories, og_min 1.008-1.092, ibu_min non-null 143, srm_min non-null 141. Pass B re-ran the command on the downloaded page and reproduced the file exactly; an independent Python parser matched every field of all 168 styles.
28 styles have one or more stats null on both ends; spot-checked against the page (Varies with style, N/A, May vary widely, colour of fruit).
As planned. Deviations within the contract: the command also fails on an HTTP error; numbers without a decimal point are written as ints. Noted, not filed: an --output into a missing directory ends in an uncaught ErrorException (non-zero exit, no file).
<!-- SECTION:FINAL_SUMMARY:END -->
