# Competition lifecycle reference

## Status values

Defined in `App\Enums\CompetitionStatus`.

| Value | Label | Filament color |
|-------|-------|----------------|
| `draft` | Draft | gray |
| `open` | Open | info |
| `judging` | Judging | warning |
| `closed` | Closed | success |

The enum implements `HasLabel` and `HasColor` for Filament integration. Labels resolve via `competition.status.{value}` translation keys.

## Database schema

Table: `competitions`

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | bigint unsigned | no | auto | |
| `name` | varchar | no | | |
| `year` | smallint unsigned | no | | |
| `judging_date` | date | yes | null | |
| `status` | enum | no | `draft` | Values: `draft`, `open`, `judging`, `closed` |
| `created_at` | timestamp | yes | | |
| `updated_at` | timestamp | yes | | |

## Competition model

Class: `App\Models\Competition`

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$id` | `int` | Primary key |
| `$name` | `string` | Display name |
| `$year` | `int` | Competition year |
| `$status` | `CompetitionStatus` | Cast to enum |
| `$judging_date` | `Carbon\|null` | Cast to date |
| `$judges` | `Collection<int, Member>` | Pivot: `competition_judge` |
| `$entries` | `Collection<int, Entry>` | Has-many |

### Status check methods

All four return `bool`.

```php
$competition->isDraft()   // status === CompetitionStatus::Draft
$competition->isOpen()    // status === CompetitionStatus::Open
$competition->isJudging() // status === CompetitionStatus::Judging
$competition->isClosed()  // status === CompetitionStatus::Closed
```

### Relationships

| Method | Return type | Pivot / FK |
|--------|-------------|------------|
| `styles()` | `BelongsToMany<Style>` | `competition_style` |
| `judges()` | `BelongsToMany<Member>` | `competition_judge` |
| `entries()` | `HasMany<Entry>` | `entries.competition_id` |

## Filament lifecycle actions

All actions are defined in `App\Filament\Resources\Competitions\Pages\EditCompetition::getHeaderActions()`. Each action is conditionally shown based on the current status.

| Action key | Visible when | Transition | Requires confirmation |
|-----------|-------------|------------|----------------------|
| `moveToOpen` | `draft` | `draft` → `open` | No |
| `moveToJudging` | `open` | `open` → `judging` | Yes |
| `moveToClosed` | `judging` | `judging` → `closed` | Yes |
| `openJudgingScreen` | `judging` | — (opens URL) | No |
| `viewResults` | `closed` | — (opens URL) | No |

`moveToJudging` also calls `BottleNumberAssignmentService::assign()` before updating status. See [bottle-assignment-reference.md](bottle-assignment-reference.md).

After each status update, the page calls `$this->refreshFormData(['status'])` to re-render the status badge without a full page reload.

## Valid transitions

Transitions are enforced by the UI (only the appropriate action button appears). There is no server-side state machine guard.

```
draft → open → judging → closed
```

Reversal is not supported. Moving backwards requires a direct database update.

## Entry constraints during the lifecycle

| Status | Entry registration | Bottle numbers | Judging | Results visible to members |
|--------|-------------------|---------------|---------|---------------------------|
| `draft` | No | Not assigned | No | No |
| `open` | Yes | Not assigned | No | No |
| `judging` | Locked | Assigned | Yes | No |
| `closed` | Locked | Assigned | Locked | Yes |

## Related

- [Lifecycle how-to](lifecycle-howto.md)
- [Lifecycle explanation](lifecycle-explanation.md)
- [Bottle assignment reference](bottle-assignment-reference.md)
- [Results reference](results-reference.md)
