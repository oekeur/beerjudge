# Why descriptors are stored as JSON

## The problem

Scoresheets need to record which off-flavours a judge detected. There are 17 possible faults. A normalised approach would put each descriptor in its own table row via a `scoresheet_descriptor` pivot. That adds a table, a model, a relationship, eager-loading concerns, and more complex upsert logic — all for a fixed set of 17 values that never changes between requests.

## The approach

Descriptors are stored as a JSON array of slugs on the `scoresheets` row:

```
scoresheets.descriptors  →  ["diacetyl", "sulfur"]
```

`DescriptorService` owns the canonical slug list. Validation in `StoreScoresheetRequest` checks incoming slugs against `DescriptorService::slugs()`, so only known values reach the database. The judging screen POSTs the full current array on every change; the server overwrites the column on upsert. No diff, no merge, no partial update.

This means the descriptor list rides along in the same `POST /scoresheets` payload that already handles scores and notes. No second endpoint, no additional round trip.

## Trade-offs

**Fixed vocabulary.** Adding a descriptor requires a code change (new slug in `SLUGS`, new translation keys). There is no admin UI to manage descriptors. For a 10-person club with a stable BJCP fault taxonomy this is fine; for a multi-club deployment with custom fault lists it would not be.

**No per-descriptor analytics.** Because descriptors are a JSON blob, querying "how many scoresheets flagged diacetyl this year" requires a JSON extraction query rather than a simple join. Given that no analytics feature is planned, this is an acceptable tradeoff.

**Last-write-wins on sync.** If a judge toggles a descriptor while a previous sync is still in flight, the later POST wins. Alpine serialises toggle events through `saveSheet()`, which awaits the IndexedDB write before syncing, so races within a single browser tab are prevented. Cross-tab or cross-device races remain possible but are not a realistic concern.

## Alternatives considered

**`scoresheet_descriptor` pivot table** — rejected. A pivot adds a model, a relationship, and complicates the upsert that already handles the entire scoresheet atomically. The fixed-vocabulary constraint makes the overhead unjustified.

**Separate `POST /scoresheets/{id}/descriptors` endpoint** — rejected. Splitting descriptors into their own endpoint breaks the offline-first contract: the judging screen syncs one complete snapshot per save. A second endpoint would require coordination between two in-flight requests and two retry queues.

## Related

- [DescriptorService reference](descriptor-service-reference.md)
- [Judging screen reference](reference.md) — full scoresheet payload shape
- [Why the judging screen is offline-first](explanation-offline-sync.md)
