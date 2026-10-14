# Results reference

## Routes

Defined in `routes/web.php`. All three results-area routes (`results.index`, `results.show`, `judge-report.show`) require `auth` and `active-member` middleware. `GET /competitions/{competition}/judge-report/{judge}` returns 404 if `{judge}` is not on the competition's judge list (a judge who stopped participating has no report, though their submitted sheets still count in the standings); admins can view it at any status, other members only their own report once the competition is closed.

### Results index

```
GET /competitions/{competition}/results
```

Named route: `results.index`

Renders `resources/views/results/index.blade.php`. Every permitted viewer sees the full standings. A viewer with an entry gets a link to their own sheet, and a viewer registered as a judge gets a link to their own judge report. Admins also see every member with entries (ordered by name) with links to each sheet, and every registered judge's report.

**Access rules:**

| Role | Condition | Result |
|------|-----------|--------|
| Admin | Any status | 200 |
| Member | `status = closed` | 200 |
| Member | Any other status | 403 |

### Member results sheet

```
GET /competitions/{competition}/results/{member}
```

Named route: `results.show`

Renders `resources/views/results/show.blade.php` with the full rankings and the named member's individual scoresheet rows.

**Access rules:**

| Role | Condition | Result |
|------|-----------|--------|
| Admin | Any status, any member | 200 |
| Member | `status = closed` AND `member.id == authenticated member` | 200 |
| Member | `status = closed` AND `member.id != authenticated member` | 403 |
| Member | Any other status | 403 |

Route segments must be integers (`whereNumber` constraint). Non-integer values return 404.

## Controller

Class: `App\Http\Controllers\ResultsController`

### Constructor

```php
public function __construct(private readonly CompetitionRankingService $ranking)
```

`CompetitionRankingService` is injected by the service container.

### `show(Competition $competition, Member $member, Request $request): View`

1. Resolves the authenticated member via `$this->member($request)`.
2. Aborts with 403 if the viewer is not an admin and the competition is not closed.
3. Aborts with 403 if the viewer is not an admin and `viewer->id !== member->id`.
4. Calls `$this->ranking->rank($competition)` to compute the full ranked list.
5. Filters that list to rows where `entry->member_id === member->id` to build `$memberEntries`.
6. Passes four variables to the view: `$competition`, `$member`, `$rankedEntries`, `$memberEntries`.

### `index(Competition $competition, Request $request): View`

1. Resolves the authenticated member via `$this->member($request)`.
2. Aborts with 403 if the viewer is not an admin and the competition is not closed.
3. Calls `rank()` for the standings and loads the viewer's own entry and judge registration. For admins only, it also loads the members with entries and the registered judges, both ordered by name.
4. Renders `results.index` with `$competition` and `$members`.

## View data

### `results.show`

| Variable | Type | Description |
|----------|------|-------------|
| `$competition` | `Competition` | The competition model |
| `$member` | `Member` | The member whose sheet is being viewed |
| `$rankedEntries` | `Collection` | All entries ranked; see [ranking service reference](ranking-service-reference.md) for shape |
| `$memberEntries` | `Collection` | Subset of `$rankedEntries` filtered to this member's entries |

### `results.index`

| Variable | Type | Description |
|----------|------|-------------|
| `$competition` | `Competition` | The competition model |
| `$members` | `Collection<Member>` | Admins only: members with at least one entry, ordered by name (empty otherwise) |
| `$judges` | `Collection<Member>` | Admins only: registered judges, ordered by name (empty otherwise) |
| `$isAdmin`, `$viewerId` | `bool`, `int` | Viewer context |
| `$rankedEntries` | `Collection` | All ranked rows from `rank()` |
| `$ownEntry` | `Entry\|null` | The viewer's first entry in this competition |
| `$isJudge` | `bool` | Whether the viewer is a registered judge |

## Ranking in results

Rankings are computed fresh on each request by `CompetitionRankingService::rank()`. There is no cached or persisted ranking. Only scoresheets with `status = submitted` count. See [ranking service reference](ranking-service-reference.md) for the full computation.

## Related

- [Results how-to](results-howto.md)
- [Ranking service reference](ranking-service-reference.md)
- [Lifecycle reference](lifecycle-reference.md)
- Judging screen reference: `../judging/reference.md`
