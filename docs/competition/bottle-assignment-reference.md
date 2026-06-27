# BottleNumberAssignmentService reference

Class: `App\Services\BottleNumberAssignmentService`

Resolved from the service container. Called by `EditCompetition::getHeaderActions()` during the `open` → `judging` transition.

## Method

### `assign(Competition $competition): void`

Assigns sequential bottle numbers to every entry in the competition.

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `$competition` | `Competition` | The competition whose entries will receive bottle numbers |

**Return type:** `void`

**Behaviour:**

1. Opens a database transaction.
2. Queries all entries for the competition via `$competition->entries()->orderBy('id')`.
3. Iterates with a counter starting at 1.
4. Calls `$entry->update(['bottle_number' => $i++])` for each entry.
5. Commits the transaction.

If any individual update fails, the transaction rolls back and no bottle numbers are changed.

**Ordering:** entries are ordered by `entries.id` (insertion order). The first entry created has bottle number 1, the second has 2, and so on.

**Idempotency:** calling `assign()` a second time overwrites existing bottle numbers. The sequence restarts at 1. Numbers are not randomised between calls.

## Database constraints

The `entries` table has a unique constraint on `(competition_id, bottle_number)`. Within a single competition, no two entries share a bottle number. The constraint is enforced at the database level; the transaction in `assign()` guarantees the full assignment is atomic, so partial states that would violate the constraint cannot persist.

## Entry model columns

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| `bottle_number` | `unsignedSmallInteger` | yes | Null until assigned; unique per competition |
| `sort_order` | `int` | yes | Controls judging order in the judging screen; independent of bottle number |

Bottle numbers and sort order are separate fields. Sort order is managed by admins via drag-and-drop in the Filament entry list. Bottle numbers are assigned at judging transition time.

## When `assign()` is called

`BottleNumberAssignmentService::assign()` is called exactly once per competition, inside the `moveToJudging` Filament action in `EditCompetition::getHeaderActions()`. The sequence is:

```php
app(BottleNumberAssignmentService::class)->assign($record);
$record->update(['status' => CompetitionStatus::Judging]);
```

Bottle number assignment runs before the status update. If assignment fails, the status remains `open`.

## Related

- [Bottle assignment explanation](bottle-assignment-explanation.md)
- [Lifecycle reference](lifecycle-reference.md)
- [Lifecycle how-to](lifecycle-howto.md)
