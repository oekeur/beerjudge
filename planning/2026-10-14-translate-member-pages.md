# Plan: Translate the member pages still in English

**File:** planning/2026-10-14-translate-member-pages.md
**Goal:** A Dutch member sees the home page, the results/score-sheet/judge-report pages and the registration and pending-approval messages in Dutch, and en/nl key drift fails a test.
**Out of scope:** Filament admin pages and the admin login (TASK-64); mails (already sent with `->locale()`); setting a new member's locale at registration (TASK-87, runs after TASK-109); a schema column telling "awaiting approval" apart from "switched off by an admin" (Oscar, 2026-10-14: one reworded message); the `name` field of `CompetitionRankingService::bands()`/`band()`, which `tests/Unit/BandLabelTest.php` pins to English (views translate by `band_key` instead).
**Constraints:** All three tasks write `lang/en.json` and `lang/nl.json`, so they run in sequence (107 -> 108 -> 109), each on its own branch, landed before the next starts. Every new key goes into both files. Keys are dotted and area-grouped; this plan introduces the new prefixes `home.*` and `results.*` (none exist yet) and reuses existing keys only where the English text stays identical. English rendering must not change except where a task says so.

---

## Phase 1: Home page and key-parity guard (TASK-107)

**Outcome:** The home page renders entirely from lang files; a unit test fails whenever en.json and nl.json hold different key sets.

Tasks:
- [ ] TASK-107: translate `resources/views/welcome.blade.php` and add the en/nl key-parity test. Done when: (a) a feature test logged in as a `locale => 'nl'` member, with one open, one judging and one closed competition, asserts the Dutch "Competitions" heading, the three Dutch section headings, the three Dutch link labels ("Judge entries", "Register entry", "View results") and a Dutch month abbreviation in the judging date, and asserts each of the English strings "Competitions", "Judging", "Past", "Judge entries", "Register entry", "View results" is absent; (b) with zero competitions the Dutch "no competitions yet" text appears and "No competitions yet." does not; (c) the existing English assertions in `tests/Feature/HomeTest.php` still pass unchanged; (d) a unit test asserts the sorted key sets of `lang/en.json` and `lang/nl.json` are equal, and passes. [AC 1, 2, new AC 3]
  Context: verified 2026-10-14: the view builds a `$sections` array (`judging => Judging`, `open => Open`, `closed => Past`) whose `$label` is used both as the section `<h2>` and as the status badge text. Keep that single label and translate it through new `home.section.{judging,open,closed}` keys with the current English values; do not switch the badge to `competition.status.*` (that would turn "Past" into "Closed" in English). The judging date uses `->format('j M Y')`; use `->translatedFormat('j M Y')` (Carbon follows `app()->setLocale`; probed: nl gives "19 okt. 2024"). en/nl keys are in parity at HEAD (273 each, jq diff empty).

Unknowns:
- None.

---

## Phase 2: Results, score sheet and judge report (TASK-108)

**Outcome:** results/index, results/show, results/judge-report and `results/partials/full-standings` render from lang files, with dates in the member's locale and Dutch word order.

