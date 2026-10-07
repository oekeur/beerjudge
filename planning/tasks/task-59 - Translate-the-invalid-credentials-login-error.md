---
id: TASK-59
title: Pick a guest's locale from Accept-Language
status: Done
assignee:
  - '@claude'
created_date: '2026-10-07 20:15'
updated_date: '2026-10-07 21:21'
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
- [x] #1 A failed password login from a browser sending Accept-Language: nl shows 'Ongeldige inloggegevens.', covered by a feature test
- [x] #2 A guest with an unsupported or missing Accept-Language gets the app default locale
- [x] #3 A logged-in member's profile locale still wins over Accept-Language
<!-- AC:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Decision (Oscar, 2026-10-07): guests get their locale from the Accept-Language header (en/nl only); logged-in members keep their profile locale.
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
SetLocale now gives guests the best Accept-Language match among the locales with a lang/*.json file, falling back to the configured app default; logged-in members keep their profile locale. The default is captured once because app()->setLocale() overwrites config('app.locale'). The magic-link email is pinned to the member's profile locale, so it no longer follows the requesting browser. Tests: tests/Feature/GuestLocaleTest.php. Gates: Pass A CONFIRMED a vacuous profile test and a cross-request leak; Pass B CONFIRMED the magic-link mail locale regression; all fixed in retries.
<!-- SECTION:FINAL_SUMMARY:END -->
