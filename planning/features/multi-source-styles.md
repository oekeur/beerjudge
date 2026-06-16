# Plan: Extend Styles to Support Multiple Sources + Custom Styles

## Context

The `styles` table currently holds BJCP 2021 beer styles only. The `code` column is unique across the whole table, and `canCreate()`/`canDelete()` are both disabled in the Filament resource — styles are treated as seeded read-only data.

The club wants:
- **Additional official guides** — e.g. BJCP Mead, BJCP Cider — imported via seeders
- **Custom styles** — club-invented categories admins create directly in the UI

Key decision: add a `source` varchar(50) column and move the unique constraint from `code` alone to `(code, source)`.

---

## Files to Modify

| File | Change |
|------|--------|
| `database/migrations/` | New migration for `source` + nullable `version` |
| `app/Models/Style.php` | Add `source`, `SOURCE_LABELS` constant, update `$fillable` |
| `database/seeders/BjcpStyleSeeder.php` | Set `source`, update upsert key |
| `app/Filament/Resources/Styles/StyleResource.php` | Enable create; conditional delete with instanceof guard |
| `app/Filament/Resources/Styles/Schemas/StyleForm.php` | Add locked `source` field; make `version` nullable |
| `app/Filament/Resources/Styles/Tables/StylesTable.php` | Add source badge column + filter |
| `app/Filament/Forms/Components/StyleMultiSelect.php` | 3-level `getCategorizedOptions()` |
| `resources/views/filament/forms/components/style-multi-select.blade.php` | New Alpine state model + 3-level left panel |
| `CLAUDE.md` | Document source pattern and seeder convention |

---

## 1. Migration

Create `database/migrations/YYYY_MM_DD_add_source_to_styles_table.php`.

```php
public function up(): void
{
    Schema::table('styles', function (Blueprint $table) {
        $table->string('source', 50)->default('bjcp_beer')->after('id');
        $table->string('version', 10)->nullable()->default(null)->change();
        $table->dropUnique('styles_code_unique');
        $table->unique(['code', 'source']);
    });
}

public function down(): void
{
    Schema::table('styles', function (Blueprint $table) {
        $table->dropUnique(['code', 'source']);
        $table->unique('code');
        $table->string('version', 10)->nullable(false)->default('2021')->change();
        $table->dropColumn('source');
    });
}
```

- Existing rows automatically get `source = 'bjcp_beer'` from the default.
- `version` becomes nullable; existing rows retain `'2021'`.

---

## 2. Model (`app/Models/Style.php`)

Add `source` to `$fillable`, update PHPDoc, and add a `SOURCE_LABELS` constant that is the single source of truth for all label maps across the app.

```php
/**
 * @property int         $id
 * @property string      $source
 * @property string      $code
 * @property string      $category_name
 * @property string      $name
 * @property string|null $version
 */
class Style extends Model
{
    /** @var array<string, string> */
    public const SOURCE_LABELS = [
        'bjcp_beer'  => 'BJCP Beer',
        'bjcp_mead'  => 'BJCP Mead',
        'bjcp_cider' => 'BJCP Cider',
        'custom'     => 'Custom',
    ];

    protected $fillable = [
        'source', 'code', 'category_name', 'category_description', 'name', 'version',
        // ... rest unchanged
    ];
```

---

## 3. Seeder (`database/seeders/BjcpStyleSeeder.php`)

1. Add `'source' => 'bjcp_beer'` to each record in `array_map`.
2. Change the upsert unique key from `['code']` to `['code', 'source']`.

`source` must not appear in the update columns list — it is part of the match key.

---

## 4. StyleResource (`app/Filament/Resources/Styles/StyleResource.php`)

```php
public static function canCreate(): bool
{
    return true;
}

public static function canDelete(Model $record): bool
{
    if (! $record instanceof Style) {
        return false;
    }
    return $record->source === 'custom';
}
```

Register the `CreateStyle` page (it already exists as a class):

```php
public static function getPages(): array
{
    return [
        'index'  => ListStyles::route('/'),
        'create' => CreateStyle::route('/create'),
        'edit'   => EditStyle::route('/{record}/edit'),
    ];
}
```

---

## 5. StyleForm (`app/Filament/Resources/Styles/Schemas/StyleForm.php`)

Add `source` to the Identity section **before** `code`. It is always `->disabled()` — locked to `'custom'` on create, locked to its stored value on edit. `->dehydrated()` ensures the value is submitted even when disabled.

```php
Select::make('source')
    ->options(\App\Models\Style::SOURCE_LABELS)
    ->default('custom')
    ->required()
    ->disabled()
    ->dehydrated()
    ->columnSpan(1),
```

Make `version` nullable (remove `->required()`):

