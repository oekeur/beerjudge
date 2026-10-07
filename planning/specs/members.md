# Members

Every user is a member. Members both brew (enter beers) and judge (score other people's entries). The group is small and closed: ~10 people, added by an admin.

## Data model

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | Display name |
| email | string | Unique, used for login |
| password | string | Hashed; nullable (not required for magic-link-only members) |
| is_admin | boolean | Grants Filament access and admin capabilities |
| is_active | boolean | Inactive members cannot log in |
| locale | enum(`en`, `nl`) | Default `en`; controls app language |
| timestamps | — | created_at, updated_at |

**Dropped from original PRD:** `bjcp_rank` (nullable BJCP certification rank). Removed — unused in scoring or assignment logic.

## Roles

There is one role flag: `is_admin`. All other members are regular members. There is no separate "judge" role — any active member can judge.

**Admin capabilities:** access to the Filament panel; create/edit/deactivate members; create and manage competitions; generate invitation links; manage entries and bottle numbers; view competition results at any status.

**Regular member capabilities:** submit entries for open competitions; access the judging screen for competitions in judging status; view their own results when a competition is closed.

## Locale preference

The `locale` column stores the member's preferred language (`en` or `nl`). The `SetLocale` middleware reads it on every authenticated request and sets the app locale. Admins can change a member's locale in Filament.

No translation strings exist yet — this is infrastructure for future localisation work.

## Admin management (Filament)

The MemberResource in Filament supports:
- Create member (name, email, password, locale, is_admin, is_active)
- Edit member (same fields; password change optional on edit)
- List members (searchable, sortable; inactive member count shown as a badge)
- Approve pending members, one at a time or in bulk; each approved member is emailed (see `authentication.md`, Invitation-based registration)

Members cannot self-edit their profile; all changes go through Filament.

## Out of scope

Member self-service profile editing. Password reset flow (magic link covers forgotten passwords). BJCP rank tracking. Photo uploads. Per-member competition history and stats (planned — see roadmap).
