# Plan: Store the guest's browser locale on invite registration

**File:** planning/2026-10-14-invite-registration-locale.md
**Goal:** A guest who registers through an invite link with a Dutch browser becomes a member with locale `nl`, so the pending-approval message and the activation email reach them in Dutch.
**Out of scope:**
- The admin notification locale in `RegistrationController::notifyAdminOfPendingMember`: it deliberately follows the notification address owner's locale, never the registrant's.
- Backfilling members already stored as `en` (the DB is not touched; members change their locale on the profile page).
- Any new Accept-Language parsing: `SetLocale` already resolves it for guests.
- A locale picker on the registration form.
**Constraints:** Use `app()->getLocale()` in the controller. Depends on TASK-109 (reworded `auth.inactive_account`, translated register notice) landing first; this plan assumes it did. No new translation keys, so the en/nl key-parity test (TASK-107) is unaffected.

---

## Phase 1: Persist the resolved guest locale

**Outcome:** `POST /join/{token}` stores `app()->getLocale()` as the new member's locale. A Dutch guest's pending-approval message and activation email are Dutch.

Why this works (verified by reading the code, 2026-10-14):
- `SetLocale` is in the `web` group (bootstrap/app.php:19) and sets `app()->getLocale()` from Accept-Language for guests, falling back to the configured default.
- `Member::booted` already sends `MemberActivatedNotification` with `->locale($member->locale)`, so the activation email follows the stored locale with no further change.
- A pending member who logs in is authenticated, then bounced by `RequireActiveMember`; `SetLocale` has already set the locale from `$member->locale` by then (the global web middleware sees the session user), so the flashed `auth.inactive_account` text is in the stored locale, not the login request's header. With `'en'` hard-coded, that message was English for a Dutch registrant.

Tasks:
- [ ] TASK-87 — In `RegistrationController::store`, replace `'locale' => 'en'` with `'locale' => app()->getLocale()`. Leave line ~76 (`$owner?->locale ?: SetLocale::defaultLocale()`) unchanged. Update the docblock/comments only if they now say "en". Add tests to `tests/Feature/InvitationTest.php` (one task owns that file):
  1. Register with `Accept-Language: nl` -> `Member` row has `locale === 'nl'` [AC 1].
  2. Regression guard (passes with or without the fix, since the default is `en` in tests; do not change `config('app.locale')` in it, `SetLocale::$defaultLocale` is memoised per process): register with no header, and with `fr`, -> `locale === SetLocale::defaultLocale()` [AC 1].
  3. Activation email: register with `Accept-Language: nl`, `Notification::fake()`, then `$member->update(['is_active' => true])` -> `MemberActivatedNotification` sent with `->locale === 'nl'` via `assertSentTo` with a closure. Do not also assert a rendered subject: the app locale stays `nl` after the registration request, so it would pass whatever is stored [AC 2].
  4. Pending-approval path (TASK-109): register with `Accept-Language: nl`, then `POST /login` with the new credentials and NO Accept-Language header, `followingRedirects()`, assert the response contains the nl text of the key `RequireActiveMember` flashes for a pending member (read the middleware at execution time; today `auth.inactive_account`, and TASK-109 may reword it or split a new key; resolve with `__($key, [], 'nl')`). This is new and sits beside, not in place of, any test TASK-109 adds with a factory member. The header-less login is what separates the stored locale from the request locale. Assert `$this->assertGuest()` afterwards [AC 1,2].
  5. Admin notification locale unchanged: the existing `PendingMemberNotificationTest` assertions must still pass, unmodified; run it.
  Also update `docs/user-journeys.md`: line 197 (drop "in English (registration stores locale `en`, TASK-87)" so it reflects the fix and TASK-109) and line 240 ("Invite registration hardcodes the locale `en`." removed), and the language-gaps bullet's "every new member is stored as `en` (TASK-87)" clause.
  Done when: `php artisan test --compact tests/Feature/InvitationTest.php tests/Feature/PendingMemberNotificationTest.php tests/Feature/MemberActivationEmailTest.php` passes, tests 1, 3 and 4 fail when the controller line is temporarily reverted to `'en'` (edit that one line and restore it; do not `git stash`), `vendor/bin/pint --dirty --format agent` is clean, `./vendor/bin/phpstan analyse` is clean, and `rg "hardcodes the locale|stores locale .en.|stored as .en." docs/` finds nothing. [AC 1,2]
  Context: Verified 2026-10-14: `AuthController::login` has no is_active check; the inactive bounce happens in `RequireActiveMember` (app/Http/Middleware), after `SetLocale` has run with the session user. Existing Dutch-header registration test is `InvitationTest::test_login_page_shows_pending_approval_message_in_dutch_for_dutch_browser`; it keeps passing.

Unknowns:
- Assumption on TASK-109: `auth.inactive_account` exists in both en and nl (it does today: "Je account is niet actief."); test 4 reads the key by name so it survives the rewording.
- `POST /join/{token}` sits in the `guest` middleware group (routes/web.php), so `store` only runs for guests and `app()->getLocale()` is always the Accept-Language match (verified by the red-team).
- Behaviour change: Oscar deferred Dutch gaps on 2026-10-13 ("our group is mostly English, so it's fine for now"), but TASK-87 itself, filed by Oscar, asks for browser-locale registration. Dutch-browser registrants will now get Dutch app pages and mail throughout; that is the task's intent.
- TASK-109's key choice is not yet fixed (reword `auth.inactive_account` vs. a new key); test 4 resolves the key at execution time instead of hard-coding it.
- `app()->getLocale()` returns whatever `SetLocale` resolved, limited to locales with a `lang/*.json` file, so the column never receives an unsupported locale.
- Overlap: TASK-109 also edits `docs/user-journeys.md` line 197 and `lang/*.json`; this plan edits the same doc line. Run order (109 then 87) makes it sequential; if 109 already rewrote the line, edit its new text.

---

## Open decisions

None.
