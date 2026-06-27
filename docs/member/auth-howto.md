# How to Authenticate as a Member

Three methods are available: password login, magic link, and password reset. Use whichever fits the situation.

---

## How to log in with a password

### Step 1: Open the login page

Navigate to `/login`.

### Step 2: Enter your credentials

Fill in your email address and password. Check **Remember me** if you want the session to persist across browser restarts.

### Step 3: Submit

Click **Log in**. On success you are redirected to the home page (`/`) or, if your account has `is_admin = true`, to `/admin`. If a login attempt redirects you somewhere unexpected, the app may be honouring an `intended` URL from a previous navigation.

---

## How to log in with a magic link (no password)

Use this if you have forgotten your password or prefer not to set one.

### Step 1: Open the magic link request page

Navigate to `/magic-link`.

### Step 2: Enter your email

Type the email address associated with your account and submit the form.

The page always shows a success message regardless of whether the email matched an account. This is intentional.

### Step 3: Check your email

Open the email and click the link. The link is valid for 15 minutes and can only be used once.

### Step 4: Verify

Clicking the link logs you in and redirects you to `/` or `/admin`. The link is immediately invalidated; a second click returns an error.

---

## How to reset a forgotten password

### Step 1: Open the reset request page

Navigate to `/password/reset` or click the "Forgot password" link on the login page.

### Step 2: Enter your email

Type your email address and submit.

The page shows a success message regardless of whether an email was sent. Reset emails are only sent to active accounts. If you do not receive the email, contact an admin to confirm your account exists and is active.

### Step 3: Click the link in the email

The reset link from the email opens a form pre-filled with your email address.

### Step 4: Set a new password

Enter your new password (minimum 8 characters) and repeat it in the confirmation field. Submit the form.

On success you are logged in immediately and redirected to `/`.

---

## How to log out

Submit the logout form, which is present in the navigation on every authenticated page. The form uses `POST /logout` with a CSRF token. After logout you are redirected to `/login`.

Alternatively, you can log out from any browser tab by navigating to a page that shows the navigation, then clicking **Log out**.

---

## Verification

- After a successful password login or magic link login, `GET /` returns 200 (not a redirect to `/login`).
- After logout, navigating to `/` redirects to `/login`.
- An expired magic link (older than 15 minutes) redirects to `/login` with an error message, not a 500.

---

## Troubleshooting

**"Your account is not active" on the login page**
The account exists but an admin has not yet activated it (or has deactivated it). Contact an admin.

**Magic link email does not arrive**
Wait a minute and check spam. If mail is not configured in the environment the email silently fails; contact the administrator.

**Magic link shows an error when clicked**
The link has either expired (15-minute window) or was already used. Request a new one at `/magic-link`.

**Password reset email does not arrive**
The account may be inactive. Inactive accounts do not receive reset emails. Contact an admin to confirm your account status.

**Rate limit error (429)**
Login: 6 attempts per minute. Magic link requests: 3 per 5 minutes. Magic link verification: 5 per minute. Password reset confirmation: 5 per minute. Wait before retrying.

---

## Related

- [auth-reference.md](auth-reference.md) — route details, rate limits, validation rules
- [magic-link-reference.md](magic-link-reference.md) — MagicLink model internals
- [registration-howto.md](registration-howto.md) — how to create an account
