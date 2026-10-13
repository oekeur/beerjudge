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
- **Middleware:** `guest`, `throttle:login` (6 attempts per minute per email and IP, 30 per minute per IP)

#### Request body

| Field | Rules |
|---|---|
| `email` | required, valid email format |
| `password` | required, string |

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
- **Middleware:** `guest`, `throttle:magic-link-request` (3 requests per 5 minutes per email and IP, 45 per 5 minutes per IP)

#### Request body

| Field | Rules |
|---|---|
| `email` | required, valid email format |

#### Behaviour

If the email matches a member, `MagicLink::createForMember()` generates a token and `MagicLinkNotification` delivers the link by email. If the email is unknown, no notification is sent. Either way the response is the same: a redirect back with a success flash. This prevents email enumeration.

### `GET /magic-link/{token}`

Shows a confirmation page with a Log in button. Does not consume the token, so mail scanners that prefetch links cannot burn it.

- **Controller:** `AuthController@showMagicLinkConfirm`
- **Route name:** `auth.magic-link.verify`
- **Middleware:** `guest`, `throttle:magic-link-token` (5 attempts per minute per link and IP, shared by GET and POST on the token path; 60 per minute per IP across all links)
- **Path parameter:** `token` — the plaintext 64-character token from the email link

#### Behaviour

1. Hashes the token with SHA-256 and looks up the matching `MagicLink` record.
2. Calls `isValid()`: rejects the token if `used_at` is set or `expires_at` is in the past.
3. Renders `auth.magic-link-confirm` with a form that POSTs to `auth.magic-link.consume`.

#### Responses

| Condition | Response |
|---|---|
| Valid, unused, unexpired token | 200 confirmation page |
| Unknown or expired or used token | 302 redirect to `login` with `email` error |
| Rate limit exceeded | 429 |

### `POST /magic-link/{token}`

Consumes the token and logs the member in.

- **Controller:** `AuthController@consumeMagicLink`
- **Route name:** `auth.magic-link.consume`
- **Middleware:** `guest`, `throttle:magic-link-token` (shared with GET), CSRF
- **Path parameter:** `token` — the plaintext token from the email link

#### Behaviour

1. Looks up the `MagicLink` by hash.
2. Calls `MagicLink::consume()`, a conditional `UPDATE` on `used_at IS NULL` and `expires_at > now()`. Of two concurrent requests, only one succeeds.
3. Calls `Auth::login()` (remember-me on) and regenerates the session.
4. Redirects admins to `/admin`, others to `/`.

#### Responses

| Condition | Response |
|---|---|
| Valid, unused, unexpired token | 302 redirect to `/admin` or `/` |
| Unknown or expired or used token | 302 redirect to `login` with `email` error |
| Rate limit exceeded | 429 |

---|---|
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
- **Middleware:** `guest`, `throttle:password-reset-confirm` (5 attempts per minute per email and IP, 30 per minute per IP)

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

Password login and magic link login always set a remember-me cookie (`remember: true`; the login form has no checkbox), so members stay signed in after the session expires or the browser closes. The session itself lasts `SESSION_LIFETIME` minutes (10080, one week, in the example env files). Logging in through the password reset flow does not set the remember cookie.

---

## Related

- [auth-howto.md](auth-howto.md) — step-by-step login guides
- [magic-link-reference.md](magic-link-reference.md) — MagicLink model internals
- [active-member-middleware-reference.md](active-member-middleware-reference.md) — what happens when `is_active` is false post-login
