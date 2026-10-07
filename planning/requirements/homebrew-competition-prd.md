# Homebrew Competition Tool: PRD

## Goal

A small tool for a ~10-person homebrew club to run four competitions a year. Members register beers, judges score them on the BJCP 50-point scale, and each competition produces a ranked result list. Deliberately narrower than BCOE&M: no club management, payments, shipping, or BJCP report generation.

## Users and roles

Everyone is a `member`. A member both brews (enters beers) and judges (scores other people's beers). One or more members also hold an `admin` flag, which lets them set up competitions, register entries, assign bottle numbers, and close competitions to release results.

No public sign-up. The admin adds members by hand; ten people, added once.

## In scope

Running a competition end to end: registering entries, blind judging on phones, averaging multiple judges' scores, and ranking entries by score. A local backup on each judge's phone so a half-filled scoresheet survives a dropped request, a sleeping phone, or an accidental refresh. A printable results sheet per participant, with their own scores and judge feedback plus the full standings. A seeded set of BJCP 2021 styles.

## Out of scope

Payments and entry fees. Public or anonymous registration. Bottle shipping and logistics. Multi-club support. Official BJCP-format scoresheet forms and statistical reports (the results sheet below is the club's own, not the AHA/BJCP layout). Awards beyond a ranked list (no medals, best-in-show, or category placings yet). Photo uploads.

## Data model

`members`: id, name, email, bjcp_rank (nullable), is_admin, is_active.

`competitions`: id, name, year, judging_date, status (draft / open / judging / closed).

`styles`: id, code (e.g. 23A), category_name, name, version (2021), optional vital-stat ranges (OG, FG, ABV, IBU, SRM). Seeded once with the full BJCP 2021 set; each competition picks the styles it accepts from these.

`entries`: id, competition_id, member_id, style_id, name, bottle_number (assigned at check-in, shown to judges instead of the brewer's name), brewer_notes, created_at.

`scoresheets`: id, entry_id, judge_id (member), aroma (0-12), appearance (0-3), flavour (0-20), mouthfeel (0-5), overall (0-10), total (computed), per-section notes, client_uuid, status (draft / submitted), created_at, submitted_at, updated_at. Unique constraint on (entry_id, judge_id): one sheet per judge per entry. A submitted sheet isn't final; its judge can reopen and resave it (the same client_uuid overwrites in place) until the competition closes.

`competition_style`: pivot listing the BJCP styles a competition accepts. Most competitions list one or more; an empty set means open to any style. At registration, an entry's style must be in the set unless the set is empty.

## Competition lifecycle

A competition moves through four states. In `draft` the admin sets it up and isn't visible to members. In `open` members register their entries, and can edit or withdraw their own entry while registration is open. When the admin moves it to `judging`, registration locks, bottle numbers are assigned, and the judging screen shows the entry list; judges can submit and re-edit their own scoresheets throughout this state. In `closed` everything locks: no new sheets, no edits, results final.

Edits are scoped to the owner. A member edits only their own entry, a judge only their own scoresheet. The lock is the phase boundary, so an accidental submit is recoverable up to that point.

## Judging and scoring

Scoring uses the standard BJCP 50-point breakdown: aroma 12, appearance 3, flavour 20, mouthfeel 5, overall impression 10. The total maps to a band for reference: Outstanding 45-50, Excellent 38-44, Very Good 30-37, Good 21-29, Fair 14-20, Problematic 0-13.

Judging is an open pool. Any judge can score any entry except their own; the queue filters on `entry.member_id != judge_id`. No pre-assignment of judges to entries, and no minimum judge count. An entry's final score is the average of its submitted scoresheet totals, over however many sheets exist.

Entries rank by that average, descending, within the competition, even when the competition spans several styles: placement is overall, not per style. Ties break on the averaged overall-impression score (BJCP convention). Because the average is computed from the current sheet values, editing a sheet updates the ranking automatically.

## Results sheet

Once a competition closes, each participant gets a printable results sheet in two parts.

Their own entries in detail: for each beer, the style, every judge's section scores (aroma, appearance, flavour, mouthfeel, overall) and notes, the averaged total, its band, and where it placed.

Then the full standings: every entry ranked by average, with brewer names revealed, since blind judging is over.

It's a print-styled Blade view, one URL per participant. Admins see sheets at any status; members see their own once the competition is closed. There is no separate publish step. Each sheet is printed from its own page, reached through the results index. Generating PDFs (dompdf) instead of relying on browser print is optional.

## Judging screen (online, with local backup)

The bar's wifi is reliable, so judging runs online. The reason for a custom screen rather than judging straight in Filament is the backup: a dropped request, a phone that sleeps mid-sheet, or an accidental refresh shouldn't cost a half-filled scoresheet.

It's a route in the same Laravel app: a Blade page with Alpine for interactivity, kept clear of Livewire, which would re-render from the server on every keystroke and lose in-progress input on any blip. As the judge fills a sheet, Alpine writes it to IndexedDB with a client-generated UUID and posts it to the server. If a post fails, the sheet stays queued and retries, so nothing is lost. The server upserts by client_uuid, so a retry can't create a duplicate.

The entry list (bottle number, style, beer name, minus their own) is server-rendered into the page on load. Sheets save as `draft` locally and become `submitted` once the server confirms. A judge can tap a submitted sheet to reopen it and resave; the post carries the same client_uuid, so the server overwrites the existing row rather than adding one. This is the fix for an accidental submit, and it works until the competition closes.

Making it installable (manifest plus service worker) is optional polish, not required now that the network is dependable.

## Routes and sync

Session-based auth (the cookie covers the judging screen too, so no separate API token). The judging list is server-rendered into the Blade page rather than fetched, so there's no entries endpoint to build. One JSON route handles writes: `POST /scoresheets` accepts a sheet (or a queued batch on retry) and is idempotent on client_uuid. Results live in Filament; expose a read route only if you also want them on the judging screen.

## Tech

One Laravel app, two frontends. Filament handles the management side: members, competitions, styles, entries, bottle-number assignment, and the results view. The judging screen is a Blade route with Alpine that autosaves each sheet to IndexedDB and posts it to the server, retrying on failure (Dexie optional, to make IndexedDB less tedious). It stays clear of Livewire, since Livewire re-renders from the server on every interaction and would drop in-progress input on a blip. Installable (manifest plus service worker) is optional. Judge sessions last about a week before re-auth, low friction for ten trusted people. Postgres or MySQL. Past competitions and their scores are retained across years.

## Decisions

1. Tiebreak: averaged overall-impression score (BJCP convention).
2. Results: visible to members once the competition is closed, to admins at any status. No separate publish step.
3. Styles: seed the full BJCP 2021 set; pick accepted styles per competition.
4. History: keep all past competitions and scores queryable across years.
5. Sessions: judges stay signed in about a week before re-auth.
