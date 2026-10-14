# Plan: Mark the demo's closed competition as already mailed

**File:** planning/2026-10-14-demo-closed-competition-mailed.md
**Goal:** Reopening and closing DemoSeeder's 'Autumn Harvest 2024' on a demo database sends no results mail.
**Out of scope:** Any change to `Competition::booted` or the results-mail guard. Rewriting the historical finding in `docs/user-journeys.md` (a dated run record). Other seeders (BrowserTestSeeder has no closed competition).
**Constraints:** Backlog TASK-89 AC 1 is the end state. No new translation keys.

---

## Phase 1: Seed the closed demo competition as mailed

**Outcome:** A fresh `DemoSeeder` run leaves 'Autumn Harvest 2024' with `results_mailed_at` set, so the `updated` hook's `whereNull('results_mailed_at')` claim fails and nothing is sent on a later close.

Tasks:
- [ ] Add `'results_mailed_at' => now()` to the `Competition::forceCreate` call for comp1 in `database/seeders/DemoSeeder.php` (forceCreate bypasses fillable; the created event does not trigger the mail hook) — done when: a seeded 'Autumn Harvest 2024' has a non-null `results_mailed_at` [AC 1]
  Context: Verified 2026-10-14: DemoSeeder.php:38-43 creates comp1 with status 'closed'; `Competition::booted` only mails on `updated` when `wasChanged('status')`, claimed via `whereNull('results_mailed_at')`. Comp2 is judging and comp3 open, so only comp1 needs the field.
- [ ] Add a test to `tests/Feature/DemoSeederTest.php` (the only task writing this file): seed `DatabaseSeeder` and `DemoSeeder`, `Notification::fake()`, `transitionTo(Judging)` then `transitionTo(Closed)` on the demo competition, assert `Notification::assertNothingSent()` and `results_mailed_at` still non-null. Mirror `CompetitionResultsEmailTest::test_competition_already_marked_as_mailed_is_not_mailed_again` — done when: the test passes, and fails when the seeder line is removed (run once without it to confirm) [AC 1]
  Context: The demo seeds active participants with entries in comp1, so the assertion is not vacuous; the test should also assert the competition has at least one active participant (`activeParticipants()->exists()`).

Unknowns:
- None found after checking the usual sources: the change is one seeder field; the hook's guard and its existing test were read.

---

## Open decisions

- [ ] None.