Tasks:
- [ ] TASK-108: translate the three result views and the full-standings partial. Strings to move to `results.*` keys (inventory verified 2026-10-14; the views contain zero `__()` calls today):
  - `<x-page :title>` strings: "Results — ..." (index:1, show:6), "Judge Report — ..." (judge-report:5): use `__('results.title', [...])` style placeholders, not concatenated fragments.
  - headings: "— Results", "— Judge Report", "Your judge report", "Full Standings", "All results", "Print", "View sheet", "View judge report", "participants".
  - table column headers and labels in all four files, including "% ABV", " of N", "You ... · Avg ...".
  - plurals: `Str::plural('judge', ...)` (show:59) and `Str::plural('entry', ...)` (judge-report:38) become `trans_choice` keys with en and nl forms.
  - ordinals: the `match ($row['placement'] % 10)` suffix (show:41) becomes lang keys; English keeps st/nd/rd/th with the 11-13 rule, Dutch is always "e" (1e, 2e, 3e).
  - judge-report sentences: "Your scores were consistent...", "One entry stood out" / "N entries stood out" (one `trans_choice` key with a :count placeholder), "you scored higher/lower", "— worth a look below.", "largest gap", "entries not flagged as outliers", "outlier". Each sentence becomes one key with placeholders, not glued fragments.
  - section labels: `ucfirst($section)` (judge-report:110) and the section names in show: new `results.section.{aroma,appearance,flavour,mouthfeel,overall}` keys keeping today's English text (do not reuse `judging.section.*`; its "Overall Impression" would overflow the `w-20` span).
  - band pills: `{{ $row['band'] }}` (show:72, full-standings:26) becomes `__("judging.band.{$row['band_key']}.name")` (keys exist; `JudgingController` does the same).
  - dates (show:28, judge-report:27, `->format('F j, Y')`): `->translatedFormat(__('results.date_format'))` with en `F j, Y` and nl `j F Y`.
  Done when: (a) feature tests on a closed competition viewed by a `locale => 'nl'` member assert, per page: index: the Dutch page title, the Dutch translation of the "Full Standings" heading, a Dutch column header and a Dutch band name, and absence of "Full Standings", "View sheet", "Print"; show: the Dutch heading, a Dutch ordinal ("1e"), a Dutch plural for judges, a Dutch band name and a day-first Dutch date (e.g. "19 oktober 2024"), and absence of "Full Standings", "All results", "Print", "1st"; judge-report: the Dutch heading, the Dutch consistent-scores or outlier sentence, a Dutch section label and the day-first Dutch date, and absence of "Judge Report", "All results", "outlier"; (b) existing English assertions in `tests/Feature/ResultsAccessTest.php` and `tests/Feature/JudgeReportAccessTest.php` still pass unchanged, which proves English output is preserved; (c) every inventory item above is moved, checked by reading the diff against the list. [AC 1, 2]
  Context: verified 2026-10-14: results/index renders no judging date. `ResultsAccessTest` (106, 146, 156) and `JudgeReportAccessTest` (101, 111) assert English strings in the default en locale. No Playwright spec asserts these strings. Alpine `x-text` in these views holds only the sort glyphs.

Unknowns:
- Dutch strings are longer; the print layout (TASK-44, awaiting Oscar's manual print check) may wrap differently. Not a blocker; Oscar's review of this task should include a print preview of one Dutch results sheet.

---

## Phase 3: Registration notice and pending-approval message (TASK-109)

**Outcome:** The invite registration page notice follows the guest's locale; an inactive member who logs in is told an admin still has to approve the account, in their locale.

Tasks:
- [ ] TASK-109: move the notice in `resources/views/auth/register.blade.php` into a new `auth.register_notice` key (en + nl), and reword `auth.inactive_account` in both files to say the account is not active yet and an admin has to approve it, with an email to follow. Done when: (a) a test requests a valid invite registration page with `Accept-Language: nl` and asserts the Dutch notice and the absence of "After registering"; (b) `tests/Feature/MiddlewareTest.php:31` and `tests/Feature/AdminPanelAccessTest.php:36`, which assert the old Dutch text "Je account is niet actief.", are updated to the new Dutch wording and pass; (c) an en test asserts the new English wording mentions approval by an admin. [AC 1, 2]
  Context: verified 2026-10-14: login does not check `is_active`; `RequireActiveMember` runs on the next request, translates the message under the member's locale, then logs out, so the error shows in the member's locale even though the login page renders in guest locale. One message covers both pending and deactivated members (Oscar, 2026-10-14). Guest-locale test pattern: `tests/Feature/GuestLocaleTest.php` (`withHeaders(['Accept-Language' => 'nl'])`). Invite fixtures: `tests/Feature/InvitationTest.php`.

Unknowns:
- A guest who registers today is stored with `locale => 'en'` (`RegistrationController::store:47`), so until TASK-87 lands a Dutch registrant sees the pending message in English. TASK-87 is planned to run after this task and covers that path with its own test; recorded as a Backlog dependency.
- TASK-65 (Backlog, not committed to) would refuse inactive logins at the login step under the guest locale. If it is ever picked up, it must keep this message's locale behaviour. No action here.

---

## Open decisions

- None. Settled 2026-10-14: one reworded inactive message (no `approved_at`); the parity test rides with TASK-107.
