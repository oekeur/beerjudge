# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Laravel app for a ~10-person homebrew club to run four competitions per year. Members register beers, judges score them on the BJCP 50-point scale, and each competition produces a ranked result list. Deliberately narrow in scope: no payments, no shipping, no BJCP report generation, no multi-club support.

## Claude Code setup

`.claude/settings.json` grants shared project permissions (pint, artisan test/route:list). Personal permissions, MCP servers, and machine-specific config go in `.claude/settings.local.json` — that file is gitignored.

`.claude/agents/` and `.claude/skills/` are committed and shared. Add project-level agents or skills there; personal ones go in `~/.claude/`.

## Debugging errors

When any error or unexpected behaviour is reported, the first two tool calls must be:
1. `mcp__laravel-boost__last-error` — gets the most recent exception with full stack trace
2. `mcp__laravel-boost__read-log-entries` (5–10 entries) — gets the Laravel log tail

Do this before reading code, running artisan commands, or forming any hypothesis. The displayed error (e.g. in the browser) is often a symptom; the log reveals the root cause.

## Skills

The `laravel-best-practices` skill is authoritative for this project. Invoke it before making any non-trivial Laravel change.

**PHPStan errors:** whenever `./vendor/bin/phpstan analyse` reports errors — whether you ran it yourself, a hook triggered it, or a CI step failed — invoke the `phpstan-fix` skill immediately. Do not attempt manual fixes inline; the skill handles it with a Haiku subagent.

Use lean sub-agents where they add value:
- `Explore` for codebase searches and multi-file reads — keep the main context clean.
- Parallel worktree agents for independent edits across unrelated files — spawn them together, collect results.

## Worktrees

Every session works in its own git worktree by default unless the user explicitly says not to. This enables parallel agents without conflicts.

**On session start (unless told otherwise):**
1. Create a worktree: `git worktree add ../beerjudge-<branch-slug> -b <branch-name>`
2. Copy the env file: `cp /home/oscar/projects/beerjudge/.env ../beerjudge-<branch-slug>/.env` — worktrees don't inherit `.env` and tests will fail with "No application encryption key" without it.
3. Copy vendor with hard links: `cp -rl /home/oscar/projects/beerjudge/vendor ../beerjudge-<branch-slug>/vendor && composer -d ../beerjudge-<branch-slug> dump-autoload --quiet` — **do not use a symlink**. PHP resolves symlinks when computing `__FILE__`, so a symlinked vendor always points `$baseDir` at the main repo's `app/` directory; any new class added in the worktree is invisible to the autoloader. Hard links (`cp -rl`) are instant and share disk blocks; `dump-autoload` rebuilds the classmap against the worktree's own `app/`.
4. Do all work there.

**On session end:**
1. Commit all changes in the worktree.
2. Switch back to the main worktree: `cd /home/oscar/projects/beerjudge`
3. Merge with `git merge --no-ff <branch-name>` — resolve conflicts carefully; never discard the other branch's changes.
4. Remove the worktree: `git worktree remove ../beerjudge-<branch-slug>` and delete the branch: `git branch -d <branch-name>`

## Tech stack

- **Laravel** with **Filament** for the admin/management side
- **Blade + Alpine.js** for the judging screen — no Livewire (Livewire re-renders from the server on every interaction and would drop in-progress input on a network blip)
- **IndexedDB** (Dexie optional) for offline-first scoresheet backup on the judging screen
- **Postgres or MySQL**
- Session-based auth; sessions last ~1 week
- **PWA** — installable on Android/iOS via `vite-plugin-pwa`; see PWA section below

## Styling in Filament

A custom Filament theme is configured at `resources/css/filament/admin/theme.css` and registered via `->viteTheme()` in `AdminPanelProvider`. It is compiled by the same Vite/Tailwind build as `app.css`, so all Tailwind utilities are available on every admin panel page.