```php
TextInput::make('version')
    ->nullable()
    ->default(null)
    ->columnSpan(1),
```

**Security note (accepted tradeoff):** `->disabled()` is UI-only. No server-side guard. Acceptable for a 10-person internal tool.

---

## 6. StylesTable (`app/Filament/Resources/Styles/Tables/StylesTable.php`)

Add after the `code` column:

```php
TextColumn::make('source')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'custom'    => 'warning',
        'bjcp_mead' => 'info',
        'bjcp_cider'=> 'success',
        default     => 'gray',
    })
    ->formatStateUsing(fn (string $state): string => \App\Models\Style::SOURCE_LABELS[$state] ?? $state),
```

Add to filters:

```php
SelectFilter::make('source')
    ->options(\App\Models\Style::SOURCE_LABELS),
```

---

## 7. StyleMultiSelect

### 7a. `getCategorizedOptions()` in `StyleMultiSelect.php`

Replace the current flat 2-level method with a 3-level version:

```php
/**
 * @return array<string, array{label: string, categories: array<string, array<int, string>>}>
 */
public function getCategorizedOptions(): array
{
    $sources = [];

    foreach (Style::orderBy('source')->orderBy('code')->get() as $style) {
        $sourceKey   = $style->source;
        $sourceLabel = Style::SOURCE_LABELS[$sourceKey] ?? $sourceKey;
        $fullLabel   = $style->version ? $sourceLabel . ' ' . $style->version : $sourceLabel;

        if (! isset($sources[$sourceKey])) {
            $sources[$sourceKey] = ['label' => $fullLabel, 'categories' => []];
        }

        $sources[$sourceKey]['categories'][$style->category_name][$style->id]
            = "{$style->code} {$style->name}";
    }

    return $sources;
}
```

Note: `$fullLabel` is derived from the first style encountered per source. If styles in a source have mixed versions (unusual), only the first is used as the group label.

### 7b. Blade template `@php` block

```php
@php
    $fieldWrapperView = $getFieldWrapperView();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();

    $sourcedData = $field->getCategorizedOptions();

    $sources = collect($sourcedData)
        ->map(fn ($src, $key) => [
            'key'        => $key,
            'label'      => $src['label'],
            'categories' => collect($src['categories'])
                ->map(fn ($opts, $name) => [
                    'name'   => $name,
                    'values' => array_keys($opts),
                    'labels' => $opts,
                ])
                ->values()
                ->all(),
        ])
        ->values()
        ->all();

    $allValues = collect($sourcedData)
        ->flatMap(fn ($src) => collect($src['categories'])->flatMap(fn ($opts) => array_keys($opts)))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();

    $totalCount = collect($sourcedData)
        ->sum(fn ($src) => collect($src['categories'])->sum(fn ($opts) => count($opts)));
@endphp
```

### 7c. Alpine state model

Replace `activeIdx` integer with `{ activeSource, activeCategory }` pair. Key changes to `x-data`:

```js
search: '',
activeSource: null,
activeCategory: null,
state: ...,
sources: @js($sources),
allValues: @js($allValues),

init() {
    if (this.sources.length > 0) {
        this.activeSource = this.sources[0].key;
        if (this.sources[0].categories.length > 0) {
            this.activeCategory = this.sources[0].categories[0].name;
        }
    }
},

getActiveSourceObj() {
    return this.sources.find(s => s.key === this.activeSource) ?? null;
},

getActiveCategoryObj() {
    const src = this.getActiveSourceObj();
    if (!src) return null;
    return src.categories.find(c => c.name === this.activeCategory) ?? null;
},

visibleSources() {
    const q = this.search.trim().toLowerCase();
    if (!q) return this.sources;
    return this.sources
        .map(src => ({
            ...src,
            categories: src.categories.filter(cat =>
                cat.name.toLowerCase().includes(q) ||
                Object.values(cat.labels).some(l => l.toLowerCase().includes(q))
            )
        }))
        .filter(src => src.categories.length > 0);
},

searchResults() {
    const q = this.search.trim().toLowerCase();
    if (!q) return [];
    return this.sources.flatMap(src =>
        src.categories.flatMap(cat =>
            Object.entries(cat.labels)
                .filter(([, l]) => l.toLowerCase().includes(q))
                .map(([value, label]) => ({
                    value, label, catName: cat.name, sourceName: src.label,
                }))
        )
    );
},

onSearchChange() {
    const vis = this.visibleSources();
    if (vis.length > 0 && vis[0].categories.length > 0) {
        this.activeSource = vis[0].key;
        this.activeCategory = vis[0].categories[0].name;
    } else {
        this.activeSource = null;
        this.activeCategory = null;
    }
},

setActive(sourceKey, catName) {
    this.activeSource = sourceKey;
    this.activeCategory = catName;
},

// Count helpers (cat = { name, values, labels })
countCat(cat) { ... },          // unchanged logic
isFull(cat)    { ... },
isEmpty(cat)   { ... },
isPartial(cat) { ... },

// Source-level helpers
countSource(src) {
    return src.categories.reduce((n, cat) => n + this.countCat(cat), 0);
},
sourceTotalValues(src) {
    return src.categories.reduce((n, cat) => n + cat.values.length, 0);
},
isSourceFull(src)    { return this.countSource(src) === this.sourceTotalValues(src); },
isSourceEmpty(src)   { return this.countSource(src) === 0; },
isSourcePartial(src) { return !this.isSourceEmpty(src) && !this.isSourceFull(src); },

selectSource(src) {
    const vals = src.categories.flatMap(cat => cat.values.map(String));
    this.state = [...new Set([...(this.state ?? []).map(String), ...vals])];
},
deselectSource(src) {
    const s = new Set(src.categories.flatMap(cat => cat.values.map(String)));
    this.state = (this.state ?? []).map(String).filter(v => !s.has(v));
},
toggleSource(src) {
    this.isSourceFull(src) ? this.deselectSource(src) : this.selectSource(src);
},

// selectAll/deselectAll/selectCat/deselectCat/toggleCat/toggle/isChecked/totalSelected/selectedItems
// — logic unchanged; selectedItems must flatten across sources
```

