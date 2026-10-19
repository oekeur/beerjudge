# Competitions

A competition is the container for one judging event. It holds a set of accepted styles, a list of entries, a judging panel, and eventually a set of results. The club runs roughly four per year.

## Data model

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | e.g. "Autumn Harvest 2024" |
| year | integer | Calendar year |
| judging_date | date | When the judging session takes place |
| status | enum | `draft`, `open`, `judging`, `closed` |
| timestamps | — | created_at, updated_at |

**Dropped from original PRD:** `published_at`. Originally used to gate member visibility of results separately from the `closed` status. Removed — results access is now purely status-based (see `results.md`).

**Pivots:**
- `competition_style` — which BJCP styles the competition accepts (many-to-many with `styles`)
- `competition_judge` — which members participate as judges (many-to-many with `members`)

## Lifecycle

```
draft → open → judging → closed
```

**`draft`** — admin sets up name, year, judging date, and accepted styles. Not visible to regular members.

**`open`** — members can submit entries. The competition appears on the home page with a "Submit entry" link.

**`judging`** — entry registration locks. Bottle numbers are assigned. The judging screen is accessible. Judges can create, edit, and resubmit their scoresheets. Members can no longer add or edit entries.

**`closed`** — everything locks. No new scoresheets, no edits to existing ones. Results are final and visible to members.

Status transitions are manual — an admin changes the status with the lifecycle buttons on the Filament edit page (the Status field is read-only and `status` is not mass assignable). There is no automatic promotion.

## Style selection

Each competition has a set of accepted styles from the BJCP 2021 catalogue. An entry's style must be in the competition's accepted set. The admin selects styles in the Filament Competition > Styles tab via a categorised checkbox component.

If the accepted set is empty the competition is open to any style (not current practice but supported by the model).

## Admin management (Filament)

The CompetitionResource has a multi-tab edit interface:

- **Overview** — name, year, judging date, status
- **Styles** — categorised style selector (custom `StyleMultiSelect` component)
- **Entries** — repeater for managing entries: member, style, ABV, brewer notes, sort order (drag-to-reorder, Sort by ABV). Editable in `draft` and `open`; read-only from `judging` on (`judging`, `closed`, reopened `judging`)
- **Judging** — select judges, display judge participation link, judging progress visualisation
- **Dashboard** — results summary with entry statistics
- **Results** — variance table showing judge score spread per entry

## Out of scope

Multiple competitions running simultaneously for the same members (no enforcement, but not the intended use). Per-style rankings or category placements — rankings are competition-wide. Automatic status transitions. Multi-club support.
