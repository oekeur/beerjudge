# RequireActiveMember Middleware Reference

`App\Http\Middleware\RequireActiveMember` guards all authenticated member-facing routes. It checks two conditions: the request has an authenticated user, and that user's `is_active` flag is true.

---

## Registration

Registered as `active-member` in the middleware alias list. Applied as a named middleware string in route definitions.

Routes that use it:

```
GET  /                     auth, active-member
GET  /profile              auth, active-member
PATCH /profile             auth, active-member
POST /scoresheets          auth, active-member
GET  /judging/{competition}        auth, active-member
GET  /judging/{competition}/progress  auth, active-member
POST /competitions/{competition}/judges  auth, active-member
DELETE /competitions/{competition}/judges auth, active-member
GET  /competitions/{competition}/results/{member}  auth, active-member
GET  /competitions/{competition}/results  auth, active-member
GET  /competitions/{competition}/judge-report/{judge}  auth, active-member
GET  /competitions/{competition}/entries/create  auth, active-member
POST /competitions/{competition}/entries  auth, active-member
```

The Filament admin panel (`/admin`) does **not** use this middleware. Filament calls `Member::canAccessPanel()`, which checks `is_admin` only; an inactive admin can still reach the panel.

---

## Logic

```
handle(Request, Closure): Response
```

| Condition | Action |
|---|---|
| `$request->user()` is null | Redirect to `route('login')` |
| `$user->is_active` is false | Log out, invalidate session, regenerate CSRF token, redirect to `route('login')` with `email` error |
| Both conditions pass | Pass request to the next middleware |

---

## Why logout on inactive (not 403)

If the middleware returned 403 without logging the member out, a session belonging to an inactive account would persist. Every subsequent request to an `auth`-guarded route would pass the `auth` check, reach `RequireActiveMember`, and redirect to `/login`. From `/login`, the `guest` middleware would redirect authenticated users away, producing an infinite redirect loop.

Logging out and invalidating the session before redirecting to `/login` breaks the loop: the member arrives at `/login` as a guest and sees the "account is not active" error once.

---

## Comparison: RequireAdmin

`RequireAdmin` is currently unused (see below). `App\Http\Middleware\RequireAdmin` (alias: `admin`) checks `is_admin` on the authenticated user. It aborts with 403 rather than redirecting, because:

- It only runs after `auth` has already verified the session is valid.
- An active member who is not an admin is a normal application state; a 403 is appropriate.
- There is no redirect-loop risk because the `auth` middleware would not redirect an authenticated member away from `/login`.

`RequireAdmin` is registered as the `admin` alias but is not applied to any route or to the Filament panel. Panel access is controlled by `Member::canAccessPanel()`.

---

## Error message

The `email` session error shown on `/login` after an inactive-member redirect is the `auth.inactive_account` key from `lang/en.json` and `lang/nl.json`. It is translated at redirect time in the member's locale (`SetLocale` runs first in the `web` group) and flashed under `email`, so it appears on the `email` field of the login form.

---

## Related

- [auth-reference.md](auth-reference.md) — login routes and session behaviour
- [registration-reference.md](registration-reference.md) — `is_active` default on new accounts
