# CompetitionRankingService reference

Class: `App\Services\CompetitionRankingService`

Registered in the service container as a singleton. Injected into `ResultsController`.

## Methods

### `rank(Competition $competition): Collection`

Computes the full ranked list for a competition. Returns one row per entry.

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `$competition` | `Competition` | The competition to rank |

**Return type:** `Collection<int, array{...}>` — see row shape below.

**Behaviour:**

1. Queries all entries for the competition via `Entry::where('competition_id', $competition->id)`.
2. Eager-loads `member`, `style`, and submitted scoresheets (filtered to `status = ScoresheetStatus::Submitted`), with each scoresheet's `judge` also loaded.
3. For each entry, computes `avg_total` and `avg_overall` by averaging across all loaded (submitted) scoresheets. Both values are rounded to 2 decimal places.
4. Assigns a `band` string via `$this->band($averageTotal)`.
5. Sorts descending by `avg_total`, then descending by `avg_overall` as tiebreaker.
6. Re-indexes the collection with `values()` so indices are 0-based.
7. Assigns `placement` as `index + 1`.

**Draft scoresheets are excluded.** Only scoresheets with `status = submitted` are counted. An entry with no submitted scoresheets gets `avg_total = 0.0`, `avg_overall = 0.0`, `sheet_count = 0`, and places at the bottom.

**Row shape:**

```php
[
    'entry'        => Entry,        // Eloquent model with member, style, scoresheets loaded
    'sheets'       => Collection,   // submitted Scoresheet models with judge loaded
    'avg_total'    => float,        // average of scoresheet.total, rounded to 2dp
    'avg_overall'  => float,        // average of scoresheet.overall, rounded to 2dp
    'sheet_count'  => int,          // number of submitted scoresheets
    'band'         => string,       // BJCP quality band name (see bands())
    'placement'    => int,          // 1-based rank position
]
```

### `sortAndPlace(Collection $rows): Collection` (static)

Sorts a pre-built collection of row arrays using the same comparator as `rank()`, then assigns `placement` values.

Use this when you already have row data and need to re-rank it (for example, after filtering out some entries).

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `$rows` | `Collection<array-key, array<string, mixed>>` | Rows already containing `avg_total` and `avg_overall` |

**Return type:** `Collection<int, array<string, mixed>>` — same shape as input, with `placement` set.

**Sort order:** descending `avg_total`, then descending `avg_overall`.

### `bands(): array` (static)

Returns the BJCP quality band definitions ordered from highest to lowest.

**Return type:** `array<int, array{min: int, key: string, name: string, range: string}>`

```php
[
    ['min' => 45, 'key' => 'outstanding', 'name' => 'Outstanding', 'range' => '45–50'],
    ['min' => 38, 'key' => 'excellent',   'name' => 'Excellent',   'range' => '38–44'],
    ['min' => 30, 'key' => 'very_good',   'name' => 'Very Good',   'range' => '30–37'],
    ['min' => 21, 'key' => 'good',         'name' => 'Good',        'range' => '21–29'],
    ['min' => 14, 'key' => 'fair',         'name' => 'Fair',        'range' => '14–20'],
    ['min' =>  0, 'key' => 'problematic',  'name' => 'Problematic', 'range' => '0–13'],
]
```

### `band(float $total): string`

Maps a numeric total to a BJCP band name. Iterates `bands()` in order; returns the `name` of the first band whose `min` is less than or equal to `$total`.

Falls back to `'Problematic'` if no band matches (unreachable under normal conditions since the last band has `min = 0`).

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `$total` | `float` | The average total score |

**Return type:** `string` — one of: `Outstanding`, `Excellent`, `Very Good`, `Good`, `Fair`, `Problematic`.

## Score components

Scoresheet totals are computed at save time in `Scoresheet::booting()`:

```php
$sheet->total = $sheet->aroma + $sheet->appearance + $sheet->flavour + $sheet->mouthfeel + $sheet->overall;
```

| Field | Max | Type |
|-------|-----|------|
| aroma | 12 | `unsignedTinyInteger` |
| appearance | 3 | `unsignedTinyInteger` |
| flavour | 20 | `unsignedTinyInteger` |
| mouthfeel | 5 | `unsignedTinyInteger` |
| overall | 10 | `unsignedTinyInteger` |
| total | 50 | `unsignedTinyInteger` (computed) |

## Tiebreak rule

When two entries have the same `avg_total`, the entry with the higher `avg_overall` places higher. This follows BJCP convention.

## Related

- [Ranking service explanation](ranking-service-explanation.md)
- [Results reference](results-reference.md)
- [Results how-to](results-howto.md)
