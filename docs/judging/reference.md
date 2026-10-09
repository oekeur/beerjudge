# Judging Screen

The judging screen is the primary interface for judges during a competition. It renders all eligible entries as scoresheets, auto-saves input to IndexedDB on every keystroke, and syncs to the server in the background. Judges can work through the screen with or without a network connection; pending sheets retry automatically when connectivity returns.

Any active member can open the screen while the competition is in Judging. Members not yet registered see a banner with a **Register as judge** button. Judges never see their own entries.

## Routes

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| `GET` | `/judging/{competition}` | member + active | Render the judging screen |
| `GET` | `/judging/{competition}/progress` | member + active + admin | JSON; per-entry submission counts |
| `POST` | `/scoresheets` | member + active | Upsert a scoresheet |

The screen is only accessible when the competition has `status = judging`. Any other status returns HTTP 403.

## BJCP scoring sections

| Section | Max | Field |
|---------|-----|-------|
| Aroma | 12 | `aroma` |
| Appearance | 3 | `appearance` |
| Flavour | 20 | `flavour` |
| Mouthfeel | 5 | `mouthfeel` |
| Overall Impression | 10 | `overall` |
| **Total** | **50** | computed |

## `POST /scoresheets` payload

```json
{
  "client_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "client_revision": 7,
  "entry_id": 42,
  "status": "draft",
  "aroma": 8,
  "appearance": 2,
  "flavour": 14,
  "mouthfeel": 4,
  "overall": 7,
  "aroma_notes": "Clean, malty...",
  "appearance_notes": null,
  "flavour_notes": null,
  "mouthfeel_notes": null,
  "overall_notes": null,
  "descriptors": ["diacetyl", "acetaldehyde"]
}
```

All five score fields are nullable when `status = draft`. When `status = submitted`, all five are required.

`client_revision` is optional. The judging screen increases it on every save of a sheet and keeps it in IndexedDB. If it is not above the revision stored for the sheet, the server ignores the payload and answers with the stored sheet. Payloads without it always apply.

Notes fields accept up to 2000 characters each. `descriptors` is an array of slug strings from the available descriptor set; it may be empty or omitted.

## `POST /scoresheets` response

```json
{
  "id": 123,
  "client_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "client_revision": 7,
  "total": 35,
  "status": "submitted",
  "saved_at": "2026-06-25T14:30:00Z",
  "ignored": false,
  "sheet": { "entry_id": 42, "client_revision": 7, "status": "submitted", "aroma": 8, "...": "..." }
}
```

`ignored` is `true` when the payload's `client_revision` was not above the stored one. Nothing was written; `sheet` holds the stored sheet (scores, notes, descriptors, status, revision), and the judging screen adopts it.

## Idempotency

The server looks up an existing sheet by `client_uuid` for this judge, and if none matches, by `(entry_id, judge_id)`; it updates that sheet or creates one. A judge therefore has at most one sheet per entry, and retrying a failed POST never creates a duplicate.

A UUID cannot be reused across judges. If judge B sends a `client_uuid` already recorded under judge A, the server returns HTTP 422.

## Sheet statuses

| Status | Meaning | Score fields |
|--------|---------|--------------|
| `draft` | In progress; auto-saved | Nullable |
| `submitted` | Final; read-only in the UI | All required |

A submitted sheet can be reopened (status set back to `draft` by the client) and resubmitted until the competition closes.

## Scoresheet validation rules

| Field | Rules |
|-------|-------|
| `client_uuid` | required, UUID |
| `client_revision` | nullable, integer, minimum 0 |
| `entry_id` | required, integer, must exist in `entries` table |
| `aroma` | nullable (draft) / required (submitted); integer, 0–12 |
| `appearance` | nullable / required; integer, 0–3 |
| `flavour` | nullable / required; integer, 0–20 |
| `mouthfeel` | nullable / required; integer, 0–5 |
| `overall` | nullable / required; integer, 0–10 |
| `*_notes` | nullable, string, max 2000 chars each |
| `descriptors` | nullable array of valid descriptor slugs |
| `status` | required; `draft` or `submitted` |

Beyond field validation, the server enforces:

- Competition must be in `judging` phase (otherwise HTTP 422)
- Judge cannot score their own entry (HTTP 422)
- `client_uuid` cannot be owned by a different judge (HTTP 422)

## Scoring bands

The total score maps to six qualitative bands:

| Band | Range |
|------|-------|
| Outstanding | 45–50 |
| Excellent | 38–44 |
| Very Good | 30–37 |
| Good | 21–29 |
| Fair | 14–20 |
| Problematic | 0–13 |

## Offline sync state machine

Each sheet cycles through these sync states in the browser:

| State | Meaning |
|-------|---------|
| `idle` | No local changes |
| `pending` | Written to IndexedDB; awaiting server sync |
| `syncing` | POST in flight |
| `synced` | Server confirmed |
| `queued` | The POST never got an answer (no connection); waits in IndexedDB until the browser is back online |
| `error` | The server answered with an HTTP error; retry scheduled in 5 seconds |

Saves of one sheet run one after the other, so a slow draft save cannot reach the server after the submit. A sheet is only marked `synced` when no newer save is waiting.

## Admin progress panel

When an admin views the judging screen, a collapsible panel shows per-entry submission counts (`submitted / eligible judges`, where eligible judges are registered judges other than the entry's brewer). This panel polls `GET /judging/{competition}/progress` every 30 seconds.

## Style guide and style info modals

Each scoring section has an info button that opens the style guide modal, showing the BJCP guideline text for that section. The appearance section also shows an SRM colour gradient across the style's declared range.

Clicking an entry's style name opens the style info modal with the style code, category description, history, and style comparison text.

## Related

- [How to judge a competition](howto-judge.md)
- [Why the judging screen is offline-first](explanation-offline-sync.md)
- `app/Http/Controllers/JudgingController.php`
- `app/Http/Controllers/ScoresheetController.php`
- `resources/js/judging.js`
