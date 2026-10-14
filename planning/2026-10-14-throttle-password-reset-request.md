# Plan: Rate-limit the password reset email request

**File:** planning/2026-10-14-throttle-password-reset-request.md
**Goal:** `POST /password/reset` (send reset link) uses a named limiter keyed by name plus IP and email, and its response stays identical whether or not the address belongs to a member.
**Out of scope:** a new 429 page (`resources/views/errors/429.blade.php` already exists and is reused); the `trustProxies('*')` X-Forwarded-For bypass of IP-keyed limits (affects every limiter, depends on the proxy); translating the reset email (TASK-64, runs after this task); changing the existing limiters' numbers.
**Constraints:** Follow the `emailKey` pattern in `AppServiceProvider`. No new lang keys (the existing 429 view and the unchanged `passwords.sent` flash are reused), so the en.json/nl.json parity rule is not touched. This task changes only the route middleware and the limiter; it does not edit `PasswordResetController`. `emailKey` is left as is: red-team probe 2026-10-14 showed Laravel's global TrimStrings middleware trims input before the limiter runs, so padded emails already share one counter.

Assumptions about siblings: TASK-64 lands after this task. Its plan (planning/2026-10-14-translate-admin-login-reset-mail.md) overrides `Member::sendPasswordResetNotification` and does not touch the controller, so there is no file overlap. No sibling touches `AppServiceProvider` limiters or `RateLimitingTest`.

---

## Phase 1: Named limiter on the reset-link request

**Outcome:** The 4th request for one email from one IP inside 5 minutes, and the 46th request from one IP inside 5 minutes, answer 429; a request for an unknown address answers exactly like one for a member; members sharing the club IP are not locked out.

**Interface contract:** `POST /password/reset` (route `password.email`) gains `->middleware('throttle:password-reset-request')`. Success and unknown-address responses are the same redirect back with flash `success = __('passwords.sent')`. A throttled request answers the framework's 429 for both existing and unknown addresses, because the limiter key is built from the submitted string, never from a member lookup.

Limiter design (mirrors `magic-link-request`, same club-Wi-Fi reasoning):
- `Limit::perMinutes(5, 3)->by('password-reset-request|'.emailKey.'|'.ip)`
- `Limit::perMinutes(5, 45)->by('password-reset-request-ip|'.ip)`

Tasks:
- [ ] Register `RateLimiter::for('password-reset-request', ...)` in `AppServiceProvider::boot` next to `password-reset-confirm`, and attach `throttle:password-reset-request` to the `password.email` route in `routes/web.php` — done when: `php artisan route:list --name=password.email -v` shows `ThrottleRequests:password-reset-request`, and a `route:list -v` diff taken before and after the change differs only on that route [AC 1]
  Context:
- [ ] Add tests to `tests/Feature/RateLimitingTest.php` (single task owns this file): 4th request for one email in 5 minutes is 429 (case variant) while another email still redirects; 46th request from one IP is 429 whatever the email; 429 for a member email and for an unknown email are both 429 with the same body, and the pre-throttle responses (redirect + same flash) are identical for member and unknown address with `Notification::fake()` asserting mail went only to the member; reset requests do not consume the `password-reset-confirm` or `magic-link-request` counters — done when: `php artisan test --compact tests/Feature/RateLimitingTest.php tests/Feature/PasswordResetTest.php` passes, and the new tests fail if the middleware is removed [AC 1,2]
  Context:
- [ ] Update `docs/user-journeys.md`: remove the "`POST /password/reset` (send link) is not throttled" line under "Found while fixing" and note the limit in the password-reset section; also drop the stale "There is no custom 429 page" line (`errors/429.blade.php` exists) — done when: `rg "not throttled|no custom 429" docs/user-journeys.md` finds nothing and the limit (3 per 5 minutes per email and IP, 45 per IP) is stated [AC 1]
  Context:

Unknowns:
- Laravel's password broker has its own 60-second per-user throttle (`config/auth.php` `throttle => 60`). It returns a status that `sendResetLink` ignores, so it does not change the visible response; the limiter is still needed because it also bounds unknown addresses and per-IP volume. Verified by reading the controller 2026-10-14.
- The per-email counter counts submitted strings, so an attacker rotating many distinct addresses is bounded only by the 45 per 5 minutes per-IP cap; `trustProxies('*')` lets a client rotate X-Forwarded-For past it. Out of scope, already recorded in docs/user-journeys.md.
- Response timing differs between member and unknown address (only members trigger a query and mail); not a visible response difference, so AC 2 holds.
- Numbers (3 and 45 per 5 minutes) copy the magic-link limiter; no evidence they are wrong for a ~10-member club.

---

## Open decisions

- none
