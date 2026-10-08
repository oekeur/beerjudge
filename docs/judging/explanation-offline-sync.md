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
   success         → mark synced
   no connection   → mark queued, wait for the browser to go online
   server error    → mark error, schedule retry in 5s
```

Every scoresheet is written to the browser's IndexedDB immediately on every input event. The network sync happens after. The UI reflects IndexedDB state, not server state — so the judge's input is never blocked on a server response.

The app tells two failures apart. A `fetch` that rejects means the request never got an answer, almost always because the device is offline. The sheet is marked `queued` and the badge reads "Waiting for connection" in neutral grey. While the browser reports that it is offline, the app does not retry (so the badge does not flicker every 5 seconds), and the `online` event retries the queue at once. If the server does answer with an HTTP error, the sheet is marked `error`, the badge turns red and reads "Retry", the app retries after 5 seconds, and tapping the badge forces an immediate retry.

A request that gets no answer within 15 seconds (headers or body) is aborted and treated like a lost connection, so one stuck request cannot block later saves. While the browser is offline, edits are written to IndexedDB and marked `queued` without sending a request. Saves of one sheet run strictly in order. Without that, a slow draft save could reach the server after the submit and put the sheet back to draft, and an early response could show "Submitted" before the server had the submit.

On page reload or browser restart, the app re-reads IndexedDB and picks up any pending, queued or errored sheets before they were confirmed by the server.

An aborted request can still reach the server later, because its bytes may already be on the wire. Each save therefore carries a `client_revision` that only goes up. The browser stores it in the IndexedDB row, and a fresh page load starts from the revision the server has stored. The server ignores a request whose revision is not above the stored one and answers with the stored sheet, so a late draft cannot overwrite a newer submit. Clients that send no revision are always applied.

IndexedDB holds one row per entry. The store is keyed on the sheet's `client_uuid`, and two tabs of the same link start with different uuids, so every write removes any other row of the same entry, and loading the page keeps only the row with the highest revision (on a tie, an unsynced one, then the latest write) and deletes the rest. A sheet keeps its own `client_uuid` for its whole life, even after it adopts the server's content; the server matches by entry and judge anyway.

Each save captures a snapshot of the sheet's content together with its revision, and stores it in the IndexedDB row. Requests and retries always send that snapshot for its revision, never the live sheet. Text typed after the save (the notes field saves 500 ms after the last keystroke) therefore never goes out under an older revision.

The same guard covers a judge with two tabs or devices. When the second device saves with a revision the server already passed, the response carries `ignored: true` and the full stored sheet. The browser then replaces its local copy with the server's sheet (memory and IndexedDB), takes the server's revision, marks the sheet synced and shows "Updated from another device" on that entry until the next local edit. It never reports its own rejected payload as saved or submitted. The browser adopts the server's sheet only when the judge has no unsaved edits and no notes field of that entry is focused. If the judge is typing, the pending save goes out above the server's revision instead and wins. If the server's content equals the local content (compared trimmed the way Laravel's `TrimStrings` does, including zero-width characters, with empty and whitespace-only notes equal, descriptors in any order), for example when a retry of an already applied save is ignored, nothing is rewritten and no notice shows. The edit that was ignored is lost; this is the last-write-wins trade-off below, made visible.

## One active tab per browser

Two tabs of the judging screen share one IndexedDB but not memory, and each would write its own copy of a sheet over the other's. Only one tab per competition is active. It holds a Web Lock (`beerjudge-judging-<competition id>`) for as long as it lives, so closing or crashing the tab releases it. A second tab that cannot get the lock shows "Judging is open in another tab." with a **Use this tab** button, hides the entries, and does not send saves or write IndexedDB. **Use this tab** takes the lock over (`steal`). The previous tab loses it at once, clears its timers and its `online` listener, stops writing to IndexedDB (every write checks that the tab still holds the lock), and shows the banner. The new tab then rebuilds its state from IndexedDB, which is authoritative after a take-over: each entry with a row takes the row's content, saved snapshot, revision and sync state, and only unsynced rows are requeued; entries without a row go back to the server's copy. Each active period has an epoch number; work that finishes after the lock was lost (a response, an IndexedDB write, a timer or listener registration in `activate()`) compares its epoch and does nothing if it is out of date. A take-over that fails for a reason other than being stolen leaves the banner in place and logs an error. Browsers without the Web Locks API fall back to the single-tab assumption.

## The page itself is not cached

The service worker caches the JavaScript, CSS and fonts, not the judging page. The page is server-rendered (entries, scores already on the server, and the translated labels), so it needs the network to load. The judging page must therefore be open before the connection drops. A reload or a fresh visit while offline fails; the sheets waiting in IndexedDB are still there and sync once the page loads again with a connection. This is a deliberate choice: caching the page would show stale entry lists and stale sheet state.

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
- `resources/js/judging.js` — `saveSheet()`, `syncToServer()`, `scheduleRetry()`, `flushQueue()`, `mergeFromIndexedDB()`
