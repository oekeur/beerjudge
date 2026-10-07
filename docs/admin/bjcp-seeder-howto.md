# How to Seed or Refresh BJCP Styles

Run this when setting up a new environment or when the BJCP JSON data file has been updated.

---

## Prerequisites

- The file `bjcp_styleguide-2021.json` is present in the project root.
- Migrations have already run (`php artisan migrate`).

---

## Seed on a fresh install

`BjcpStyleSeeder` runs automatically as part of:

```bash
php artisan migrate --seed
```

No further action needed.

---

## Re-seed an existing database

To refresh styles without wiping other data:

```bash
php artisan db:seed --class=BjcpStyleSeeder
```

This upserts all styles from the JSON file. Existing rows with the same `(code, source)` pair are updated in place. Rows created by previous runs are not duplicated.

---

## Verification

Count the seeded styles:

```bash
php artisan tinker --execute 'echo App\Models\Style::where("source", "bjcp_beer")->count()." styles\n";'
```

A full BJCP 2021 beer dataset typically contains around 100 styles. If the count is 0, check that the JSON file exists and is readable.

---

## Troubleshooting

**Count stays at 0 after seeding**

The seeder exits silently when `bjcp_styleguide-2021.json` is missing or cannot be read. Confirm the file is at the project root:

```bash
ls -lh bjcp_styleguide-2021.json
```

If the file is in a different location, update `BjcpStyleSeeder::run()` to point to the correct path.

**Styles appear without vital stats (OG/FG/ABV/IBU/SRM)**

Some styles in the source JSON store OG/FG as gravity points (e.g. `1055`) rather than specific gravity (`1.055`). The seeder normalises values greater than 2 by dividing by 1000. If a style still shows blank ranges, the source JSON for that style may lack the range fields entirely; that is expected for some specialty styles.

**"SQLSTATE: Duplicate entry" error**

This should not happen because the seeder uses upsert. If it does, check whether a migration added a unique constraint on a column the seeder does not include in its upsert key. The unique key is `['code', 'source']`.

---

## Related

- [BJCP seeder reference](bjcp-seeder-reference.md)
- [Filament admin panel overview](filament-overview-explanation.md)
