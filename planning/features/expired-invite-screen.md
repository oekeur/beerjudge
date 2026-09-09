# Expired invite screen

Issue: [#15](https://github.com/oekeur/beerjudge/issues/15)
Spec: `planning/specs/authentication.md` — Invitation-based registration

## Problem

`RegistrationController::resolveInvitation()` runs `abort_unless($invitation->isValid(), 410)`. An expired time-bound link or a claimed single-use link therefore renders Laravel's stock 410 page. The visitor is a prospective member without an account, so neither the error page nor the login page tells them anything actionable.

## Approach

Keep the 410 status; replace the body with a branded Blade screen that names the reason and the next step.

The controller stops aborting and instead returns `response()->view('auth.invite-invalid', [...], 410)` for an invalid invitation. That is what lets the screen distinguish "expired" from "already used" — an error view resolved by status code alone cannot see the invitation.

Unknown tokens keep their 404 (`firstOrFail`). That is a different situation (mistyped or truncated link) and is not part of this change.

## Tasks

1. **`HasTokenExpiry::invalidReason()`** — returns `'used'`, `'expired'`, or `null`. Rewrite `isValid()` as `invalidReason() === null` so the two cannot disagree. The trait is shared with `MagicLink`; the addition is inert there.

2. **`RegistrationController`** — drop `abort_unless`. `show()` returns `View|Response`, `store()` returns `RedirectResponse|Response`; both return the 410 view when the invitation is invalid. `store()` must check before creating the member.

3. **`resources/views/auth/invite-invalid.blade.php`** — `<x-page>` + `<x-auth-card>`, matching the other auth screens (amber heading, dark-mode pairs). Contents: reason-specific heading and body, the expiry date when there is one, "ask a club admin for a new link", and a link to `route('login')` for people who already have an account.

4. **Copy** — `auth.invite_*` keys in `lang/en.json` and `lang/nl.json`, alongside the existing auth keys.

5. **Tests** — extend `tests/Feature/InvitationTest.php`: expired GET renders the expired copy at 410, used GET renders the used copy at 410, POST with an expired token returns 410 and creates no member. Existing 410 status assertions stay. Add `invalidReason()` cases to `tests/Unit/HasTokenExpiryTest.php`.

6. **Spec** — update the `GET /join/{token}` / `POST /join/{token}` lines in `planning/specs/authentication.md` to record the explanation screen.

## Verification

- `php artisan test --compact --filter=Invitation` and `--filter=HasTokenExpiry`
- `./vendor/bin/phpstan analyse`, `vendor/bin/pint --dirty`
- Drive both states in a browser (seed an expired and a used invitation, load each `/join/{token}`) — light and dark.

## Out of scope

Unknown-token 404s. Expired magic links and password-reset links, which already redirect to a form with a flash error rather than dead-ending.
