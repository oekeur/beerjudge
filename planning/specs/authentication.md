# Authentication

Session-based auth for a closed group of known members. Three ways in: password, magic link, and invitation-based registration. No public sign-up.

## Data model

**`magic_links`** — token (64-char random, unique), member_id, expires_at (15 min), used_at (nullable), timestamps.

**`invitations`** — token (64-char random, unique), type (`single_use` | `time_bound`), expires_at (nullable), used_at (nullable), created_by (FK → members), timestamps.

## Password login

`GET /login` — login form.
`POST /login` — authenticates with email + password. On success, admins redirect to `/admin`; members redirect to `/`. Rate-limited to 6 attempts per minute per IP.

## Magic link (passwordless)

`GET /magic-link` — request form.
`POST /magic-link` — generates a 64-char token, stores it in `magic_links` with a 15-minute expiry, dispatches `MagicLinkNotification` email. Rate-limited to 3 requests per 5 minutes per IP.
`GET /magic-link/{token}` — validates the token (not expired, not used), destroys any existing session, logs in the member, marks `used_at`. Expired or used tokens redirect back to the request form with an error.

Tokens are single-use. The member must request a new one if they miss the 15-minute window.

## Invitation-based registration

Admins generate invitation links in Filament. Two types:

- `single_use` — the first registration marks `used_at` and the token is invalid thereafter.
- `time_bound` — valid until `expires_at`, can be used more than once (useful for sharing a link at a club meeting).

`GET /join/{token}` — validates via `Invitation::isValid()` (not expired, not used on single_use), renders the registration form (name, email, password + confirmation).
`POST /join/{token}` — re-validates the token, creates the member, starts a session. Single-use invitations are marked `used_at` on success.

An invitation that fails validation on either verb renders `auth.invite-invalid` with status 410 instead of the stock error page. `HasTokenExpiry::invalidReason()` splits the two cases so the screen can say which one applies: an expired time-bound link shows its expiry date, a claimed single-use link says it was a one-time link. Both point the visitor at a club admin for a fresh link and link through to `/login` for people who already registered. Unknown tokens still 404 (`firstOrFail`).

Registration produces a regular member account. New members are active by default; the admin can deactivate them in Filament.

## Sessions

Session-based auth throughout — the session cookie covers both the Filament admin panel and all member-facing routes. No separate API tokens. Sessions last approximately one week before re-authentication is required.

## Middleware

**`RequireActiveMember`** — applied to all authenticated routes. Redirects inactive members to the login page, even if their session is valid.

**`SetLocale`** — applied to all authenticated routes. Sets the app locale from `member.locale`.

## Logout

`POST /logout` — destroys the session and redirects to `/login`.

## Views

All auth views (`/login`, `/magic-link`, `/password-reset*`) use the `<x-app-head>` anonymous Blade component for their page head — charset, viewport, PWA meta, favicon, Vite CSS, and theme initialisation are all handled there.

## Out of scope

Public or anonymous registration. OAuth / social login. Two-factor auth. API tokens or bearer auth.
