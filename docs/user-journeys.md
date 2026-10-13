# User journeys

The journeys an admin and a member take through BeerJudge, end to end. Each one lists who does it, what has to be true first, the steps, what should happen, where it lives in the code, and which automated tests cover it.

The deterministic suites (`php artisan test`, `npm run test:browser`) check each rule in isolation. These journeys chain the rules into the order a club actually uses them: an admin opens a competition, members enter it, judges score it, the admin closes it and everyone reads their results. A walkthrough of all of them in a real browser is a second check on top of those suites; the latest run is logged at the bottom of this page.

## Roles

- **Member**: every club member brews and judges. Logs in to the member site (`/`), enters beers, scores other members' beers, reads results.
- **Admin**: a member with `is_admin`. Uses the Filament panel at `/admin` for members, competitions, styles and entries, and the member site for judging and results like anyone else.
- **Guest**: someone with an invite link and no account yet.

## Admin journeys

### A1. Onboard a new member

- **Actor**: admin, then guest.
- **Preconditions**: none.
- **Steps**:
  1. Admin opens **Members** and clicks **Generate invite link**: single-use, or time-bound with an expiry date. The link is copied and shown in a notification; active time-bound links are listed on the Members page.
  2. Guest opens `/join/{token}` and enters name, email and password.
  3. Guest is sent to the login page. The account exists but is inactive, so logging in is refused.
  4. Admin approves the member, per row (**Approve**) or in bulk (**Approve selected**). The member gets an activation email.
  5. Member logs in and lands on `/`.
- **Expected**: a used single-use link answers 410; a time-bound link accepts several registrations until it expires.
- **Code**: `RegistrationController`, `Resources/Members/Pages/ListMembers.php`, `Resources/Members/Tables/MembersTable.php`, `MemberActivatedNotification`.
- **Docs**: [How to register](/member/registration-howto).
- **Tests**: `InvitationTest`, `MemberActivationEmailTest`.

### A2. Set up a competition

- **Actor**: admin.
- **Steps**:
  1. **Competitions** -> **New competition**: name, year, optional judging date. It is created as Draft.
  2. **Styles** tab: search and tick the accepted styles, then save.
  3. **Entries** tab: copy the member entry link (`/competitions/{id}/entries/create`) to share with the club.
  4. **Move to Open**.
- **Expected**: the competition shows as Open in the list and on members' home page.
- **Code**: `Resources/Competitions/Schemas/CompetitionForm.php`, `Pages/CreateCompetition.php`, `Pages/EditCompetition.php`.
- **Docs**: [How to run a competition](/competition/lifecycle-howto).
- **Tests**: `CompetitionLifecycleTest`, `CompetitionStatusGuardTest`.

### A3. Manage entries while registration is open

- **Actor**: admin.
- **Steps**:
  1. **Entries** tab lists every entry in a repeater.
  2. Add an entry on behalf of a member (member, style, ABV, notes) and save.
  3. **Sort by ABV** to set the tasting order from light to strong.
- **Expected**: `sort_order` follows ABV ascending; entries without ABV go last.
- **Code**: `CompetitionForm.php` (repeater and `sortByAbv` action).
- **Tests**: `EntrySortOrderTest`, `EntryStyleValidationTest`.

### A4. Start judging

- **Actor**: admin.
- **Steps**:
  1. **Move to Judging** and confirm the "Lock Registration" dialog. Bottle numbers 1..n are assigned.
  2. **Dashboard** tab: pick judges, or copy the judge-join link (`/judging/{id}?register=1`) for members to sign themselves up.
  3. Follow progress on the Dashboard (entries, judges, sheets, per-entry missing judges) and on the judging screen's admin progress panel.
- **Expected**: the member entry page answers 403 from now on.
- **Code**: `EditCompetition.php`, `BottleNumberAssignmentService`, `Widgets/ResultsSummaryWidget.php`, `JudgingController@progress`.
- **Docs**: [Bottle assignment reference](/competition/bottle-assignment-reference).
- **Tests**: `CompetitionJudgeAssignmentTest`, `JudgeParticipationLinkTest`.