**Rules:**
- Use Tailwind utility classes directly in Filament Blade views — no scoped `<style>` blocks, no inline styles.
- The theme's `@source` directives scan `app/Filament/**/*` and `resources/views/filament/**/*`. Any new custom Blade view must live under one of those paths, or add an `@source` entry to `theme.css`.
- After editing `theme.css` (e.g. adding a new `@source` or a custom CSS rule), run `npm run build`.

## Playwright browser tests

- Target interactive elements with `page.getByRole('button', { name: /label/ })`, not `page.locator('button').filter({ hasText: /label/ })`. `filter({ hasText })` matches `textContent()`, which includes text from `display:none` elements (Alpine `x-show` keeps hidden spans in the DOM). `getByRole` uses the ARIA accessible-name computation, which skips hidden elements. The judging screen has `x-show` sync-status badges inside the entry header button — any text-based locator will match those hidden spans and click the wrong element.

- Never select by Tailwind utility classes (`span.text-emerald-600`, `div.border-t`, `button.w-full`). Class names are renamed as colours or spacing change. For non-interactive elements, add `data-testid="..."` to the Blade and use `page.getByTestId(...)`.

- `<x-app-nav />` renders a logout `<form method="POST">` with `<button type="submit">` **before** any page-level form. Two consequences:
  1. `page.click('button[type="submit"]')` hits the logout button, not the page form. Fix: `page.getByRole('button', { name: /specific label/ })`.
  2. `document.querySelector('form')` selects the logout form. Fix: `document.querySelectorAll('form').forEach(f => f.noValidate = true)`.
  Pages using `<x-auth-card>` (login, register) have no nav and are safe to use generic selectors.

## Git

- Commit automatically after completing each task — do not wait to be asked.
- Use the format: `[type](scope): description` (e.g. `feat(judging): add offline sync retry`)
- Stage only files relevant to the task; do not use `git add -A` blindly.

## Commands

Commands will be documented here once the project is scaffolded. Expected:

```bash
composer install
php artisan migrate --seed     # includes full BJCP 2021 style set
php artisan test
php artisan test --filter=...  # run a single test
./vendor/bin/phpstan analyse   # static analysis
```

## Architecture

Two frontends, one app:

**Filament (admin)** — members, competitions, styles, entries, bottle-number assignment, results view, judge variance flags. All management flows go here.

**Judging screen (Blade + Alpine)** — a single Blade route. Entry list is server-rendered into the page on load. Alpine writes each scoresheet to IndexedDB as the judge fills it, then POSTs to the server. On failure it queues and retries. The server upserts by `client_uuid`, so retries are idempotent. No entries API needed; the list comes from the initial page render.

## Data model

```
members             id, name, email, is_admin, is_active
competitions        id, name, year, judging_date, status (draft/open/judging/closed), published_at
styles              id, code (e.g. 23A), category_name, name, version (2021), OG/FG/ABV/IBU/SRM ranges
                    + description, commercial_examples (Enhancement 1)
entries             id, competition_id, member_id, style_id, bottle_number, brewer_notes
scoresheets         id, entry_id, judge_id, aroma (0-12), appearance (0-3), flavour (0-20),
                    mouthfeel (0-5), overall (0-10), total (computed), per-section notes,
                    client_uuid, status (draft/submitted), timestamps
competition_style   pivot: competition_id, style_id
descriptors         id, name, slug, kind (fault/style-trait)  [Enhancement 2]
scoresheet_descriptor  scoresheet_id, descriptor_id, intensity (low/med/high, optional)  [Enhancement 2]
```

Unique constraint on `(entry_id, judge_id)` — one scoresheet per judge per entry.

## Styles and style sources

Styles have a `source` column that identifies where the style came from. Known source keys:

- `bjcp_beer` — seeded by `BjcpStyleSeeder` from a JSON file of BJCP 2021 beer styles
- `bjcp_mead`, `bjcp_cider` — reserved; no seeders yet
- `custom` — created by admins through the Filament UI

