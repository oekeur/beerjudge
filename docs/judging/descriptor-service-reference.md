# DescriptorService

`DescriptorService` is the canonical source of off-flavour descriptors used in scoresheets. It provides the slug list for server-side validation and the full descriptor array (slug + name + description) for the judging screen UI. All methods are static.

## API

### `DescriptorService::descriptors(): array`

Returns the full descriptor list as an array of associative arrays, one entry per descriptor.

```php
/** @return array<int, array{slug: string, name: string, description: string}> */
public static function descriptors(): array
```

Each element has three keys:

| Key | Type | Source |
|-----|------|--------|
| `slug` | `string` | Hard-coded in `SLUGS` constant |
| `name` | `string` | `trans("descriptor.{$slug}.name")` |
| `description` | `string` | `trans("descriptor.{$slug}.description")` |

Names and descriptions come from the `lang/*/descriptor.php` translation files. If a translation key is missing, `trans()` returns the key string.

### `DescriptorService::slugs(): array`

Returns only the slug list. Used by `StoreScoresheetRequest` to validate incoming descriptor values.

```php
/** @return array<int, string> */
public static function slugs(): array
```

## Descriptors

17 descriptors are defined:

| Slug | Common name |
|------|-------------|
| `acetaldehyde` | Acetaldehyde |
| `alcoholic` | Alcoholic |
| `astringent` | Astringent |
| `diacetyl` | Diacetyl |
| `dms` | DMS |
| `estery` | Estery |
| `grassy` | Grassy |
| `light_struck` | Light-struck |
| `metallic` | Metallic |
| `musty` | Musty |
| `oxidized` | Oxidized |
| `phenolic` | Phenolic |
| `solvent` | Solvent |
| `sour_acidic` | Sour / Acidic |
| `sulfur` | Sulfur |
| `vegetal` | Vegetal |
| `yeasty` | Yeasty |

## Storage

Descriptors are stored as a JSON column (`descriptors`) on the `scoresheets` table. The value is an array of slug strings. The column is nullable; an absent value is treated as an empty array by both the server and the browser.

```json
["diacetyl", "sulfur"]
```

## Usage in the codebase

**Validation** (`app/Http/Requests/StoreScoresheetRequest.php`):

```php
'descriptors'   => ['nullable', 'array'],
'descriptors.*' => ['string', 'in:' . implode(',', DescriptorService::slugs())],
```

**Judging screen** (`app/Http/Controllers/JudgingController.php`):

```php
$descriptors = DescriptorService::descriptors();
// passed to view as $descriptors; rendered as a checkbox list per entry
```

## Related

- [Judging screen reference](reference.md) — scoresheet payload, validation rules
- [Why descriptors are stored as JSON](descriptor-service-explanation.md)
- `app/Services/DescriptorService.php`
- `lang/en/descriptor.php`