### A5. Close the competition and publish results

- **Actor**: admin.
- **Steps**:
  1. **Results & Variance** tab: entries whose judge spread exceeds 7 points are flagged.
  2. **Move to Closed** and confirm.
  3. Every active member with an entry gets one results email.
  4. **View Results** opens the results index, which for admins also lists participants and judges.
  5. **Resend results email** sends the email again to one chosen member.
- **Expected**: `results_mailed_at` is set on the first close.
- **Code**: `Competition::booted()`, `CompetitionResultsNotification`, `Widgets/JudgeVarianceWidget.php`, `ResultsController`.
- **Docs**: [How to view results](/competition/results-howto).
- **Tests**: `CompetitionResultsEmailTest`, `JudgeVarianceWidgetTest`, `ResultsAccessTest`.

### A6. Reopen judging and close again

- **Actor**: admin.
- **Steps**:
  1. On a closed competition, **Reopen judging** and confirm. Status goes back to Judging; bottle numbers stay.
  2. Judges correct their sheets.
  3. **Move to Closed** again.
- **Expected**: members cannot see results while it is reopened (403). Closing again sends no second results email.
- **Code**: `EditCompetition.php` (`reopenJudging`).
- **Tests**: `CompetitionStatusGuardTest`, `CompetitionResultsEmailTest`.

### A7. Add a custom style

- **Actor**: admin.
- **Steps**: **Styles** -> **New style**, fill code and name, create.
- **Expected**: the style has source `custom` and can be found in the style list.
- **Code**: `Resources/Styles/`.

### A8. Admin guards

- **Actor**: admin.
- **Expected**:
  - The last active admin cannot be demoted, deactivated or deleted.
  - An inactive admin cannot reach `/admin`.
- **Code**: `Schemas/MemberForm.php`, `Pages/EditMember.php`, `RequireActiveMember`, `Member::canAccessPanel()`.
- **Tests**: `LastActiveAdminGuardTest`, `AdminPanelAccessTest`.

## Member journeys

### M1. Sign in

- **Actor**: member.
- **Steps**:
  - **Password**: email and password at `/login`. Members land on `/`, admins on `/admin`.
  - **Magic link**: request a link at `/magic-link`, open it from the email, press the button on the confirm page. Opening the link alone does not log in; a used link shows "expired or already used".
  - **Password reset**: request a reset email, set a new password, which logs you in.
  - **Log out** from the nav.
- **Code**: `AuthController`, `PasswordResetController`.
- **Docs**: [How to log in](/member/auth-howto), [Magic link reference](/member/magic-link-reference).
- **Tests**: `AuthTest`, `MagicLinkTest`, `PasswordResetTest`, `RateLimitingTest`, `tests/Browser/auth.spec.ts`.

### M2. Edit your profile

- **Actor**: member.
- **Steps**: open **Profile**, change name or language (English or Dutch), save. Optionally change your password; that needs the current one.
- **Expected**: the whole member site switches language.
- **Code**: `ProfileController`.
- **Tests**: `ProfileControllerTest`, `GuestLocaleTest`.

### M3. Enter a beer

- **Actor**: member.
- **Preconditions**: the competition is Open.
- **Steps**: on the home page, follow the competition card's entry link. Pick a style from the competition's styles, add ABV and notes, submit. The entry appears under "Your entries".
- **Expected**: a second entry in the same style is refused. The entry page answers 403 once the competition is no longer Open.
- **Code**: `EntryController`, `StoreEntryRequest`.
- **Tests**: `EntryFormTest`, `EntryStyleValidationTest`, `tests/Browser/entry-submission.spec.ts`.

### M4. Judge

