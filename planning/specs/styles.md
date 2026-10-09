# Styles

The BJCP 2021 and Brewers Association (BA) 2026 style guides are seeded as the master catalogue. Each competition selects from this catalogue the styles it accepts. Admins can also create custom styles.

## Data model

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| source | string | `bjcp_beer`, `ba_beer`, `custom`. Identity key together with `code` |
| code | string | Unique per source: BJCP code (`23A`) or BA code (`BA-001`..`BA-168`) |
| version | string | `2021` (BJCP), `2026` (BA), null for custom |
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

`BjcpStyleSeeder` reads `bjcp_styleguide-2021.json` and upserts by `(code, source)`. `BaStyleSeeder` reads `ba_styleguide-2026.json` (produced by `styles:scrape-ba`) and does the same for `ba_beer`. Running either again is safe: it updates existing rows, not duplicates. `DatabaseSeeder` runs both, BJCP first. For an existing production database run `php artisan db:seed --class=BaStyleSeeder --force`.

### BA field mapping

| JSON | Column |
|---|---|
| `code` | `code` (`BA-001`..`BA-168`) |
| `name` | `name` |
| `category` | `category_name` |
| `appearance`, `aroma`, `flavor`, `mouthfeel`, `overall_impression` | same-named columns; labelled parts joined by `\n` |
| `og_*`, `fg_*`, `abv_*`, `ibu_*`, `srm_*` | same-named columns, null when absent |
| `edition` | `version` |
| (constant) | `source` = `ba_beer` |
| (none) | `category_description`, `comments`, `history`, `style_comparison`, `ingredients`, `examples`, `tags` = null |

### Re-seed hazard

The upsert key is `(code, source)`, so seeding a later BA edition would rewrite the rows that existing entries point to, changing the style text of past competitions. Other BA editions are out of scope; adding one needs a new source key (e.g. `ba_beer_2027`) or a data migration.

## Attribution

The BA licence requires an attribution line wherever BA guideline text is shown: "Based on Brewers Association 2026 Beer Style Guidelines with changes. Used with permission of Brewers Association." The text lives in `Style::SOURCE_ATTRIBUTIONS` (English, not translated). Reading agreed 2026-10-09: it appears once at the top of an expanded entry on the judging screen, on the style-info sheet and on the Filament style edit page; not on the per-aspect style-guide sheets, and not on pages that show only a style's code or name (results sheet, judge report, entry form, pickers). The judging payload carries `style_source` and `style_attribution` per entry.

The style picker (`StyleMultiSelect`) orders source groups by the key order of `Style::SOURCE_LABELS`.

## Style guidelines on the judging screen

Each entry's judging card includes a collapsible panel with the style guide content for that entry's style: overall impression, aroma, appearance, flavor, mouthfeel, vital-stat ranges, and commercial examples. This is served into the page on load — no separate request.

## Admin management (Filament)

StyleResource in Filament lets admins view, edit and create styles. The `source` field is disabled in the form; custom styles are created with source `custom`. The list is searchable and sortable.

## Out of scope

BA editions other than 2026. Translating BA text. Sub-styles or speciality-ingredient variants. Style-level rankings or statistics (planned — see roadmap).
