# Homebrew Competition Tool: Enhancements PRD

Builds on the v1 PRD. Five enhancements, in the order to build them. Each ships independently, so none blocks the others; the order reflects value per unit of effort. Terminology (members, entries, scoresheets, styles, results sheet, judging screen) carries over from v1.

## 1. Style guidelines next to judging - DONE

Judges score better when the target is in front of them. Show the BJCP description and vital stats for the entry's style on the judging screen, so people score to style instead of from memory.

Extend `styles` with the guideline content: a `description` (the full style text, markdown), `commercial_examples`, and the vital-stat ranges already noted (OG, FG, ABV, IBU, SRM). Seed it from the BJCP 2021 data alongside the style list itself.

On the judging screen, each entry shows its style code and name with a collapsible panel holding the description, commercial examples, and the stat ranges. Read-only, and server-rendered into the page like the rest, so it works without a live connection.

One thing to settle first: confirm the BJCP's redistribution terms for the guideline text and include attribution. Bundling the full text is a content decision, not a code one.

Effort: low, mostly seed data and a panel.

## 2. Off-flavor descriptor checkboxes - DONE

Free-text notes are slow to write on a phone and uneven to read back. Add structured descriptor tick boxes to the scoresheet, the way the paper BJCP sheet does, for faster judging and clearer feedback.

A `descriptors` reference table (id, name, slug, kind: fault or style-trait) seeded with the common ones: acetaldehyde, alcoholic/hot, astringent, diacetyl, DMS, estery, grassy, light-struck, metallic, musty, oxidized, phenolic, solvent, sour, sulfur, vegetal, yeasty, and a few positives. A `scoresheet_descriptor` pivot (scoresheet_id, descriptor_id, optional intensity: low/med/high) records what each judge flagged.

The descriptors ride along in the same offline payload, so `POST /scoresheets` carries them with the sheet and the retry/idempotency rules already cover them. On the results sheet, summarize which descriptors multiple judges flagged for a beer, which is more useful to the brewer than any single note.

Keep the list short and curated to start; let it grow from use. Intensity is optional in the first cut.

Effort: low to medium.

## 3. Judge variance flagging

With several sheets averaged per entry, one strict or lenient judge can move a result. Surface the disagreement so you can act on it before locking, without auto-correcting anyone's scores.

Per entry, compute the spread of submitted totals (range, or standard deviation). Flag entries whose spread crosses a tunable threshold (a range over roughly 7 points is a sensible default). Show the flag in Filament as a badge on the entry, so an admin can prompt a re-taste or a quick discussion before publishing.

No new tables; it's a computed view over `scoresheets`. Optionally add a `reviewed_at` on the entry so an admin can mark a flag as handled. Define the small-n cases: one sheet means no variance, two sheets means the flag is just their difference.

Normalization stays out of scope. This only surfaces outliers; it doesn't adjust scores.

Effort: low, a query plus a Filament column.

## 4. Email on publish

Sharing results by hand is the kind of step that gets skipped. When an admin publishes a competition (sets `published_at`), email each participant a link to their results sheet.

Publishing dispatches a queued job that emails every member with an entry in that competition, one message each, linking to their per-participant sheet. The link is login-gated (or a signed, expiring URL) so results stay private until published. Guard against double-sends on a re-publish, with a manual resend action for stragglers.

This needs a mail transport (Mailgun, Postmark, or a self-hosted SMTP) and a running queue worker, neither of which v1 requires. A registration-deadline reminder is a close sibling and can reuse the same plumbing.

Effort: low, once mail and the queue are set up.

## 5. History and stats

The v1 decision to keep every year's data makes this cheap. Turn the archive into something people look at.

Per member: their entries across competitions, score trend over time, best results, average by style. Club-wide: winner per competition (top overall), most-entered styles, score distributions, participation. Per style: how styles tend to score across competitions.

All of it reads from existing tables, so there's no new schema; at this scale plain queries are fine, and caching can wait until something feels slow. Surface it as Filament pages and chart widgets, plus an optional read-only "my history" page for members. Settle one definition up front: the winner is the top overall entry per competition, consistent with the v1 ranking.

Effort: medium, mostly queries and presentation.