- **Actor**: member.
- **Preconditions**: the competition is in Judging.
- **Steps**:
  1. Join as a judge from the judge-join link or the banner on `/judging/{id}`; leave the same way.
  2. The list holds every entry except your own, in tasting order. **My ranking** sorts by your own scores.
  3. Expand an entry, score the five sections, add notes and off-flavour descriptors. The info buttons open the style guideline (BJCP or Brewers Association) for that section.
  4. **Submit**. A submitted sheet can be reopened and saved again until the competition closes.
  5. Without a connection, sheets are kept in IndexedDB and sent when the connection returns.
- **Code**: `JudgingController`, `ScoresheetController`, `resources/views/judging/show.blade.php`, `resources/js/judging.js`.
- **Docs**: [How to judge](/judging/howto-judge), [Offline sync](/judging/explanation-offline-sync).
- **Tests**: `JudgingScreenTest`, `ScoresheetUpsertTest`, `ScoresheetValidationTest`, `tests/Browser/judging-*.spec.ts`, `tests/Browser/judge-registration.spec.ts`.

### M5. Read your results

- **Actor**: member.
- **Preconditions**: the competition is Closed.
- **Steps**:
  1. The home page card links to **my results**: your entries with each judge's scores and notes, rank, band and the full standings. **Print** gives a print layout.
  2. If you judged, the results index links to your judge report, which compares your scores with the other judges'.
- **Expected**: another member's sheet answers 403; another judge's report answers 403 or 404; members see nothing before the competition closes.
- **Code**: `ResultsController`, `JudgeReportController`.
- **Tests**: `ResultsAccessTest`, `JudgeReportAccessTest`.

## Where the code and the planning docs disagree

Open observations, not decided:

- The PRD says members can edit or withdraw an entry while the competition is Open. There is no route for either; only an admin can change entries.
- The PRD and `planning/specs/judging.md` describe blind judging by bottle number. The judging screen shows the brewer's name and a tasting number.
- The README and `planning/specs/competitions.md` say Closed locks everything. **Reopen judging** now moves a closed competition back to Judging.
- The README gives port 8080 for the full Docker stack; `docker-compose.override.yaml` maps the app to 8000.

## Walkthrough log

### 2026-10-13

Pre-launch run on main (`2f3483e`) before the first club evening. `scripts/journeys/run-all.sh`: 100 of 100 steps passed. On top of that, unhappy paths were probed with Playwright scripts against a separate seeded SQLite server at 390 px, and two code reviews (judging evening; sign-in and entries) probed the rest with PHPUnit. Each finding says how it was confirmed.

**Fixed the same day**

1. **Tasting numbers differed per judge.** The list was numbered after removing the judge's own entries, so Munich Helles was #4 for one judge and #5 for another. Own entries now stay in the list as locked rows and the numbers come from the full tasting order. Browser probe. PR #56.
2. **Magic-link limits were per IP.** On one shared IP, six link requests gave `302,302,302,429,429,429`, and the third member opening a link within a minute got a 429. The limiters now key on email or link plus IP, with per-IP caps sized for about 15 members. PHPUnit probe. PR #57.
3. **403 and 429 were Laravel's bare English pages.** A judge reloading after close saw "403 Forbidden" with no way back. Both now have a translated page in the standalone error-view style, with a link home. Checked in a browser at 390 px. PR #60.
4. **Email addresses were case-sensitive.** An account registered as `Jan@Example.com` could not log in as `jan@example.com`, a magic link for the lowercase address silently sent nothing, and a second account with the lowercase address was accepted. Emails are now stored lowercased, every lookup lowercases its input, and a migration lowercases stored addresses (skipping case-only duplicates). PR #62.
5. **Notes over 2000 characters retried on 422 forever.** The notes fields now stop at 2000 characters. PR #62.
6. **A double tap on "Submit entry" could save twice or answer 500.** The button disables itself on submit, and a request that still loses the race reports success. PR #62.
7. **Opening an invite link while logged in landed on `/` without a word.** It now explains the link is not needed and how to register someone else. PR #62.

**Decided not a problem**

