# BJCP Scoring Model

## The problem

Homebrew competitions need a scoring method that separates style-specific assessment from gut preference. Without a structured rubric, judges produce incomparable numbers: one judge's 35 is another's 25 for the same beer. Aggregating those scores into a ranking produces noise, not a result.

The secondary problem is tiebreaking. When two beers average to the same total, a competition needs a principled way to separate them rather than declaring a tie or falling back to arbitrary criteria.

## The approach

The app uses the BJCP 50-point scale. Judges score each beer across five sections:

| Section            | Max points | What it captures                        |
|--------------------|------------|-----------------------------------------|
| Aroma              | 12         | Hop, malt, yeast, and fermentation character by smell |
| Appearance         | 3          | Colour, clarity, head retention         |
| Flavour            | 20         | Taste balance, finish, aftertaste       |
| Mouthfeel          | 5          | Body, carbonation, warmth, creaminess   |
| Overall Impression | 10         | Holistic assessment; adherence to style |

The total is the sum of all five. It is computed server-side in the `Scoresheet` model's `saving` hook, not client-side, so the client cannot send a pre-summed total that disagrees with its components.

Score ranges map to named quality bands:

| Band          | Range  |
|---------------|--------|
| Outstanding   | 45–50  |
| Excellent     | 38–44  |
| Very Good     | 30–37  |
| Good          | 21–29  |
| Fair          | 14–20  |
| Problematic   | 0–13   |

**Per-entry ranking** uses the average total across all submitted scoresheets for that entry. Only sheets with `status = submitted` count; drafts are excluded. When two entries tie on average total, the tiebreak is the average overall-impression score. This matches the BJCP convention: overall impression captures the judge's holistic read, so it is a better discriminator than any single technical section.

The ranking logic lives in `CompetitionRankingService`. The sort is:

```php
->sort(fn (array $a, array $b): int =>
    $b['avg_total'] <=> $a['avg_total']
    ?: $b['avg_overall'] <=> $a['avg_overall']
)
```

Rankings are competition-wide, not per style. The club runs small competitions (5-10 beers) where a per-style ranking would produce one-entry categories. A single ranked list with style labels is more useful.

**Draft vs submitted.** A judge can save a sheet as a draft at any point during judging. Section scores are nullable for drafts; all five are required on submission. This lets the judging screen auto-save progress to the server without the judge having to complete the sheet in one sitting. A submitted sheet can be reopened and resaved (same `client_uuid` overwrites the row) until the competition closes.

Each scoresheet row carries a globally unique `client_uuid`. The server upserts on that field. Retried POSTs from the judging screen are idempotent.

## Trade-offs

**No per-style ranking.** Style categories are attached to entries for reference and display, but the final standings do not group by style. For a 10-person club, this is the right call: running per-style rankings on 8 beers across 6 styles produces thin, uninteresting brackets. A competition-wide list is more readable and more fun.

**Server-side total computation.** The `saving` hook recalculates `total` on every save, which means the client-submitted total is silently overwritten. A crafted POST with a forged total has no effect. The downside is a tiny overhead on every draft auto-save, which is acceptable.

**Average, not median.** The ranking uses `avg('total')` from Eloquent. With 3-6 judges, median and mean rarely differ enough to change placement. Mean is simpler to reason about and consistent with how BJCP scoresheets are traditionally aggregated.

**No BJCP report export.** The BJCP generates formal report data (flight sheets, scoresheets in their format) for sanctioned competitions. This app does not target sanctioned events and does not implement that export format.

## Related

- [`two-frontend-explanation.md`](two-frontend-explanation.md) — the Blade + Alpine judging screen that collects these scores
- [`../judging/explanation-offline-sync.md`](../judging/explanation-offline-sync.md) — how scoresheet data moves from the browser to the server
