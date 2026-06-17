# Plan: Security Hardening (pre-launch)

## Context

A security review was completed ahead of the app going public. Most critical findings are straightforward fixes. One is already handled correctly (`ResultsController` has a proper auth check at line 21-23). Items are split into two phases: blocking launch, and deferred low-priority.

`.env.production` already has `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, and `SESSION_SAME_SITE=strict`.

---

## Phase 1: Blocking — must be fixed before launch

### 1.1 Trusted proxy

**File:** `bootstrap/app.php`

Without trusted proxy config, `Request::ip()` returns the proxy IP, which breaks rate limiting. The docker setup confirms the app runs behind nginx (`VIRTUAL_HOST=competition.foebar.com`, `NETWORK=proxy`).

Fix — add `->withMiddleware()` trust config:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

**Test:** Write a feature test that sends a request with `X-Forwarded-For: 1.2.3.4` and asserts `$request->ip()` returns `1.2.3.4`, not the loopback address.

---

### 1.2 Scoresheet UUID scoping

**File:** `app/Http/Controllers/ScoresheetController.php`, line 51

The UUID lookup has no judge scope. A member who knows another judge's `client_uuid` can overwrite their sheet.

Current:
```php
$sheet = Scoresheet::where('client_uuid', $data['client_uuid'])->first()
```

Fix:
```php
$sheet = Scoresheet::where('client_uuid', $data['client_uuid'])
    ->where('judge_id', $judge->id)
    ->first()
```

The fallback on line 52 (`where('entry_id', ...)->where('judge_id', ...)`) is already correctly scoped.

**Test:** Add a test case to `ScoresheetTest` (or create `tests/Feature/ScoresheetSecurityTest.php`): member A creates a sheet and its `client_uuid` is known; member B POSTs to `/scoresheets` with that same UUID — assert the response is a 422 or that member A's sheet is unchanged.

---

### 1.3 Rate limiting on auth token routes

**File:** `routes/web.php`

`/magic-link/{token}` (GET, line 19) and `/password/reset/confirm` (POST, line 26) have no throttle middleware. Both consume single-use tokens, making them brute-forceable.

Fix — add `->middleware('throttle:5,1')` to each:

```php
Route::get('/magic-link/{token}', [AuthController::class, 'verifyMagicLink'])
    ->name('auth.magic-link.verify')
    ->middleware('throttle:5,1');
```

```php
Route::post('/password/reset/confirm', [PasswordResetController::class, 'resetPassword'])
    ->name('password.update')
    ->middleware('throttle:5,1');
```

**Test:** Add tests that hit each route 6 times in quick succession and assert the 6th returns 429.

---

### 1.4 Environment and credentials

**File:** `.env.production` (not committed — handled manually or via deployment config)

| Variable | Current value | Required change |
|----------|--------------|-----------------|
| `APP_ENV` | `testing` | `production` |
| `DB_PASSWORD` | `secret` | Rotate to a strong password |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Set to the real sender address |
| `REDIS_PASSWORD` | `null` | Confirm Redis is localhost-only; if exposed, set a password |

These are ops tasks with no code change. Verify `APP_ENV=production` is set before the first deploy by checking `php artisan config:show app.env` on the server.

---

### 1.5 ResultsController authorization — already correct

`ResultsController::show` at line 20-23 checks that non-admins can only view results for their own `{member}` param:

```php
if (! $viewer->is_admin) {
    abort_unless($competition->isClosed(), 403);
    abort_unless($viewer->id === $member->id, 403);
}
```

No change needed. A test covering this path is worth adding if one does not already exist: assert a member gets 403 when requesting another member's results URL.

---

## Phase 2: Deferred — post-launch

### 2.1 Last-admin guard

Prevent an admin from removing their own `is_admin` flag when they are the only admin. Without this, the app can end up with no admin and no way to recover without database access.

**Where to implement:** A Filament action hook or model observer on `Member` that counts remaining admins before allowing the update.

No test until implemented.

---

### 2.2 Redis password

Only needed if Redis is not bound to localhost. Check with `redis-cli CONFIG GET bind` on the server. If the bind address includes anything other than `127.0.0.1`, set `REDIS_PASSWORD` in `.env.production` and `requirepass` in `redis.conf`.

---

## Verification Checklist

**Phase 1 code changes:**
- [ ] `bootstrap/app.php` — `trustProxies(at: '*')` present; test passes
- [ ] `ScoresheetController.php` — UUID lookup includes `judge_id` scope; security test passes
- [ ] `routes/web.php` — both token routes have `throttle:5,1`; rate limit tests pass
- [ ] `php artisan test --compact` green after all changes
- [ ] `./vendor/bin/phpstan analyse` passes

**Phase 1 ops tasks:**
- [ ] `APP_ENV=production` confirmed on server (`php artisan config:show app.env`)
- [ ] `DB_PASSWORD` rotated
- [ ] `MAIL_FROM_ADDRESS` set to real sender
- [ ] Redis bind address confirmed localhost-only (or password set)
