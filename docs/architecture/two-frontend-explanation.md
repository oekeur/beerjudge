# Two-Frontend Architecture

## The problem

The app has two fundamentally different interaction patterns, and a single UI framework cannot serve both without painful compromises.

**Admin management** (competitions, entries, members, styles) is a classic CRUD workload: a form is submitted, data is saved, a page reloads or redirects. Server-round-trips are expected; state is simple. Filament handles this class of UI well out of the box.

**Judging** is different. A judge fills in five scored sections and five freetext notes fields for each beer, sometimes over 20-30 minutes per sheet. The venue may have unreliable Wi-Fi. If a network request fails mid-fill and the UI re-renders from the server, whatever the judge was typing disappears. That is not recoverable.

Livewire, which Filament uses for its reactive components, re-renders the component from the server on every reactive update. A failed or slow request causes the component to either freeze or snap back to the last server-confirmed state, discarding in-progress input in both cases. For a CRUD admin panel this is invisible; for a scoresheet with several paragraphs of typed notes, it is unacceptable.

## The approach

The app runs two completely separate UIs against one Laravel backend.

```
Browser
  │
  ├─── /admin/*  ──────► Filament (PHP/Livewire)
  │                        Resources: Competitions (Entries,
  │                        Dashboard, Results & Variance tabs),
  │                        Members, Styles
  │
  └─── /judging/*  ───► Blade + Alpine.js
       /scoresheets       Single-page scoresheet screen
                          IndexedDB (Dexie) for local persistence
                          POST /scoresheets for server sync
```

Filament is mounted at `/admin` via `AdminPanelProvider` and discovered automatically from `app/Filament/Resources/`. It uses the standard Filament session-cookie auth guard.

The judging screen is one page route, `GET /judging/{competition}`, plus an admin-only JSON route `GET /judging/{competition}/progress`. Results pages (`/competitions/{id}/results`) are server-rendered Blade without the offline layer. The controller renders a Blade view with all entry data server-rendered into the page as a JSON payload. Alpine.js takes over from there. Every scoresheet change writes to IndexedDB immediately, then queues a `POST /scoresheets` sync in the background. The server upserts by `client_uuid`, so retries are safe.

The judging screen JS entry point is `resources/js/judging.js`. It initialises Alpine, Dexie (IndexedDB wrapper), and registers the service worker for PWA support. The `window.judgingApp()` function carries the entire client-side state machine.

Both UIs share:
- The same session-based auth (cookie, one week TTL)
- The same Eloquent models and validation rules
- The same Postgres database

Neither UI knows about the other's internals. Admin pages never load judging JS. The judging screen never renders a Filament component.

## Trade-offs

**Two tech stacks to maintain.** Filament upgrades do not affect the judging screen, and changes to judging JS do not affect admin pages. That isolation is intentional, but it means two mental models for contributors.

**No shared component library.** Filament components (form fields, tables, actions) are not available on the judging screen. Judging screen styling uses Tailwind from the same `app.css` build, but any custom UI has to be written in Blade and Alpine directly.

**Session coverage, not token auth.** The judging screen POSTs scoresheets using the same session cookie as the page request. No API token is issued. This keeps the auth model simple for an internal tool, but means the judging screen cannot be called from a native mobile app or a third-party client without a separate auth mechanism.

## Alternatives considered

**Livewire everywhere:** Rejected because of the re-render problem described above. Livewire's optimistic UI tools did not exist in stable form when this was designed, and even with them, losing a paragraph of tasting notes to a network blip is a bad failure mode for judges.

**Full SPA (Vue/React):** Would solve the offline problem but would require an API layer for all admin operations too. Filament would be replaced by a custom admin UI, multiplying scope significantly for a 10-person club app.

**Inertia.js:** Would allow sharing Laravel routing and auth while moving to a component-based frontend. Not adopted because Filament already covers the admin surface and adding Inertia for judging only would introduce a third frontend pattern.

## Related

- [`../judging/explanation-offline-sync.md`](../judging/explanation-offline-sync.md) — how the judging screen handles offline and retry
- [`bjcp-scoring-explanation.md`](bjcp-scoring-explanation.md) — the scoring model that the judging screen collects
