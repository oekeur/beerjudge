# Plan: Password management for members

**Goal:** Members can change their password while logged in, and reset a forgotten password via an emailed link.
**Out of scope:** Admin password management (already covered by `member:password` artisan command), social/OAuth auth, enforced password policies.
**Constraints:** Must fit the existing custom auth flow (no Breeze/Fortify); both EN and NL translations required; views must match the existing amber/white design.

---

## Phase 1: Forgot password flow

**Outcome:** A logged-out member can enter their email, receive a reset link, click it, set a new password, and log in.

Tasks:
- [x] Add `CanResetPassword` interface + trait to `Member` model
- [x] Check whether `password_reset_tokens` table migration exists; create it if not
- [x] Add routes: `GET /password/reset` (request form), `POST /password/reset` (send email), `GET /password/reset/{token}` (reset form), `POST /password/reset/{token}` (apply new password)
- [x] Add controller methods to `AuthController` (or a new `PasswordResetController`) for all four routes
- [x] Create Blade view: `auth/password-reset-request.blade.php` (email input, amber/white design)
- [x] Create Blade view: `auth/password-reset.blade.php` (new password + confirm, token as hidden field)
- [x] Add a "Forgot password?" link to `auth/login.blade.php`
- [x] Add EN + NL translation strings for the new UI copy
- [x] Write feature tests: happy path (request → email → reset → login), invalid/expired token, inactive member blocked

Unknowns:
- [RISK] Does `CanResetPassword::sendPasswordResetNotification()` work with the custom `Member` model and the app's mail setup? The default notification targets `App\Models\User`; overriding it may be needed.
- Should inactive members (`is_active = false`) be allowed to reset their password? Likely no — clarify.
- The app has no existing `password_reset_tokens` migration. Laravel ships one in the default skeleton; verify it is absent before creating a new one.

---

## Phase 2: Change password (logged-in)

**Outcome:** An authenticated member can change their password from a settings page accessible from the main nav.

Tasks:
- [ ] Add routes: `GET /account/password` (form), `POST /account/password` (update)
- [ ] Add controller methods (new `AccountController` or added to an existing one)
- [ ] Create Blade view: `auth/password-change.blade.php` (current password, new password, confirm; amber/white)
- [ ] Add a "Change password" link in the existing home/nav area so members can reach it
- [ ] Add EN + NL translation strings
- [ ] Write feature tests: successful change, wrong current password, mismatched confirmation, unauthenticated redirect

Unknowns:
- Where exactly is the navigation link placed? The home screen (`HomeController@index`) likely needs a link; check what that view looks like.
- Should the page be reachable from the judging screen too, or only from the home/dashboard?

---

## Decisions made

- Inactive members: blocked from reset (silent no-op, same generic response as unknown email)
- Controllers: separate `PasswordResetController` + `AccountController`
- "Change password" link: member profile page (not home dashboard or judging screen)
