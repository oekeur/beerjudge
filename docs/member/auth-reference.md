# Auth Reference

Covers all authentication routes: password login, logout, magic link, and password reset.

All routes in the `guest` middleware group redirect already-authenticated users away. The `logout` route requires `auth`. All other protected routes require `auth` plus `active-member`.

---

## Password login

### `GET /login`

Displays the login form.

- **Controller:** `AuthController@showLogin`
- **Route name:** `login`
- **Middleware:** `guest`

### `POST /login`

Attempts credential-based login.

- **Controller:** `AuthController@login`
- **Middleware:** `guest`, `throttle:6,1` (6 attempts per minute per IP)

#### Request body

| Field | Rules |
|---|---|
| `email` | required, valid email format |
| `password` | required, string |
| `remember` | optional boolean; passes a remember-me cookie when true |

#### Behaviour

`Auth::attempt()` checks the email/password pair against `members.password` (bcrypt-hashed). On success, the session is regenerated and the member is redirected:

- Admins (`is_admin = true`) redirect to `/admin`.
- All other members redirect to `/`.

`redirect()->intended()` respects any URL the member was trying to reach before being sent to `/login`.

On failure, the response redirects back with an `email` validation error. The plain password is not flashed back.

#### Responses

| Condition | Response |
|---|---|
| Valid credentials | 302 redirect to `/admin` or `/` |
| Invalid credentials | 302 redirect back, session error on `email` |
| Rate limit exceeded | 429 |

---

## Logout

### `POST /logout`

Terminates the current session.

- **Controller:** `AuthController@logout`
- **Route name:** `logout`
- **Middleware:** `auth`

Calls `Auth::logout()`, invalidates the session, regenerates the CSRF token. Redirects to `/login`.

---

## Magic link

A passwordless login flow. The member requests a link by email; clicking it logs them in without a password.

### `GET /magic-link`

Displays the magic link request form.

- **Controller:** `AuthController@showMagicLinkForm`
- **Route name:** `auth.magic-link.request`
- **Middleware:** `guest`

### `POST /magic-link`

Sends a magic link email.

- **Controller:** `AuthController@sendMagicLink`
- **Middleware:** `guest`, `throttle:3,5` (3 attempts per 5 minutes per IP)

#### Request body

| Field | Rules |
|---|---|
| `email` | required, valid email format |

#### Behaviour

If the email matches a member, `MagicLink::createForMember()` generates a token and `MagicLinkNotification` delivers the link by email. If the email is unknown, no notification is sent. Either way the response is the same: a redirect back with a success flash. This prevents email enumeration.

### `GET /magic-link/{token}`

Verifies a magic link token and logs the member in.

- **Controller:** `AuthController@verifyMagicLink`
- **Route name:** `auth.magic-link.verify`
- **Middleware:** `guest`, `throttle:5,1` (5 attempts per minute per IP)
- **Path parameter:** `token` — the plaintext 64-character token from the email link

#### Behaviour

1. Hashes the token with SHA-256 and looks up the matching `MagicLink` record.
2. Calls `isValid()`: rejects the token if `used_at` is set or `expires_at` is in the past.
3. Calls `markUsed()` to stamp `used_at`.
4. Calls `Auth::login()` and regenerates the session.
5. Redirects admins to `/admin`, others to `/`.

#### Responses

| Condition | Response |
|---|---|
| Valid, unused, unexpired token | 302 redirect to `/admin` or `/` |
| Unknown or expired or used token | 302 redirect to `login` with `email` error |
| Rate limit exceeded | 429 |

---

## Password reset

Laravel's built-in password broker, configured for the `Member` model.

### `GET /password/reset`

Displays the reset-request form (email input).

- **Controller:** `PasswordResetController@showRequestForm`
- **Route name:** `password.request`
- **Middleware:** `guest`

### `POST /password/reset`

Sends a password reset email.

- **Controller:** `PasswordResetController@sendResetLink`
- **Route name:** `password.email`
- **Middleware:** `guest`

#### Request body

| Field | Rules |
|---|---|
| `email` | required, valid email format |

#### Behaviour

Sends a reset email only when the member exists **and** `is_active = true`. Unknown email addresses and inactive members both receive the same success flash; no error distinguishes them.

### `GET /password/reset/{token}`

Displays the new-password form.

- **Controller:** `PasswordResetController@showResetForm`
- **Route name:** `password.reset`
- **Middleware:** `guest`
- **Path parameter:** `token` — the reset token from the email link
- **Query parameter:** `email` — pre-fills the email field; expected to be present from the emailed link

### `POST /password/reset/confirm`

Applies the new password.

- **Controller:** `PasswordResetController@resetPassword`
- **Route name:** `password.update`
- **Middleware:** `guest`, `throttle:5,1` (5 attempts per minute per IP)

#### Request body

| Field | Rules |
|---|---|
| `token` | required |
| `email` | required, valid email format |
| `password` | required, string, min 8, confirmed |
| `password_confirmation` | must match `password` |

#### Behaviour on success

Sets the new password, rotates the remember token, fires `PasswordReset`, logs the member in, and redirects to `/`.

#### Responses

| Condition | Response |
|---|---|
| Valid token and matching email | 302 redirect to `/` (authenticated) |
| Invalid token | 302 redirect back with `email` error |
| Mismatched confirmation | 302 redirect back with `password` error |
| Rate limit exceeded | 429 |

---

## Session duration

Sessions last approximately one week by default. The `remember` flag on `POST /login` and magic link login do not change this; the application does not configure extended remember-me cookies beyond the default session TTL.

---

## Related

- [auth-howto.md](auth-howto.md) — step-by-step login guides
- [magic-link-reference.md](magic-link-reference.md) — MagicLink model internals
- [active-member-middleware-reference.md](active-member-middleware-reference.md) — what happens when `is_active` is false post-login
