# Styles

The BJCP 2021 style guide is seeded once as the master catalogue. Each competition selects from this catalogue the styles it accepts.

## Data model

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| code | string | BJCP code, e.g. `23A`; unique together with `source` |
| source | string | `bjcp_beer`, `bjcp_mead`, `bjcp_cider`, or `custom` |
| version | string | nullable; `2021` for seeded BJCP styles, `null` for `custom` |
| category_name | string | e.g. `Pale American Ale` |
| category_description | text | nullable |
| name | string | Style name, e.g. `American Pale Ale` |
| og_min / og_max | decimal(5,3) | Gravity range |
| fg_min / fg_max | decimal(5,3) | |
| abv_min / abv_max | decimal(4,2) | |
| ibu_min / ibu_max | decimal(5,1) | |
| srm_min / srm_max | decimal(5,1) | |
| overall_impression | text | nullable |
| aroma | text | nullable |
| appearance | text | nullable |
| flavor | text | nullable |
| mouthfeel | text | nullable |
| comments | text | nullable |
| history | text | nullable |
| style_comparison | text | nullable |
| ingredients | text | nullable |
| examples | text | nullable (commercial examples) |
| tags | text | nullable |
| timestamps | — | created_at, updated_at |

## Seeding

`BjcpStyleSeeder` reads `bjcp_styleguide-2021.json` and upserts all styles by `(code, source)`. Running the seeder again is safe — it updates existing rows, not duplicates. The full BJCP 2021 style set is seeded by `php artisan migrate --seed` and by the demo seeder.

## Style guidelines on the judging screen

Each entry's judging card includes a collapsible panel with the style guide content for that entry's style: overall impression, aroma, appearance, flavor, mouthfeel, vital-stat ranges, and commercial examples. This is served into the page on load — no separate request.

## Admin management (Filament)

Admins can view and edit any style's content (useful for corrections to the seeded data), create new styles, and delete styles — but delete is restricted to styles with `source === 'custom'` (`StyleResource::canDelete()`); seeded BJCP styles can be edited but not deleted. The list is searchable and sortable.

## Multi-source styles

Styles carry a `source` column identifying where they came from: `bjcp_beer` (seeded by `BjcpStyleSeeder`), `bjcp_mead` / `bjcp_cider` (reserved, no seeders yet), or `custom` (created by admins through Filament). The upsert key for seeded styles is `(code, source)`. Admin-created custom styles are the only ones deletable through the UI (see above).

## Out of scope

Style versions other than BJCP 2021 for the seeded catalogue. Sub-styles or speciality-ingredient variants. Style-level rankings or statistics (planned — see roadmap).
