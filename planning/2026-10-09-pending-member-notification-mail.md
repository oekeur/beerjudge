# Plan: Pending-member notification email, and prod demo-member cleanup

**File:** planning/2026-10-09-pending-member-notification-mail.md
**Task:** TASK-79
**Goal:** The admin notification address (o.e.keur@gmail.com, set via `ADMIN_NOTIFICATION_EMAIL`) gets an email for every invite registration awaiting approval, and prod no longer holds the inactive DemoSeeder members.
**Out of scope:** notifying other admins; a DB flag or Filament UI for the notification address; a reusable prune command; deleting ACTIVE demo members (the runbook lists them, Oscar decides separately); deleting the demo competitions themselves; guarding DemoSeeder against production.
**Constraints:** prod is reachable only by Oscar (no git on the VPS; image pushed from the laptop to ghcr); the server's `.env.production` has drifted from the repo copy, so env changes go into the server file and need `up -d --force-recreate app`.

---

## Phase 1: Admin notification address and pending-registration notification

**Outcome:** with `ADMIN_NOTIFICATION_EMAIL` set, completing invite registration (`POST /join/{token}`) queues one mail to that address naming the new member and linking to the Filament Members list filtered on pending approval. Unset or blank: nothing is sent and registration behaves as today.

Decisions taken (Oscar, 2026-10-08): env var + config, not a members column; that one address only; the trigger is invite registration in `RegistrationController::store`, not a `Member::created` hook, so admins creating inactive members in Filament or via `member:create` do not trigger it.

Base branch: start from `main` after `improve/journey-walkthrough-fixes` has landed. That branch edits `lang/en.json`, `lang/nl.json` and `tests/Feature/InvitationTest.php`, which this task also edits.

Tasks:
- [ ] Add `'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL')` to `config/app.php` (blank string treated as unset) and document it in `.env.example` and `.env.docker.example`. Add a queued (`ShouldQueue`) mail notification, sent on demand (`Notification::route('mail', ...)`) from `RegistrationController::store` after the member is created. A failed dispatch is passed to `report()` and does not fail the registration. Mail text in EN and NL in `lang/*.json`. Locale: the locale of the member whose email equals the notification address if one exists, else the configured default captured by `SetLocale` (make its `defaultLocale()` reachable). Do not use `config('app.locale')` at send time: on guest routes `SetLocale` has already overwritten it with the registrant's Accept-Language. The link targets the Members index with `filters[pending_approval][isActive]=true` (Filament v4 binds table filters as `#[Url(as: 'filters')]`, see `vendor/filament/filament/src/Resources/Pages/ListRecords.php`). Update `planning/specs/authentication.md` (Invitation-based registration) and `planning/specs/members.md`. Done when these feature tests pass:
  (a) key set: `Notification::assertSentOnDemand` to that address; the rendered mail contains the new member's name and email and a URL containing `filters[pending_approval][isActive]=true` (URL-decoded);
  (b) a Livewire test loads `ListMembers` with that query string and asserts the inactive member is listed and an active member is not;
  (c) key unset and key `""`: nothing sent, member still created inactive;
  (d) invalid or used invitation: nothing sent;
  (e) admin creates an inactive member in Filament: nothing sent;
  (f) registrant sends `Accept-Language: nl`, no member matches the notification address, `APP_LOCALE=en`: the mail renders in English; a member with that email and locale `nl` gets Dutch;
  (g) the notification dispatch throws: registration still redirects to login with the success flash and the exception is reported.
  `php artisan test --compact` on the touched test files, PHPStan and Pint are clean.  [AC 1-6]
  Context:

Unknowns:
- Queued mail delivery on prod: `CompetitionResultsNotification` is queued and `docker/supervisord.conf` runs `queue:work` in the app container, but a delivered queued mail on prod is unverified. Sync magic-link mail was confirmed delivered on 2026-10-07. Phase 2's end-to-end check covers it.

---

