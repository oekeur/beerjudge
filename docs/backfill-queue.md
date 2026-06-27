# Doc Backfill Queue

Generated: 2026-06-25. Completed: 2026-06-25.

Docs are organised by domain under `docs/`:

```
docs/
  judging/        scoring, offline sync, scoresheets
  competition/    lifecycle, results, rankings, bottle assignment
  member/         registration, invitations, auth
  admin/          Filament panel, artisan commands, styles
  architecture/   cross-cutting design decisions
  env-reference.md
  docker-reference.md
  docker-howto.md
  pwa-reference.md
```

---

## Tier 1 — User-facing features

- [x] Judging screen — `docs/judging/` — done 2026-06-25
- [x] Competition lifecycle — `docs/competition/lifecycle-*.md` — done 2026-06-25
- [x] Member registration + invitation — `docs/member/registration-*.md` — done 2026-06-25
- [x] Results & rankings — `docs/competition/results-*.md` — done 2026-06-25
- [x] Password + magic-link auth — `docs/member/auth-*.md` — done 2026-06-25

## Tier 2 — CLI / API endpoints

- [x] `POST /scoresheets` — covered in `docs/judging/reference.md`
- [x] `GET /judging/{competition}` — covered in `docs/judging/reference.md`
- [x] `GET /competitions/{competition}/results` — covered in `docs/competition/results-reference.md`
- [x] `member:create` — `docs/admin/artisan-commands-reference.md` — done 2026-06-25
- [x] `member:delete` — `docs/admin/artisan-commands-reference.md` — done 2026-06-25
- [x] `member:password` — `docs/admin/artisan-commands-reference.md` — done 2026-06-25

## Tier 3 — Configuration

- [x] Environment variables — `docs/env-reference.md` — done 2026-06-25
- [x] Docker stack — `docs/docker-reference.md`, `docs/docker-howto.md` — done 2026-06-25
- [x] PWA / service worker — `docs/pwa-reference.md` — done 2026-06-25
- [x] BJCP style seeder — `docs/admin/bjcp-seeder-*.md` — done 2026-06-25

## Tier 4 — Internal classes

- [x] `CompetitionRankingService` — `docs/competition/ranking-service-*.md` — done 2026-06-25
- [x] `BottleNumberAssignmentService` — `docs/competition/bottle-assignment-*.md` — done 2026-06-25
- [x] `DescriptorService` — `docs/judging/descriptor-service-*.md` — done 2026-06-25
- [x] `MagicLink` model — `docs/member/magic-link-reference.md` — done 2026-06-25
- [ ] `CompetitionStatus` enum — covered in `docs/competition/lifecycle-reference.md`
- [x] `RequireActiveMember` middleware — `docs/member/active-member-middleware-reference.md` — done 2026-06-25

## Tier 5 — Architecture

- [x] Two-frontend architecture — `docs/architecture/two-frontend-explanation.md` — done 2026-06-25
- [x] Offline-first scoresheet sync — `docs/judging/explanation-offline-sync.md` — done 2026-06-25
- [x] Filament admin panel overview — `docs/admin/filament-overview-explanation.md` — done 2026-06-25
- [x] BJCP scoring model — `docs/architecture/bjcp-scoring-explanation.md` — done 2026-06-25

---

**Remaining:** 0 items — backfill complete 2026-06-25.
