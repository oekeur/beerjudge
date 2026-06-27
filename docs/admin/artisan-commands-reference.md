# Artisan Commands Reference: member:*

Three Artisan commands manage the member lifecycle outside the Filament UI. All three prompt interactively when required arguments are omitted.

---

## member:create

**Signature**

```
php artisan member:create [--name=NAME] [--email=EMAIL] [--admin]
```

**Options**

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| `--name` | string | No | Member's display name. Prompted if omitted. |
| `--email` | string | No | Member's email address. Must be unique in `members.email`. Prompted if omitted. |
| `--admin` | flag | No | Grants admin access without an interactive prompt. |

**Interactive prompts** (in order):

1. `Name` — skipped if `--name` is provided.
2. `Email` — skipped if `--email` is provided.
3. `Password` — always prompted; hidden input (`secret()`). Cannot be empty.
4. `Grant admin access?` — skipped if `--admin` flag is present. Defaults to `no`.

**Validation**

- `name` is required.
- `email` is required, must be a valid email format, and must not already exist in the `members` table.

**Side effects**

Creates one row in `members` with `is_active = true`. Password is hashed via Laravel's `Member::create()` (the model's mutator handles hashing).

**Exit codes**

- `0` (SUCCESS) — member created.
- `1` (FAILURE) — validation failed or password was empty.

**Output on success**

```
Member created: Jane Brewer (jane@example.com) [admin].
```
The `[admin]` suffix appears only when admin access was granted.

---

## member:delete

**Signature**

```
php artisan member:delete [email]  [--deactivate]
```

**Arguments**

| Argument | Required | Description |
|----------|----------|-------------|
| `email` | No | Member's email address. Prompted if omitted. |

**Options**

| Option | Type | Description |
|--------|------|-------------|
| `--deactivate` | flag | Sets `is_active = false` immediately, skipping the interactive choice prompt. |

**Interactive flow** (when `--deactivate` is not provided):

1. Looks up the member by email. Prints name, email, and admin status.
2. Presents a choice: `Deactivate (keeps data, blocks login)` or `Delete permanently`.
3. If "Delete permanently" is chosen: asks for a second confirmation (`y/N`). Aborts if declined.

**Side effects**

- Deactivate path: sets `is_active = false` on the member row. All related data (entries, scoresheets) is retained.
- Delete path: hard-deletes the member row. Cascade behaviour depends on foreign key constraints in the database schema.

**Exit codes**

- `0` (SUCCESS) — deactivated, deleted, or aborted by the user.
- `1` (FAILURE) — no member found with the given email.

---

## member:password

**Signature**

```
php artisan member:password [email]
```

**Arguments**

| Argument | Required | Description |
|----------|----------|-------------|
| `email` | No | Member's email address. Prompted if omitted. |

**Interactive prompts** (in order):

1. `New password` — hidden input. Cannot be empty.
2. `Confirm password` — hidden input. Must match the first entry exactly.

**Side effects**

Updates `members.password` for the matched row. The model's `password` setter hashes the value before storage.

**Exit codes**

- `0` (SUCCESS) — password updated.
- `1` (FAILURE) — no member found, password was empty, or passwords did not match.

**Output on success**

```
Password updated for Jane Brewer (jane@example.com).
```

---

## Related

- [How to add a new member](artisan-commands-howto.md)
- [Filament admin panel overview](filament-overview-explanation.md)
