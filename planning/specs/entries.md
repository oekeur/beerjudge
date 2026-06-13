# Entries

An entry is a beer a member submits to a competition for judging. One member, one style, one competition per entry row.

## Data model

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| competition_id | bigint | FK → competitions |
| member_id | bigint | FK → members (the brewer) |
| style_id | bigint | FK → styles |
| bottle_number | integer | Assigned at judging start; shown to judges instead of brewer name |
| sort_order | integer | Controls display order on judging screen and results sheet |
| brewer_notes | text | Optional notes from the brewer to the judges |
| timestamps | — | created_at, updated_at |

**Dropped from original PRD:** `name` (beer name field). Removed — the bottle number is the anonymous identifier during judging, and brewers can include a name in `brewer_notes` if they want.

**Unique constraint:** `(competition_id, member_id, style_id)` — one entry per member per style per competition.

## Submission rules

- Only allowed when the competition is in `open` status.
- The chosen style must be in the competition's accepted style set.
- A member cannot submit two entries of the same style in the same competition.
- Members submit their own entries; admins can manage entries for any member via Filament.

## Routes

`GET /competitions/{competition}/entries/create` — entry submission form showing available styles and the member's existing entries for this competition.
`POST /competitions/{competition}/entries` — stores the entry. Validated by `StoreEntryRequest`.

## Sort order

Entries have a `sort_order` column. In the Filament Competition > Entries tab, entries can be reordered via drag-and-drop. The order controls the display sequence on the judging screen and the results sheet.

## Bottle numbers

Bottle numbers are assigned by the admin during the transition to `judging` status. They identify entries to judges anonymously — judges see the bottle number and style, not the brewer's name or the beer's name.

## Admin management (Filament)

The Entries tab in CompetitionResource displays all entries as a repeater. Admins can edit any field (member, style, bottle number, brewer notes, sort order) and reorder entries via drag-and-drop.

## Out of scope

Entry withdrawal after submission (no delete route for members). Entry editing after the competition moves to `judging`. Multiple bottles per entry. Photo uploads. Entry fees.
