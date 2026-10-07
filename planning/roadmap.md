# Roadmap
2026-06-13 21:55
Features planned or specced but not yet built. Ordered by value per unit of effort, consistent with the enhancements PRD priority order.

---

## 1. Email on publish (built)

Built: see `specs/results.md`, section "Results email". The notes below are the original design questions.

**Spec:** `requirements/homebrew-competition-enhancements-prd.md` — Enhancement 4.

When an admin closes a competition (currently: advances to `closed` status, since `published_at` was dropped), the app emails each member with an entry a link to their personal results sheet.

Key design decisions to settle before building:
- The trigger was originally `published_at` being set; with that field gone, the trigger is the transition to `closed` status via Filament.
- The link can be login-gated (current `/competitions/{id}/results/{member}` route) or a signed URL. Signed URL removes the need for the recipient to have a session; either approach is valid.
- Guard against double-sends if the status bounces (unlikely with the current lifecycle, but worth a check on the queued job).
- Manual resend action in Filament for members who didn't receive.

Prerequisites: mail transport configured (Mailgun, Postmark, or SMTP), queue worker running.

---

## 2. History and stats

**Spec:** `requirements/homebrew-competition-enhancements-prd.md` — Enhancement 5.

Turn the competition archive into something members look at. All data is already in existing tables; no schema changes needed.

Planned surfaces:
- **Per-member history** — entries across competitions, score trend over time, best results, average score by style.
- **Club-wide stats** — winner per competition, most-entered styles, score distributions, participation counts.
- **Per-style stats** — how a style tends to score across competitions.

Implementation: Filament pages and chart widgets for the admin view; optionally a read-only "my history" page for members on the non-Filament side.

---

## 3. Blind guess competition type

**Full plan:** `features/blind-guess-competition.md` — implementation-ready, covers database, backend, frontend, and tests in detail.

A second competition format where judges guess the style, brewer, and five vital statistics (OG, FG, ABV, IBU, SRM) instead of scoring on the BJCP scale. Points awarded by comparing guesses to the actual entry data after close (max 45 pts). Results rank judges by accuracy rather than ranking entries by score.

High-level scope:
- Add `type` enum to `competitions` (`bjcp` / `blind_guess`).
- New `blind_guesses` table + `BlindGuess` model + `BlindGuessScorer` service.
- New `BlindGuessController` with the same upsert/idempotency pattern as `ScoresheetController`.
- Judging screen branches on `competitionType`: hides brewer/style identity, shows guess dropdowns and stat inputs instead.
- New results view (`results/blind_guess_show.blade.php`) with a judge leaderboard.
- Comprehensive test suite (unit scorer tests + feature tests for upsert, judging screen, results).

The existing lifecycle, offline-first judging, and IndexedDB sync are all reused unchanged.