## Phase 2: Deploy and switch on in prod (Oscar)

**Outcome:** prod runs Phase 1's code with `ADMIN_NOTIFICATION_EMAIL=o.e.keur@gmail.com`, and a real registration has produced a delivered email.

Tasks:
- [ ] Oscar: before building, check what else on `main` ships with this deploy (`git log` since the last deployed image; the entrypoint runs `migrate --force`). Build and push per `README.md` Deploy. Add `ADMIN_NOTIFICATION_EMAIL=o.e.keur@gmail.com` to the server's `.env.production`. Run `docker compose -f docker-compose.yaml -f docker-compose.prod.yaml pull app && docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d --force-recreate app`. Register once through a fresh single-use invite with a throwaway address. Done when: `php artisan config:show app.admin_notification_email` in the app container prints the address, the pending-approval mail arrives at o.e.keur@gmail.com, and the throwaway member is deleted with `php artisan member:delete <email>` (interactive: it prompts for the email, a choice and a confirmation, so run it with a TTY exec). [AC 7]
  Context:

Unknowns:
- Whether the server's `.env.production` sets a queue connection the worker consumes (the repo copy has drifted). If the mail does not arrive, check `failed_jobs` and the worker log first.

---

## Phase 3: Remove inactive demo members from prod (Oscar runs, runbook)

**Outcome:** no member with an email like `member%@beerjudge.local` and `is_active = false` exists on prod. Their entries (with every scoresheet on those entries) and the scoresheets they judged are gone; the demo competitions stay. Independent of Phases 1-2. The set may be empty or partial: DemoSeeder creates all 8 as active, so any inactive one was deactivated by hand.

Decisions taken (Oscar, 2026-10-08): one-off runbook, no new code; remove the members' references in the demo competitions, then the members; active demo accounts are not deleted.

Runbook. Compose prefix `dc` = `docker compose -f docker-compose.yaml -f docker-compose.prod.yaml`.
1. Back up, on the host: `dc exec -T postgres pg_dump -U <DB_USERNAME> <DB_DATABASE> > ~/backup-$(date +%F)-pre-demo-cleanup.sql`, with the values from the server's `.env.production`. The app image has no `pg_dump`; `-T` prevents a TTY from mangling the redirected dump. Check the file is non-empty.
2. List (read-only) in `dc exec app php artisan tinker`: every `member%@beerjudge.local` member with id, name, email, is_active, is_admin, created_at; per member, their entries and the scoresheets they judged grouped by `competition_id` with the competition name; and the count of scoresheets by other members on their entries. Oscar confirms the inactive set and that every referenced competition is a demo competition (`Autumn Harvest 2024`, `Spring Cup 2025`). If a reference sits in any other competition, stop and decide before step 3. Active demo members are reported, not touched.
3. Delete in one `DB::transaction` in tinker, for each confirmed inactive member: `Scoresheet::where('judge_id', $id)->delete()`, then `Entry::where('member_id', $id)->delete()` (scoresheets on those entries cascade), then `$member->delete()` (magic links and judge-pool rows cascade). `scoresheets.judge_id` and `entries.member_id` restrict deletes, so this order is required. No delete hooks exist on Member, Entry or Scoresheet.
4. Re-run step 2's member query: it returns only active demo members, if any.

Tasks:
- [ ] Oscar runs the runbook on prod. Done when step 4 returns no row with `is_active = false` and the step-1 dump file is non-empty. [AC 8]
  Context:

Unknowns:
- Real members' scoresheets on demo entries are deleted with those entries. Step 2 shows the count first.
- Removing entries from a closed demo competition changes its standings. Acceptable for demo data.
- `invitations.created_by` and `sessions.user_id` have no FK, so rows pointing at a deleted member keep a dangling id. Harmless.

---

## Open decisions

- [ ] What to do with any ACTIVE demo members the listing shows (deactivate, delete or keep). DemoSeeder creates member1 and member2 as active admins with password `password`.