- A shared phone posts one judge's unsent sheet as the next judge's score: IndexedDB rows carry no judge id and survive logout. Browser probe: member 5's offline sheet was stored as member 1's after member 1 logged in on the same browser. Oscar, 2026-10-13: "people use their own device".
- If outbound mail fails, `POST /magic-link` and `POST /password/reset` answer 500 and bulk approve stops after the first failure (PHPUnit with a dead SMTP port). Oscar, 2026-10-13: production mail works; he logs in with magic links.

**Open, not filed**

- Closing a competition while a judge has an unsent sheet: the POST gets 422 and the sheet shows "Retry" forever; reloading the judging page answers 403. Reopen judging recovers it (the queued sheet keeps its uuid). Browser probe. Related to TASK-84.
- Entry form: typing a style without tapping a suggestion submits an empty `style_id` and shows "The style id field is required."; the typed text is gone. Browser probe.
- A member waiting for approval is told "Your account is not active.", in English (registration stores locale `en`, TASK-87).
- Not probed, read from code: a CSRF token made stale by logging out in another tab makes saves retry on 419 until a reload.

**Checked and fine**: a session wiped mid-judging (remember-me logs the judge back in and the next save answers 200), password login for 10 members on one IP, two tabs of one judge (Web Locks), out-of-range scores (buttons only), the ABV decimal comma on a phone keyboard.

**Not covered**: real iOS and Android devices, the installed PWA, Postgres behind the production proxy.

### 2026-10-09

Re-run of all thirteen journeys with `JOURNEY_PORT=8022 scripts/journeys/run-all.sh` on the integration branch after the fixes for findings 1 to 10 landed, on a fresh SQLite database seeded as on 2026-10-08 (headless Chromium, Playwright 1.61, `npm run build` first).

Result: 100 of 100 steps passed, no `FAIL` or `FATAL` line, every journey printed its `===` header and at least one `PASS`. The 2026-10-08 run had 101 steps; the net difference of one step comes from journey changes made while fixing: A2 asserts the header after Move to Open as one step without a reload, M4 dropped the offline-reload step (the page is still not available offline, and is not claimed to be), and A3's Sort by ABV probe was reworked. The `results.json` files hold 106 entries: the 100 steps plus 6 console notes, all 4xx responses to requests the journeys make on purpose (the 410 for a used invite in A1, and 403 for pages a member must not reach in A6, M3, M4 and M5). There were no page errors, no 5xx and no failed requests. Screenshots reviewed by hand: A1 register through invite, A4 dashboard, M4 offline step, A3 after Sort by ABV, the admin competition list and M3 at phone width.

**Findings 1 to 10 fixed**

1. No feedback after registering through an invite: the login page shows the pending-approval message. TASK-70.
2. Dashboard and Variance widgets did not refresh: the widgets poll again. TASK-71.
3. Header actions stayed on the previous status: the header is rebuilt in place after a status change. TASK-63.
4. Sort by ABV dropped unsaved rows: it reorders the form rows and saves nothing. TASK-72.
5. Login throttle shared per IP: per-route limiters, login keyed by email plus IP with a per-IP cap. TASK-62, TASK-73.
6. Offline judging looked like an error: the queued sheet shows "Wacht op verbinding" instead of Retry. The save pipeline was hardened with it: one save chain per entry with abort, a server revision guard, a notice when the server copy is adopted, and one active tab. TASK-74.
7. Admin panel loaded Sentry from the CDN: the SDK is self-hosted. TASK-75.
8. Year column showed "2,024": the year has no thousands separator. TASK-76.
9. Labels and translations: entry repeater labels, invite type label, bug-report button and scoresheet accessible labels. TASK-77.
10. Phone-width nav wrapped onto two lines: fixed. TASK-78.

Finding 11 (the style guide panel is not a dialog for screen readers) is still not filed.

**Found while fixing** (not fixed in this run, not filed as tasks yet)

