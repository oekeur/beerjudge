# MagicLink Reference

`App\Models\MagicLink` stores one-time login tokens for passwordless authentication.

---

## Table: `magic_links`

| Column | Type | Notes |
|---|---|---|
| `id` | int | Primary key. |
| `member_id` | int | Foreign key to `members.id`. |
| `token` | string | SHA-256 hash of the plaintext token. Never stored in plain form. |
| `expires_at` | datetime | Always set; 15 minutes from creation. |
| `used_at` | datetime\|null | Null until consumed. Set by `consume()`. |

---

## Static methods

### `MagicLink::createForMember(Member $member): string`

Creates a new magic link for the given member and returns the **plaintext** token.

1. Generates a 64-character random string via `Str::random(64)`.
2. Hashes it with SHA-256 and stores the hash in `magic_links.token`.
3. Sets `expires_at` to `now()->addMinutes(15)`.
4. Returns the plaintext string (used to build the verification URL).

The plaintext token is never persisted. Only the SHA-256 hash is stored.

### `MagicLink::findByPlaintext(string $plain): ?MagicLink`

Hashes `$plain` with SHA-256 and queries `magic_links.token`. Returns the matching record or null.

Used in `AuthController@showMagicLinkConfirm` and `AuthController@consumeMagicLink` to look up the record from the URL token.

---

## Instance methods (from `HasTokenExpiry` trait)

### `isValid(): bool`

Returns `true` when both conditions hold:

- `used_at` is null
- `expires_at` is null **or** `expires_at` is in the future

For `MagicLink`, `expires_at` is always set, so only the "not in the past" condition applies.

### `consume(): bool`

Marks the link used with one conditional `UPDATE` (`used_at IS NULL` and `expires_at` in the future) and returns true only if this call changed the row. Called by `AuthController@consumeMagicLink` before `Auth::login()`, so a token cannot be reused and two concurrent POSTs cannot both log in.

---

## Relationship

### `member(): BelongsTo<Member, MagicLink>`

Returns the `Member` this link belongs to.

---

## Token security

| Property | Value |
|---|---|
| Plaintext length | 64 characters (`Str::random`) |
| Storage format | SHA-256 hash |
| Expiry | 15 minutes from creation |
| One-time use | Yes; `consume()` stamps `used_at` on POST, before `Auth::login()`; the GET never does |

Storing only the hash means a database read alone cannot produce a working link. The plaintext exists only in transit (the emailed URL).

---

## Verification URL format

```
/magic-link/{plaintext-token}
```

The route name is `auth.magic-link.verify`. `GET` hits `AuthController@showMagicLinkConfirm`, which renders a Log in button. The button POSTs to the same path (route name `auth.magic-link.consume`), handled by `AuthController@consumeMagicLink`. Both receive the plaintext token as the `$token` path parameter, hash it, and query the database.

---

## No automatic cleanup

Used and expired records are not removed automatically. They remain in the database. No scheduled job or observer currently purges old rows. This is acceptable at the current scale but worth noting if the table grows unexpectedly.

---

## Related

- [auth-reference.md](auth-reference.md) — magic link routes and rate limits
- [auth-howto.md](auth-howto.md) — how to request and use a magic link
