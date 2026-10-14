# Tell the judge when a scoresheet save is rejected

Plan date: 2026-10-14. Task: TASK-84. Depends on: none in Backlog; assumes TASK-107 (lang key-parity test) has landed, see Unknowns. TASK-90 (style-guide dialog accessibility) edits the same judging screen and runs after this plan lands.

## Goal

A 4xx answer to `POST /scoresheets` stops the 5-second retry loop and tells the judge what happened: log in again (401/419, sheet stays queued locally) or why the server refused the sheet (403/422).

## Out of scope

- 5xx and 429 responses keep the current retry-with-badge behaviour (transient; the existing Sentry report stays).
- Returning the judge to the judging screen after logging in again (`url.intended` on a fetch 401). The login link goes to the login page; the sheet flushes when the judging screen is next opened.
- Refreshing the CSRF token in place. Not needed: a retry in the same tab after a re-login in another tab passes (shared session cookie plus Sec-Fetch-Site).
- Any rate limit or backoff on 5xx.

## Verified facts (2026-10-14, read from the repo)

- `resources/js/judging.js` `sendSheet`: any `!res.ok` throws `HTTP <status>`, and the catch calls `markUnsynced(entryId, 'error')`, which pushes the sheet on `syncQueue` and calls `scheduleRetry()` (5 s, forever).
- The request sends `Accept: application/json`. Verified by the red-team (probe against the app and Chromium, 2026-10-14): Laravel 13's `PreventRequestForgery` skips the token check when `Sec-Fetch-Site: same-origin` is present, and Chromium's fetch sends it. So an expired or cleared session gets 401 `{"message":"Unauthenticated."}`, not 419. 419 only arises without that header (older browsers). Handle both; tests assert 401.
- `RequireActiveMember` answers a deactivated member with `redirect()->route('login')`. `fetch` follows it and receives a 200 HTML login page; `res.json()` then throws a `SyntaxError`, which also lands in the retry loop today. It must count as an auth failure.
- `ScoresheetController::upsert` aborts with English literals: 422 "Competition is not in judging phase.", 403 "Join the competition before scoring.", 422 "You cannot score your own entry." and 422 "UUID already in use by another judge." (a judge-side client_uuid collision).
- `rebuildFromIndexedDB` re-queues rows whose `syncStatus` is in `['pending','queued','error']`; the `rank` helper treats any non-`synced` row as unsynced. New states must be added to the first list so a reload after logging in re-sends them.
- Badges render in two places in `resources/views/judging/show.blade.php` (entry header, line ~201; open sheet, line ~425), both with `x-show` on `syncStatus`.
- Labels reach Alpine as the Blade-built `$labels` array at the top of `show.blade.php`.
- `tests/Browser/judging-sync-report.spec.ts` currently asserts the opposite of this task: it waits 7 s and expects the sheet to "keep retrying" after a mocked 422 (it only asserts the report is not repeated). It must change.
- No Feature test asserts the English abort messages (grep of `tests/`, hits only in the browser spec mock).

## Design

State per sheet: `syncStatus` gains `'auth'` and `'rejected'`, plus `sheets[id].syncMessage` (string, shown for `rejected`).

Classification in `sendSheet`:

| Condition | Result |
|---|---|
| 401 (the real case), 419 (browsers without Sec-Fetch-Site), or `res.redirected` / non-JSON 2xx body | `auth` |
| 403, 422, other 4xx except 408 and 429 | `rejected`, message = `json.message` from the server, else generic label |
| network failure, abort, 5xx, 408, 429 | unchanged (`queued` / `error`, retries) |

For `auth` and `rejected` the sheet is written to IndexedDB with that status and is NOT pushed to `syncQueue` and `scheduleRetry()` is not called. The sheet stays in IndexedDB, so nothing is lost. A new edit on the sheet still triggers one attempt (it sets `pending` first), which is a user action, not a loop. Clicking the badge calls the existing `retryNow` for one attempt.

`authExpired` (top-level Alpine flag): set true on any `auth` result. On any successful save it is cleared and every sheet still in `auth` is pushed onto `syncQueue` and flushed once, so a banner that disappears never leaves an unsaved sheet behind. While true, a `role="alert"` banner (`data-testid="session-expired-banner"`) at the top of the screen reads "You are signed out. Your scoresheets are saved on this device. Log in again to save them." with a link to `route('login')` (same tab; sheets survive in IndexedDB and flush on the next judging-screen load).

Badges: `auth` shows "Not saved: log in again"; `rejected` shows "Not saved" and the server reason below the badge (`data-testid="sync-rejected-reason"`).

Server messages become translatable: `ScoresheetController` aborts use `__('judging.rejected.*')` so the reason matches the judge's locale (the request goes through `SetLocale`). JS shows `json.message`, with `judging.rejected.generic` as the fallback when the body has no message (validation 422 carries Laravel's translated message already).

