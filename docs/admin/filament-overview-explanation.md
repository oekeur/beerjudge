# Filament Admin Panel Overview

The admin panel at `/admin` is the management interface for competitions, members, and styles. It is built with Filament 4. All authenticated admin users can access it; non-admin members cannot.

---

## Why Filament

The alternative was writing Blade controllers and views by hand. For a CRUD-heavy management panel with tables, filters, forms, and relation managers, that would produce a lot of repetitive code with no obvious benefit for a 10-person club tool. Filament generates those surfaces from PHP class definitions, which means the admin panel stays maintainable without a dedicated frontend build.

The judging screen is intentionally not Filament. Livewire's server round-trips on every interaction would drop in-progress scoresheet input on a network blip. That screen uses Blade, Alpine.js, and IndexedDB instead. The admin panel does not have that constraint.

---

## Panel configuration

`AdminPanelProvider` registers the panel at the `admin` path with:

- Amber as the primary colour.
- The `web` auth guard (session-based; no API tokens).
- A custom Vite theme at `resources/css/filament/admin/theme.css`.
- The brand name set from `config('app.club_name')` with a ` BeerJudge` suffix, falling back to `BeerJudge` if unset.
- The brand logo linked to `/` (the judging screen), not `/admin`.
- Sentry initialised via a `panels::head.end` render hook, injecting the DSN and current user from meta tags.

Resources, pages, and widgets are discovered automatically from their respective directories under `app/Filament/`.

---

## Resources

### CompetitionResource

Manages the full competition lifecycle. The edit form is tabbed across five areas:

- **Overview** — name, year, judging date, status (`draft`, `open`, `judging`, `closed`).
- **Styles** — multi-select of styles to include in this competition. Styles must be attached before entries are created.
- **Entries** — a Repeater that manages entries inline: member, style, ABV, brewer notes, and drag-to-reorder. Includes a "Sort by ABV" action and a copyable registration link.
- **Dashboard** — judge assignment, a live-polling results summary widget showing scores per entry as they come in.
- **Results** — a judge variance widget flagging entries where the score spread across judges is high.

### MemberResource

Manages the member list. The table shows name, email, admin status, and active status. A filter narrows to inactive members. Two actions on each row: `Approve` (sets `is_active = true`) and `Edit`. A bulk `Approve` action handles multiple rows at once.

The navigation badge shows a count of inactive members so that pending approvals are visible at a glance without opening the member list.

The edit form does not expose the password field. Password resets go through `member:password` on the command line.

### StyleResource

Lists all styles across all sources. Admins can create and edit any style. Deletion is only permitted for `source = "custom"` styles; seeded BJCP styles cannot be deleted through the UI. This guard is in `StyleResource::canDelete()`.

---

## File organisation

Each resource lives in its own subdirectory under `app/Filament/Resources/`. Form schema, table definition, and any widgets are split into separate classes (e.g. `CompetitionResource`, `CompetitionForm`, `CompetitionsTable`, `ResultsSummaryWidget`). This keeps individual files short and makes it easier to find the definition for a specific column or field.

---

## Authentication

The panel uses the same session cookie as the rest of the app. There is no separate admin login. Members with `is_admin = true` can access `/admin`; the `Authenticate` middleware enforces this. Inactive members (`is_active = false`) are blocked from logging in by `RequireActiveMember` middleware on the main app and cannot reach the admin panel.

---

## Styling

A custom Tailwind theme compiled by Vite applies to the admin panel. Custom Blade views must live under `app/Filament/**/*` or `resources/views/filament/**/*` for their classes to be picked up by the Tailwind build. Adding a new source directory requires an `@source` entry in `resources/css/filament/admin/theme.css` followed by `npm run build`.

---

## Related

- [Artisan commands reference](artisan-commands-reference.md)
- [BJCP seeder reference](bjcp-seeder-reference.md)
