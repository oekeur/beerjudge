# How to Add a New Member

Use `member:create` to add a member from the command line. This is the preferred path for the first admin account and for any member who cannot self-register.

---

## Prerequisites

- SSH or local access to the server running the app.
- The app's `.env` is loaded (i.e. you are in the project root or the correct environment is active).

---

## Steps

**1. Run the command**

```bash
php artisan member:create
```

Or pass options. In an interactive terminal the name and email prompts still appear and the option values win; add `--no-interaction` to skip them (the password is still asked):

```bash
php artisan member:create --name="Jane Brewer" --email="jane@example.com" --admin
```

**2. Respond to prompts**

The command asks for name and email first; if you passed `--name` or `--email`, that value is used and the typed answer is ignored. Then it always asks for a password (hidden input).

Finally, it asks whether to grant admin access — unless you passed `--admin`, in which case it skips that prompt entirely.

**3. Confirm the output**

On success you see:

```
Member created: Jane Brewer (jane@example.com) [admin].
```

The `[admin]` suffix confirms admin access was granted.

---

## Verification

Check the member exists in the database:

```bash
php artisan tinker --execute 'echo App\Models\Member::where("email", "jane@example.com")->value("name");'
```

Or log in at `/admin` with the new credentials.

---

## Troubleshooting

**"The email has already been taken."**
A member with that email already exists. Use `php artisan member:password` to reset their password instead.

**"Password cannot be empty."**
The password prompt was submitted blank. Re-run the command and enter a non-empty password.

**"No application encryption key has been set."**
The `.env` file is missing or `APP_KEY` is not set. Run `php artisan key:generate` and retry.

---

## Related

- [All member:* commands (reference)](artisan-commands-reference.md)
- [Filament admin panel overview](filament-overview-explanation.md)