### 7d. Left panel HTML structure

Replace the single `<template x-for="cat in visibleCategories()">` loop with two nested loops:

```html
<template x-for="src in visibleSources()" :key="src.key">
    <div>
        {{-- Source header --}}
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-3 py-1.5 ...">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="src.label"></span>
            <button type="button" x-on:click.stop="toggleSource(src)" ...
                x-text="countSource(src) + '/' + sourceTotalValues(src)"></button>
        </div>

        {{-- Category rows — key must be composite to avoid cross-source collisions --}}
        <template x-for="cat in src.categories" :key="src.key + '::' + cat.name">
            <div x-on:click="setActive(src.key, cat.name)" ...
                 x-bind:class="{ 'active': activeSource === src.key && activeCategory === cat.name, ... }">
                <span x-text="cat.name"></span>
                <button x-on:click.stop="toggleCat(cat)" x-text="countCat(cat) + '/' + cat.values.length"></button>
            </div>
        </template>
    </div>
</template>
```

### 7e. Right panel changes

- Replace all `activeCategory()` references with `getActiveCategoryObj()`.
- In search results, show `item.catName + ' · ' + item.sourceName` as the subtitle.

---

## 8. CLAUDE.md

Add a **Styles and style sources** section:

- List known source keys and which seeder provides each.
- Document the seeder pattern for new official guides (new class + JSON + `SOURCE_LABELS` entry + `DatabaseSeeder` registration).
- Note the `->disabled()` UI-only tradeoff.
- Note `version` nullability and how the Blade label is derived.

---

## Verification Checklist

**Migration:**
- [ ] `php artisan migrate` succeeds on fresh and existing databases
- [ ] Existing styles have `source = 'bjcp_beer'`, `version = '2021'`
- [ ] `(code, source)` unique constraint rejects duplicate pairs

**Seeder:**
- [ ] `php artisan db:seed --class=BjcpStyleSeeder` completes without error
- [ ] Re-running the seeder is idempotent

**StyleResource:**
- [ ] Admin can access the Create Style page
- [ ] Delete action is absent for seeded styles, present for custom styles
- [ ] PHPStan level 10 passes on `StyleResource.php`

**StyleForm:**
- [ ] On create: source shows "Custom", is non-editable, saves `source = 'custom'`
- [ ] On edit of seeded style: source field shows correct label, is non-editable
- [ ] Saving a custom style with blank `version` succeeds

**StylesTable:**
- [ ] Source badge renders with correct label and color per source
- [ ] Source filter narrows the table correctly

**StyleMultiSelect:**
- [ ] Left panel shows source header rows (e.g. "BJCP Beer 2021") with category rows beneath
- [ ] Clicking a category row activates the correct source + category in the right panel
- [ ] Source-level toggle selects/deselects all styles in that source
- [ ] Category-level select/deselect works as before
- [ ] Global select all / deselect all works across all sources
- [ ] `totalSelected() / totalCount` counter is correct
- [ ] Search finds styles across all sources; results show `catName · sourceName`
- [ ] With only `bjcp_beer` data, the component renders as a single source group
- [ ] PHPStan level 10 passes on `StyleMultiSelect.php`

**General:**
- [ ] `./vendor/bin/phpstan analyse` passes
- [ ] `php artisan test --compact` passes
