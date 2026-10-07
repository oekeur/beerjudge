---
id: TASK-52
title: Translate the inactive-account login error
status: Done
assignee:
  - '@agent'
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 20:01'
labels:
  - i18n
dependencies: []
priority: low
type: bug
ordinal: 52000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
RequireActiveMember.php:28 uses __('Your account is not active.'), which has no key in lang/en.json or nl.json, so Dutch members see English.
<!-- SECTION:DESCRIPTION:END -->

## Implementation Notes

<!-- SECTION:NOTES:BEGIN -->
Plan: planning/2026-10-07-launch-email-bugfix-batch.md (Phase 3: Small fixes)
<!-- SECTION:NOTES:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
RequireActiveMember now flashes __('auth.inactive_account') (en 'Your account is not active.', nl 'Je account is niet actief.'); translated at redirect time because SetLocale runs first in the web group. docs/member/active-member-middleware-reference.md:69 updated. Verified: MiddlewareTest test_inactive_member_sees_translated_error_on_login_page (fails at merge base), Pass B probes (en locale, empty locale fallback, magic-link path in nl, lang JSON valid with no duplicate keys), full suite green. Reported, not fixed: AuthController.php:33 __('Invalid credentials.') has no key; inactive admins keep Filament access (canAccessPanel checks is_admin only, RequireActiveMember not on the panel).
<!-- SECTION:FINAL_SUMMARY:END -->
