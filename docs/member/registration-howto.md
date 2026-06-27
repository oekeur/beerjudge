# How to Invite and Register a New Member

Two roles are involved: an admin (creates the invitation) and the new person (completes registration).

---

## Prerequisites

- You are logged in as an admin.
- You have the new person's email address.
- Mail is configured so the app can deliver messages, or you can share the invitation link manually.

---

## Part 1: Create the invitation (admin)

### Step 1: Open the Invitations resource in Filament

Navigate to `/admin` and select **Invitations** from the sidebar.

### Step 2: Create a new invitation

Click **New Invitation**. Choose the invitation type:

- **Single use:** expires after the first person completes registration. Use this when inviting one specific person.
- **Time-bound:** remains valid until the `expires_at` datetime you set. Multiple people can register with the same link. Use this for open club sign-up windows.

Save the form.

### Step 3: Copy the invitation link

Open the invitation record. The invitation URL takes the form:

```
https://<your-domain>/join/<token>
```

Copy the full URL and send it to the new person out of band (email, chat, etc.).

---

## Part 2: Register (new member)

### Step 1: Open the invitation link

Visit the URL the admin sent you. The link loads the registration form. If you see a "Gone" (410) page, the invitation has expired or was already used; contact the admin.

### Step 2: Fill in the form

| Field | Notes |
|---|---|
| Name | Your display name, shown to other members and judges. |
| Email | Must not already exist in the system. |
| Password | Minimum 8 characters. |
| Confirm password | Must match exactly. |

### Step 3: Submit

Click **Register**. On success, you are redirected to the login page with a confirmation message.

---

## Step 4: Wait for activation (admin action)

New accounts are created with `is_active = false`. The new member cannot log in until an admin activates the account.

**Admin:** open Filament, go to **Members**, find the new member, and toggle **Active** to on. Save.

---

## Verification

- The new member appears in the **Members** list in Filament with `Active = false` immediately after registration.
- After activation, the member can log in at `/login`.
- Attempting to log in before activation returns an "account is not active" error on the login page.

---

## Troubleshooting

**"410 Gone" on the invitation link**
The invitation was already used (single-use type) or its expiry has passed. Create a new invitation in Filament.

**"404 Not Found" on the invitation link**
The token in the URL does not match any invitation. Check the copied URL for truncation.

**"Email has already been taken" on the registration form**
An account already exists with that email. The member should use the password reset flow or contact an admin.

**Member cannot log in after registering**
The account is still inactive. An admin must toggle `is_active` to true in Filament.

---

## Related

- [registration-reference.md](registration-reference.md) — invitation types, routes, and validation rules
- [auth-howto.md](auth-howto.md) — how to log in after activation
- [active-member-middleware-reference.md](active-member-middleware-reference.md) — behaviour when `is_active` is false
