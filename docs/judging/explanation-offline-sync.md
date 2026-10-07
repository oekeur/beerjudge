# Why the judging screen is offline-first

## The problem

Judges fill out scoresheets over 30–90 minutes, typing notes across five sections per beer. If the form relied on live server round-trips (as a standard Livewire component would), any network blip during that time would cause the page to re-render from server state — discarding whatever the judge had typed. In a judging session, that means lost work with no way to recover it.

A second problem: homebrew club judging happens in kitchens, garages, and back rooms. Wi-Fi is unreliable. Submitting on a flaky connection to a synchronous server and losing data is a realistic failure mode, not a theoretical one.

## The approach

The judging screen separates writes into two layers:

```
Judge types a score or note
        |
        v
  IndexedDB (instant, local)
        |
        v
  POST /scoresheets (async, best-effort)
        |
   success → mark synced
   failure → mark error, schedule retry in 5s
```

Every scoresheet is written to the browser's IndexedDB immediately on every input event. The network sync happens after. The UI reflects IndexedDB state, not server state — so the judge's input is never blocked on a server response.

If the POST fails, the sync badge on the entry turns red. The app retries automatically after 5 seconds. Tapping the badge forces an immediate retry.

On page reload or browser restart, the app re-reads IndexedDB and picks up any pending or errored sheets before they were confirmed by the server.

## Idempotency via `client_uuid`

Every scoresheet gets a UUID when first created in the browser. The UUID travels with every POST. The server looks up an existing sheet by `client_uuid` for this judge, and if none matches, by `(entry_id, judge_id)`; it updates that sheet or creates one. A judge therefore has at most one sheet per entry, and retrying a failed POST never creates a duplicate.

The same UUID cannot be used by two different judges. If judge B sends a UUID already owned by judge A, the server returns HTTP 422. This prevents a crafted request from overwriting another judge's scores.

## Why not Livewire

Livewire re-renders components from server state on every reactive update. A network interruption during typing causes the server-side re-render to win and overwrites the browser's unsaved text. For a form with five large text areas filled in over an hour, that is unacceptable data loss.

Alpine.js manages all state locally. The server is a sync target, not the source of truth during an active session.

## Trade-offs

**Conflict resolution is last-write-wins.** If a judge opens the same competition on two devices simultaneously, whichever device POSTs last sets the server record. There is no merge. This is acceptable given the use case: a 10-person club where judges do not share devices.

**IndexedDB is browser-scoped.** Pending sheets on one device are invisible on another. A judge who switches from a laptop to a phone mid-session will not see their in-progress notes on the new device — only what was successfully synced to the server.

**Draft scores are nullable.** The server accepts partial sheets (`status = draft`) with no scores set, to allow note-taking before any scores are committed. Score fields only become required when `status = submitted`. This means a draft with zero scores is a valid server record.

## Related

- [Judging screen reference](reference.md)
- [How to judge a competition](howto-judge.md)
- `resources/js/judging.js` — `saveSheet()`, `syncToServer()`, `scheduleRetry()`, `mergeFromIndexedDB()`
