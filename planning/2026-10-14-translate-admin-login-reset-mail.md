# Plan: Translate the admin login page and the password-reset email

**File:** planning/2026-10-14-translate-admin-login-reset-mail.md
**Goal:** /admin/login renders in the guest's Accept-Language locale, and the password-reset email renders in the member's profile locale, mail chrome included.
**Out of scope:** Filament's own password-reset flow (the panel only enables `->login()`; the app's `/password/reset` routes stay the only flow). Rewording or restructuring `PasswordResetController` (TASK-85 owns the rate limit). Setting a new member's locale at registration (TASK-87). Translating the other four notifications' own lines (they already use `auth.*` keys); they gain the translated mail chrome as a side effect, which is intended. A Member-wide `HasLocalePreference` (it would change who gets which locale for mails that today use an explicit `->locale()`).
**Constraints:**
- Every new key goes into BOTH `lang/en.json` and `lang/nl.json` in the same commit (TASK-107's key-parity test). Stock-framework English strings used as keys go into `en.json` too.
- Do not parse Accept-Language anywhere; `SetLocale` already does.
- Reset mail uses `->locale($member->locale ?: SetLocale::defaultLocale())`, never the page locale.
- The visible response of `sendResetLink` must not branch on whether the member exists (TASK-85's throttled response stays identical for unknown addresses).
- Assumes TASK-85 and TASK-109 have landed. This plan touches neither `PasswordResetController` nor `auth.inactive_account`. Also assumes TASK-107 has landed: Phase 2's done-when cites its lang key-parity test, which does not exist in the repo yet (conventions say it lands first; if it has not, diff the key sets of both files by hand). Overlap: TASK-107/108/109 and TASK-87 also write `lang/en.json` and `lang/nl.json`; this plan only appends keys, so rebase conflicts are textual.

---

## Findings that shape the design (verified 2026-10-14)

- `bootstrap/app.php` adds `SetLocale` to the `web` group only. Filament panel routes use the panel's own `middleware()` and `authMiddleware()` lists, not the web group. `AdminPanelProvider` lists `SetLocale` only in `authMiddleware([...], isPersistent: true)`, which runs after `Authenticate`; the guest login page never gets it. Filament ships `nl` strings for the login page (`vendor/filament/filament/resources/lang/nl/auth/pages/login.php`, heading "Inloggen op je account"), so setting the locale is enough; no custom login view exists.
- `Panel::middleware()` appends to the list on each call and accepts `isPersistent`. Panel `middleware()` entries wrap `authMiddleware()` entries, so `SetLocale` there runs on every panel page load. `SetLocale` reads `$request->user()`, which needs the session started: it must sit after `StartSession` (and after `AuthenticateSession`, which can log a stale session out).
- Authenticated members: `SetLocale::handle` already does `$user->locale ?: defaultLocale()` for a `Member`, guests get Accept-Language. Registering it once in the panel's `middleware()` therefore gives members their profile locale and guests their browser locale with one class and no new logic. The existing `authMiddleware` entry stays: it is persistent, so it also covers Livewire update requests that replay the auth stack; removing it is an unverified change with no benefit here.
- `Member` uses Laravel's `CanResetPassword` trait; `Password::broker()->sendResetLink()` calls `$member->sendPasswordResetNotification($token)`, which sends the stock `Illuminate\Auth\Notifications\ResetPassword` without a locale. `Member` does not implement `HasLocalePreference`. The notification therefore renders in the request locale (the guest's Accept-Language), not the profile locale. Other notifications set `->locale($member->locale)` explicitly.
- The stock `ResetPassword` mail calls `Lang::get(...)` for five strings (subject "Reset your password", intro "You are receiving this email because we received a password reset request for your account.", action "Reset Password", expiry "This password reset link will expire in :count minutes.", outro "If you did not request a password reset, no further action is required."). The Markdown mail template adds `Hello!`, `Regards,` and a two-line subcopy ("If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:"). None of these is in `lang/nl.json`. The `Hello!`/`Regards,`/subcopy keys also affect the four existing notifications, which today render Dutch lines inside English chrome.
- Decision: translate by key (the stock strings as JSON keys), not by a custom `ResetPassword` subclass or `toMailUsing`. It reuses the framework notification, keeps `Notification::assertSentTo($member, ResetPassword::class)` in `PasswordResetTest` valid, and fixes the chrome for every notification. Informal register ("je") matches the existing `nl.json`.

---

## Phase 1: Admin login follows the browser locale

**Outcome:** `/admin/login` renders Dutch for `Accept-Language: nl`, English otherwise; signed-in members on admin pages keep their profile locale.

Tasks:
- [ ] Add a second `->middleware([SetLocale::class])` call in `AdminPanelProvider::panel()`, directly after the existing `->middleware([...])` list, so it runs after `StartSession` and `AuthenticateSession`. Leave the `authMiddleware` entry in place. In `tests/Feature/GuestLocaleTest.php` add: (a) a guest GET `/admin/login` with `Accept-Language: nl` asserts the Dutch Filament heading `Inloggen op je account` and `app()->getLocale() === 'nl'`; (b) the same request without the header asserts `app()->getLocale() === 'en'` and `assertDontSee('Inloggen op je account')` (the English heading is just `Sign in`, which also labels the button, so asserting it is weak); (c) a failed login submit through `/livewire/update` on `/admin/login` with `Accept-Language: nl` returns the Dutch error `Onjuiste inloggegevens.` (copy the snapshot-POST pattern of `AdminPanelAccessTest::test_deactivated_admin_cannot_save_through_livewire_update`); (d) a member with `locale = 'nl'` GETting an admin page with `Accept-Language: en` gets `nl` (profile wins; use an admin member and `/admin`), and a member with `locale = 'en'` and `Accept-Language: nl` gets `en`. — done when: the tests below pass, and reverting the `AdminPanelProvider` change makes (a) fail. [AC 1]
  Context: verified 2026-10-14: panel middleware() and authMiddleware() are separate lists (vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php); `GuestLocaleTest` already uses `withHeaders(['Accept-Language' => ...])`.

Unknowns:
- Livewire login submits run the web group (SetLocale included); the red-team probe confirmed locale `nl` and the Dutch error over HTTP, now covered by test (c). A visual browser check is still manual.

---

## Phase 2: Reset email follows the profile locale

**Outcome:** the reset email for a member with `locale = 'nl'` is fully Dutch, including greeting, sign-off and the subcopy under the button, whatever Accept-Language the requester sent; a member with `locale = 'en'` still gets English.

Tasks:
- [ ] Override `sendPasswordResetNotification($token): void` (keep `$token` untyped: the `CanResetPassword` contract declares it untyped and a `string` type is a fatal error; carry the type in a docblock) in `app/Models/Member.php` to `$this->notify((new ResetPassword($token))->locale($this->locale ?: SetLocale::defaultLocale()))`. Add these nine keys to BOTH `lang/en.json` (identical English text) and `lang/nl.json` (Dutch, informal): `Reset your password`, `You are receiving this email because we received a password reset request for your account.`, `Reset Password`, `This password reset link will expire in :count minutes.`, `If you did not request a password reset, no further action is required.`, `Hello!`, `Regards,`, `All rights reserved.` (layout footer from vendor/laravel/framework/src/Illuminate/Mail/resources/views/{html,text}/message.blade.php; no published mail views exist), and the subcopy key, which is the framework's string with a literal newline (`If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:` as JSON). Extend `tests/Feature/PasswordResetTest.php`: (a) with a real mail transport capture (`ArrayTransport`, as `GuestLocaleTest` does), a `nl` member requesting a reset with `Accept-Language: en` yields a message whose subject is the Dutch string and whose body contains the Dutch `Hello!`, `Regards,` and subcopy translations and none of the English source strings; (b) an `en` member requesting with `Accept-Language: nl` gets English; (c) the response redirect and `success` flash are identical for an existing and an unknown address under `Accept-Language: nl` (regression for the no-branching constraint); (d) existing `Notification::assertSentTo($member, ResetPassword::class)` tests stay green. — done when: tests (a)-(d) pass, `lang/en.json` and `lang/nl.json` hold the same nine new keys (TASK-107's parity test passes), and `php artisan test --filter=PasswordResetTest` is green. [AC 2]
  Context: verified 2026-10-14: stock strings are in vendor/laravel/framework/src/Illuminate/Auth/Notifications/ResetPassword.php and Notifications/resources/views/email.blade.php; the subcopy key contains a literal newline.

Unknowns:
- [RISK] The subcopy key must match the framework's string byte for byte or Laravel silently falls back to English. Resolved by test (a): it asserts the Dutch subcopy text appears in the rendered body, so a mismatch fails the test. No deferral needed.
- Mail chrome keys also change the four existing Dutch notifications' appearance. Intended; existing tests asserting English chrome (grep `Hello!` and `Regards` in tests/) must be checked at execution.

---

## Open decisions

- None. Chosen: translate by JSON key on the stock `ResetPassword` rather than subclassing (reason above).