**Adding a new official guide:** create a seeder class + JSON data file, add a `SOURCE_LABELS` entry to `Style::SOURCE_LABELS`, and register the seeder in `DatabaseSeeder`. The upsert key is always `['code', 'source']`. Never include `source` in the update columns list — it is part of the identity key, not a value to overwrite.

**`source` field in Filament:** the field uses `->disabled()` in the style form so admins cannot change it through the UI. There is no server-side guard; a crafted POST could override it. Acceptable for a 10-person internal tool.

**`version` nullability:** `version` is nullable. Custom styles have `null`. In Blade, the label for a source group renders as `"{SOURCE_LABEL} {version}"` when version is set, or just `"{SOURCE_LABEL}"` when null.

## Competition lifecycle

`draft` → `open` (members register entries) → `judging` (registration locks, bottle numbers assigned, judging begins) → `closed` (everything locks, results final).

Judges score any entry except their own (`entry.member_id != judge_id`). Final score = average of submitted totals. Tiebreak = averaged overall-impression score (BJCP convention). Rankings are competition-wide, not per style.

## Results sheet

- All members see the full standings once a competition closes; admins see them at any status
- One URL per participant: their own scores + judge notes, then the full standings with brewer names revealed
- Admins can render all participants at once for batch printing
- Print-styled Blade view; dompdf is optional

## Scoresheet sync (judging screen)

`POST /scoresheets` — accepts a sheet or queued batch; idempotent on `client_uuid`. No separate API token; the session cookie covers this route. Sheet status: `draft` locally, `submitted` once server confirms. A submitted sheet can be reopened and resaved (same `client_uuid` overwrites the row) until the competition closes.

## PWA

The app is installable as a Progressive Web App, targeting the judging screen as the primary use case.

**Files:**
- `public/manifest.json` — web app manifest (name: "Foebar BeerJudge", theme: amber `#b45309`, start_url: `/`)
- `public/icons/icon-192.png` and `public/icons/icon-512.png` — rasterised app icons
- `public/favicon.svg` — SVG favicon
- `resources/images/icon.svg` — editable source SVG for the icon (amber pint glass + Amsterdam coat of arms XXX)

**Service worker:** generated by `vite-plugin-pwa` into `public/build/sw.js`. Served at root scope via a `GET /sw.js` Laravel route in `routes/web.php` that adds `Service-Worker-Allowed: /`. Registered manually in `resources/js/judging.js` with `scope: '/'`.

**Why the route is needed:** `vite-plugin-pwa` places the SW in `public/build/`, which limits its default scope to `/build/`. The Laravel route re-serves it from `/sw.js` with a header that grants root scope.

**Regenerating icons after editing the source SVG:**
```bash
convert -background '#1c1917' resources/images/icon.svg public/icons/icon-512.png
convert public/icons/icon-512.png -resize 192x192 public/icons/icon-192.png
cp resources/images/icon.svg public/favicon.svg
```

**PWA meta tags** are present in all Blade views (after `<meta name="viewport">`). Any new view that renders a full HTML document must include them.

## Enhancements (planned, in priority order)

1. **Style guidelines** — collapsible BJCP description + vital stats panel per entry on the judging screen. Seed from BJCP 2021 data. Check redistribution terms first.
2. **Off-flavor descriptors** — structured tick boxes on the scoresheet (acetaldehyde, diacetyl, DMS, etc.). Ride along in the same POST payload, covered by existing idempotency.
3. **Judge variance flagging** — computed over `scoresheets`, no new tables. Flag entries whose score spread exceeds ~7 points; surface as a badge in Filament.
4. **Email on publish** — queued job dispatched when `published_at` is set; one email per participant with a link to their results sheet. Requires mail transport and queue worker.
5. **History and stats** — per-member trend, club-wide stats, per-style distributions. All reads from existing tables; surface as Filament pages and chart widgets.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

</laravel-boost-guidelines>