- Any Save on the competition edit page deletes entries registered after the page was opened: the relationship repeater removes rows missing from its stale form state.
- `POST /password/reset` (send link) is not throttled.
- `trustProxies('*')` lets a client that rotates `X-Forwarded-For` escape IP-keyed limits; it depends on the proxy in front.
- There is no custom 429 page.
- Invite registration hardcodes the locale `en`.
- The entries repeater has no status lock during Judging or Closed.
- A 4xx on a scoresheet save (419 or 422) is retried every 5 seconds forever.
- DemoSeeder's closed competition has no `results_mailed_at`, so reopening and closing it on a demo database mails participants.
- Bottle numbers follow entry id, not tasting order.

**Not covered by this run**: unchanged from 2026-10-08.

### 2026-10-08

Run with `scripts/journeys/run-all.sh` (see its README). All journeys run in headless Chromium (Playwright 1.61) against a local server on a fresh SQLite database seeded with the BJCP styles and `DemoSeeder`. Video and a screenshot per step were recorded; every screenshot was reviewed by hand. The admin builds a new competition (A2) that members enter (M3), judge (M4) and read results for (M5), so the run covers the whole chain on new data as well as the seeded competitions. Members 3 and 4 used the Dutch interface; member 6 entered and member 3 judged at phone width (390 px).

Result of the final run: 99 of 101 steps passed. The two failures are findings 1 and 2.

**Findings**

1. **No feedback after registering through an invite.** `RegistrationController` flashes `auth.register_success` ("Account created. An admin will approve it...") under `success`, but `auth/login.blade.php` never renders `session('success')`. The new member sees an empty login form. Seen in 3 of 3 runs. TASK-70, fixed 2026-10-09.
2. **The competition Dashboard and Variance widgets do not refresh.** Both widgets set `$pollingInterval = '5s'`, but on the edit page there is no `wire:poll` in the DOM and no Livewire request in 16 seconds idle. After saving two judges the Judges tile stays at 0 until the page is reloaded. Seen in 3 of 3 runs. TASK-71, fixed 2026-10-09.
3. **Header actions stay on the previous status** after **Move to Open** until a reload. Tracked as TASK-63, fixed 2026-10-09.
4. **Sort by ABV drops unsaved entry rows.** The action redirects to the edit page, which lands on the Overview tab and discards the form state. In one probe, an added but unsaved row was not in the database after sorting. TASK-72, fixed 2026-10-09.
5. **Login throttle is per IP.** `POST /login` uses `throttle:6,1`, which keys guests by IP; the seventh login from one IP within a minute got a 429 page. On a club evening where everyone shares one Wi-Fi connection, members would share that limit too (expected, not observed in production). TASK-73; TASK-62 covers the related shared counter across auth routes. Both fixed 2026-10-09.
6. **Offline judging looks like an error.** A sheet submitted without a connection shows a red "Retry" ("Opnieuw") badge, although it is queued and is sent within 5 seconds of the connection returning, without a reload (1 of 1 probes). Reloading the judging page while offline fails with `ERR_INTERNET_DISCONNECTED`, because the service worker caches assets but not the page. TASK-74, fixed 2026-10-09.
7. **The admin panel loads Sentry from `browser.sentry-cdn.com`** (`AdminPanelProvider.php`). The request was aborted on every admin page load in the run. The error page bundles Sentry locally because ad blockers block that domain. TASK-75, fixed 2026-10-09.
8. **The Year column shows "2,024"** in the competition list: the year is formatted with a thousands separator. TASK-76, fixed 2026-10-09.
9. **Labels and translations**: the entries repeater labels read "Style id" and "Abv"; the invite type radio is labelled "Single-use", the same as its first option; the "Report a Bug" button and the scoresheet's accessible labels stay English in the Dutch interface; the login page title is a fixed "Log in". TASK-77, fixed 2026-10-09.
10. **Phone width**: at 390 px the member nav wraps the app name and "Log out" onto two lines. TASK-78, fixed 2026-10-09.
11. **The style guide panel** on the judging screen is not exposed as a dialog to screen readers. Not filed.

**Not covered by this run**: dark mode, installing the PWA, the contents of the admin progress panel on the judging screen, and email rendering in a real mail client (mail went to the log).
