---
id: TASK-59
title: Pick a guest's locale from Accept-Language
status: In Progress
assignee:
  - '@claude'
created_date: '2026-10-07 20:15'
updated_date: '2026-10-07 21:06'
labels:
  - i18n
dependencies: []
priority: low
type: bug
ordinal: 59000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
The Dutch string for __('Invalid credentials.') already exists (lang/nl.json:2). The real bug: SetLocale (app/Http/Middleware/SetLocale.php) sets the locale only from the logged-in member's locale, so every guest (login page, failed login, magic-link pages) gets APP_LOCALE=en. Found by the wave incompatibility check, 2026-10-07.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 A failed password login from a browser sending Accept-Language: nl shows 'Ongeldige inloggegevens.', covered by a feature test
- [ ] #2 A guest with an unsupported or missing Accept-Language gets the app default locale
- [ ] #3 A logged-in member's profile locale still wins over Accept-Language
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decision (Oscar, 2026-10-07): guests get their locale from the Accept-Language header (en/nl only); logged-in members keep their profile locale.
<!-- SECTION:NOTES:END -->
