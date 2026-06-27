# Registration Reference

Covers the invitation model, registration routes, and their validation rules.

---

## Invitation

An `Invitation` record gates access to the registration form. Admins create invitations via Filament; no route exists for member-side creation.

### Types

| Constant | Value | Behaviour |
|---|---|---|
| `Invitation::TYPE_SINGLE_USE` | `single_use` | Marked used after the first successful registration. Subsequent visits return 410. |
| `Invitation::TYPE_TIME_BOUND` | `time_bound` | Valid until `expires_at`; never marked used regardless of how many registrations occur. |

### Fields

| Field | Type | Description |
|---|---|---|
| `id` | int | Primary key. |
| `token` | string(64) | Random URL-safe token. Never hashed. |
| `type` | string | `single_use` or `time_bound`. |
| `expires_at` | datetime\|null | Null means no expiry. Checked on every request. |
| `used_at` | datetime\|null | Set when a single-use invitation is consumed. |
| `created_by` | int | Foreign key to `members.id`. |

### Validity

An invitation is valid when `used_at` is null **and** either `expires_at` is null or `expires_at` is in the future. The `scopeValid` query scope applies both conditions. `Invitation::isValid()` (from `HasTokenExpiry`) performs the same check on an already-loaded model.

### `Invitation::generate()`

```php
Invitation::generate(string $type, ?Carbon $expiresAt, int $createdBy): Invitation
```

Creates and persists a new invitation with a 64-character random token.

---

## Routes

All registration routes are wrapped in the `guest` middleware group. Authenticated users cannot reach them.

### `GET /join/{token}`

Displays the registration form.

- **Controller:** `RegistrationController@show`
- **Route name:** `register`
- **Path parameter:** `token` — the plain invitation token
- **Response:** 200 with the `auth.register` view, or 404 if the token does not exist, or 410 if the invitation is expired or already used.

### `POST /join/{token}`

Creates a new member account.

- **Controller:** `RegistrationController@store`
- **Path parameter:** `token` — the plain invitation token
- **No route name.**

#### Request body

| Field | Rules |
|---|---|
| `name` | required, string, max 255 |
| `email` | required, valid email format, max 255, unique in `members` table |
| `password` | required, string, min 8, confirmed (`password_confirmation` field must match) |

#### Behaviour on success

1. Creates a `Member` with `is_active = false` and `locale = 'en'`.
2. For `single_use` invitations: calls `markUsed()` on the invitation.
3. For `time_bound` invitations: does not modify the invitation.
4. Redirects to `route('login')` with a success flash.

#### Responses

| Condition | Response |
|---|---|
| Valid token, valid input | 302 redirect to `/login` |
| Invalid token (not found) | 404 |
| Expired or used invitation | 410 |
| Validation failure | 422 (redirect back with errors) |

---

## Member model: registration-relevant fields

| Field | Type | Default on creation |
|---|---|---|
| `is_active` | boolean | `false` |
| `locale` | string | `'en'` |
| `is_admin` | boolean | `false` (implicit, not set by registration) |

A new member cannot log in until an admin sets `is_active = true` in Filament.

---

## Related

- [registration-howto.md](registration-howto.md) — step-by-step guide for inviting and registering a member
- [auth-reference.md](auth-reference.md) — login and password reset routes
- [active-member-middleware-reference.md](active-member-middleware-reference.md) — what happens when `is_active` is false