## Phase 1: Server messages and client classification (one task, one browser review)

The screen, the controller messages and the spec are reviewed together, so this is one phase and one task. File ownership: one task writes `judging.js`, `show.blade.php`, the controller, both lang files and both test files.

### TASK-84 (one task)

- Replace the four English `abort` literals in `app/Http/Controllers/ScoresheetController.php` with `__('judging.rejected.not_judging' | 'not_participant' | 'own_entry' | 'uuid_in_use')`. Status codes unchanged.
- Add keys to BOTH `lang/en.json` and `lang/nl.json` in the same commit (TASK-107 adds a key-parity unit test): `judging.rejected.not_judging`, `judging.rejected.not_participant`, `judging.rejected.own_entry`, `judging.rejected.uuid_in_use`, `judging.rejected.generic`, `judging.sync.not_saved`, `judging.sync.login_again_badge`, `judging.sync.signed_out`, `judging.sync.login_link`. Pass the JS-used ones through the `$labels` array in `show.blade.php`; the badge and banner strings render directly with `__()`. No JS string literals for user-facing text.
- `judging.js`: classification table above (on `redirected`/non-JSON set `error.status = 'redirect'`); `auth`/`rejected` skip the queue and the retry timer; add both statuses to the `unsynced` list in `rebuildFromIndexedDB`; `authExpired` set/clear; keep `reportSyncFailure` for every classified failure including `auth` (once per `entry:status`, so a signed-out judge sends one warning per sheet; accepted, it is a real signal).
- `show.blade.php`: banner, two badge sets (entry header and open sheet) using the same markup as the existing `retry` badge, reason line for `rejected`.
- Extend `tests/Feature/ScoresheetUpsertTest.php` (or the file that already covers 422/403, read it first): assert all four refusals (not judging, not a participant, own entry, uuid in use) return the translated message in `en` and `nl`.
- `tests/Browser/judging-sync-report.spec.ts`: replace the "keeps retrying" tail with: after the mocked 422 the sheet shows the reason, and `/scoresheets` request count stays at 1 over 7 s (no loop). The Sentry assertion stays unchanged (the mock body is fixed). Drop the existing second click (Appearance 2) and its 7 s tail: the assertion is exactly one `/scoresheets` request over 7 s after the first score. Add two cases to the same file: a mocked 302 to `/login` followed by a 200 HTML body (sheet shows the log-in-again badge, one request in 7 s) and a mocked 503 (more than one request within 12 s, badge stays on Retry).
- New `tests/Browser/judging-session-expired.spec.ts` (exactly one task writes each spec file): log in, open the judging screen, `context.clearCookies()`, score a section (the real response is 401); assert the banner and the sheet's "log in again" badge are visible, the request count to `/scoresheets` is 1 after 7 s (no retry loop), and IndexedDB still holds the sheet with `syncStatus: 'auth'`. Then log in through a second page in the same context and score a second sheet in the first tab: both sheets reach Saved without a reload and the banner disappears (covers the flush-on-success rule); finally assert the server rows exist via the tinker helper pattern from `judging-offline.spec.ts`. Real expired session, no route mock.
- Run `vendor/bin/pint --dirty --format agent`, the two PHP/Playwright specs, and `npm run build`.

Done when: [AC 1] a cookie-less `POST /scoresheets` (401) shows the log-in-again banner and badge with the sheet kept in IndexedDB and exactly one request sent in 7 s; a 422/403 shows the server's translated reason on the sheet and sends no retry within 7 s; a mocked redirect-to-login response behaves like 401 and a mocked 503 still retries (both in `judging-sync-report.spec.ts`). [AC 2] `judging-session-expired.spec.ts` passes against a local server (`npm run test:browser -- judging-session-expired judging-sync-report`).

Context: none verified beyond "Verified facts" above.

## Unknowns

- [RISK] Wording: banner says "signed out" and links to login; a deactivated member sees it too but cannot log in (login then shows the inactive-account error). Production rarely reaches `auth` because remember-me re-logs the judge in silently (logout in another tab, lost remember cookie on Firefox PWA, deactivation). Deferred: confirm the wording with Oscar in the browser review.
- Login return path: after re-login the judge lands on `/`, not the judging screen. Accepted for now (out of scope above); confirm with Oscar in the browser review.
- Manual browser review is still required for the Dutch wording and phone-width layout of the banner (Playwright covers behaviour, not look). Task goes to Review after the gate.
- Sibling overlap: TASK-90 edits `judging.js` and `show.blade.php` after this one lands (recorded dependency); TASK-107/108/109 write `lang/en.json` and `lang/nl.json`, and TASK-107's key-parity test makes both files mandatory for every new key. Not a Backlog dependency of TASK-84 (the orchestrator recorded only 64/87/90). Assumption: 107 lands first; otherwise the executor checks both lang files by hand for identical key sets.
