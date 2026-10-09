# BA Style Seeder Reference

`BaStyleSeeder` loads the Brewers Association 2026 beer style snapshot into the `styles` table. It runs as part of `php artisan migrate --seed` and can be re-run at any time without duplicating rows.

---

## Class

`Database\Seeders\BaStyleSeeder`

Registered in `Database\Seeders\DatabaseSeeder::run()`.

---

## Data source

Reads `ba_styleguide-2026.json` in the project root (`base_path('ba_styleguide-2026.json')`). The file is a committed snapshot written by the `styles:scrape-ba` command. If the file is missing or malformed, the seeder exits silently without error.

**Expected JSON structure:**

```json
{
  "edition": "2026",
  "source_url": "...",
  "scraped_at": "...",
  "styles": [ { "code": "BA-001", "name": "...", "category": "..." } ]
}
```

A style entry without `code` or `name` is skipped.

---

## Fields populated

| `styles` column | Source JSON field | Notes |
|-----------------|-------------------|-------|
| `code` | `code` | e.g. `"BA-001"` |
| `source` | hardcoded | Always `"ba_beer"` |
| `category_name` | `category` | Empty string when missing |
| `name` | `name` | |
| `version` | top-level `edition` | Falls back to `"2026"` when the file has no edition |
| `og_min` / `og_max` | `og_min` / `og_max` | Numeric values only, otherwise `null` |
| `fg_min` / `fg_max` | `fg_min` / `fg_max` | Same |
| `abv_min` / `abv_max` | `abv_min` / `abv_max` | Same |
| `ibu_min` / `ibu_max` | `ibu_min` / `ibu_max` | Same |
| `srm_min` / `srm_max` | `srm_min` / `srm_max` | Same |
| `overall_impression` | `overall_impression` | Nullable |
| `aroma` | `aroma` | Nullable |
| `appearance` | `appearance` | Nullable |
| `flavor` | `flavor` | Nullable |
| `mouthfeel` | `mouthfeel` | Nullable |
| `category_description`, `comments`, `history`, `style_comparison`, `ingredients`, `examples`, `tags` | none | Always `null`; the BA guide has no such fields |

---

## Upsert behaviour

The seeder calls `Style::upsert()` with the unique key `['code', 'source']` and updates every other column. A re-run overwrites style data and creates no duplicates. `source` is part of the identity key and is never in the update list.

---

## Attribution

The BA licence requires an attribution line wherever the guideline text is shown. The text lives in `Style::SOURCE_ATTRIBUTIONS['ba_beer']` (English, not translated). The judging screen shows it once at the top of an expanded entry and at the end of the style-info sheet; the Filament style edit page shows it too. It is not repeated on the per-aspect guide sheets.

---

## Seeding an existing database

A database that predates the BA styles gets them with:

```bash
php artisan db:seed --class=BaStyleSeeder --force
```

The command is safe to repeat. `php artisan migrate --seed` on a fresh database seeds BJCP and BA styles together.

---

## Related

- [BJCP seeder reference](bjcp-seeder-reference.md)
- [How to seed or refresh BJCP styles](bjcp-seeder-howto.md)
