# CompetitionRankingService: design explanation

## The problem this solves

Results need to be computed from multiple judges' scoresheets, merged into a single ranked list, and served on every results page request. The data lives in three tables (`entries`, `scoresheets`, and their related members and styles). Without a dedicated service, this logic would leak into the controller and be duplicated if rankings ever needed to appear in a Filament widget or a batch-print view.

`CompetitionRankingService` centralises all ranking logic: score averaging, tiebreak handling, and BJCP band assignment.

## Why rankings are computed on request

Rankings are not stored. `rank()` runs a query and computes placement every time a results page loads.

The alternative is to persist placements in a `results` table when the competition closes. That approach has two problems for this app:

- Results can be corrected during `judging` status (a judge can resubmit a scoresheet). Persisted rankings would need to be invalidated and recomputed on each resubmission, adding a write-path concern.
- The club runs four competitions per year with 5-10 entries each. The query is trivially fast at this scale.

If the data set ever grew large enough for page latency to matter, caching the result of `rank()` keyed on `competition_id` would be a straightforward addition.

## Why `sortAndPlace()` exists as a static method

`rank()` and `sortAndPlace()` share the same sort comparator. The static method exists for callers that already have row data in memory and need to apply the same ordering without re-querying the database.

The primary use case is the Filament results widget, which may filter or reorder rows before displaying them. Rather than re-implementing the comparator inline, the widget calls `sortAndPlace()`.

## The tiebreak rule

The BJCP recommends breaking score ties on the overall impression score. Overall impression is the judge's holistic assessment of the beer, separate from the individual technical components. Two beers with the same total score but different overall impressions are meaningfully different: the beer that received higher overall marks was judged to be closer to its style ideal.

The comparator applies both sorts in one `<=>` chain:

```php
fn (array $a, array $b): int =>
    $b['avg_total'] <=> $a['avg_total']
    ?: $b['avg_overall'] <=> $a['avg_overall']
```

If `avg_total` differs, that determines placement. If `avg_total` is equal, `avg_overall` decides. If both are equal, the order is stable (PHP's `usort` is not guaranteed stable across all PHP versions, but `sort()` on a Collection uses `usort` internally; ties at both levels are extremely rare in practice).

## Why draft scoresheets are excluded

A judge saves scoresheets as `draft` while filling them in. The data is incomplete at that point. Including draft scores in rankings would produce misleading results: an entry scored by one judge who submitted plus one who only saved a draft would show a lower average than the true submitted score.

The filter in `rank()` is explicit:

```php
fn (Relation $query) => $query->where('status', ScoresheetStatus::Submitted)
```

This means an entry with zero submitted scoresheets gets `avg_total = 0.0` and places at the bottom. The results page should make this visible so admins can identify missing submissions.

## BJCP bands

The six bands (`Outstanding`, `Excellent`, `Very Good`, `Good`, `Fair`, `Problematic`) come directly from the BJCP scoresheet style. They give context to a numeric score: a 38 and a 44 are both "Excellent", while a 45 crosses into "Outstanding".

Bands are defined in `bands()` as a static array so they can be used by Blade views and Filament widgets without instantiating the service. The `band()` instance method uses them to map a float to a name.

## Related

- [Ranking service reference](ranking-service-reference.md)
- [Results reference](results-reference.md)
- [Lifecycle explanation](lifecycle-explanation.md)
