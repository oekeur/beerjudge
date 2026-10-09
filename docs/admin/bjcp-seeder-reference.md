# BJCP Style Seeder Reference

`BjcpStyleSeeder` loads the full BJCP 2021 beer style dataset into the `styles` table. It runs as part of `php artisan migrate --seed` and can be re-run at any time without duplicating rows.

---

## Class

`Database\Seeders\BjcpStyleSeeder`

Registered in `Database\Seeders\DatabaseSeeder::run()`.

---

## Data source

Reads from `bjcp_styleguide-2021.json` in the project root (`base_path('bjcp_styleguide-2021.json')`). The file is committed to the repository and must be present at that path before the seeder runs.

**Expected JSON structure:**

```json
{
  "beerjson": {
    "styles": [ { ... }, { ... } ]
  }
}
```

Each style object maps to one row in `styles`. If the file is missing or malformed, the seeder exits silently without error.

---

## Fields populated

| `styles` column | Source JSON field | Notes |
|-----------------|-------------------|-------|
| `code` | `style_id` | e.g. `"1A"`, `"23B"` |
| `source` | hardcoded | Always `"bjcp_beer"` |
| `category_name` | `category` | |
| `category_description` | `category_description` | Nullable |
| `name` | `name` | |
| `version` | hardcoded | Always `"2021"` |
| `og_min` / `og_max` | `original_gravity.minimum/maximum.value` | Normalised from gravity points if value > 2 (divides by 1000) |
| `fg_min` / `fg_max` | `final_gravity.minimum/maximum.value` | Same normalisation |
| `abv_min` / `abv_max` | `alcohol_by_volume.minimum/maximum.value` | |
| `ibu_min` / `ibu_max` | `international_bitterness_units.minimum/maximum.value` | |
| `srm_min` / `srm_max` | `color.minimum/maximum.value` | |
| `overall_impression` | `overall_impression` | Nullable |
| `aroma` | `aroma` | Nullable |
| `appearance` | `appearance` | Nullable |
| `flavor` | `flavor` | Nullable |
| `mouthfeel` | `mouthfeel` | Nullable |
| `comments` | `comments` | Nullable |
| `history` | `history` | Nullable |
| `style_comparison` | `style_comparison` | Nullable |
| `ingredients` | `ingredients` | Nullable |
| `examples` | `examples` | Nullable |
| `tags` | `tags` | Nullable |

---

## Upsert behaviour

The seeder calls `Style::upsert()` with the unique key `['code', 'source']`. On conflict, it updates every column except `code` and `source`. Running the seeder again will overwrite style data but will not create duplicate rows, and it will not change the `source` column on existing rows.

---

## Source keys and labels

The `source` column identifies where a style came from. `Style::SOURCE_LABELS` maps source keys to display labels:

| Key | Label |
|-----|-------|
| `bjcp_beer` | BJCP Beer |
| `bjcp_mead` | BJCP Mead |
| `bjcp_cider` | BJCP Cider |
| `ba_beer` | Brewers Association |
| `custom` | Custom |

`bjcp_mead` and `bjcp_cider` are reserved; no seeders exist for them yet. `ba_beer` is seeded by `BaStyleSeeder`.

---

## Custom styles

Admins create custom styles through the Filament UI. These receive `source = "custom"` and `version = null`. The Filament style form sets `source` to `->disabled()` so admins cannot change it through the UI. Custom styles can be deleted through the UI; seeded styles cannot.

---

## Adding a new official style guide

To add a second official guide (e.g. BJCP Mead):

1. Create a JSON data file in the project root following the same `beerjson.styles` structure.
2. Write a new seeder class (e.g. `BjcpMeadStyleSeeder`) that reads the file and calls `Style::upsert()` with the appropriate `source` key (`bjcp_mead`).
3. Add an entry to `Style::SOURCE_LABELS` for the new key.
4. Register the seeder in `DatabaseSeeder::run()`.

Do not include `source` in the upsert update columns list. It is part of the unique identity key, not a value to overwrite.

---

## Related

- [BA seeder reference](ba-seeder-reference.md)
- [How to seed or refresh BJCP styles](bjcp-seeder-howto.md)
- [Filament admin panel overview](filament-overview-explanation.md)
