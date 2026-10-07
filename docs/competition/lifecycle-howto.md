# How to run a competition from start to finish

## Prerequisites

- Admin access to the Filament panel
- At least one beer style attached to the competition before entries open

## Step 1: Create the competition

1. Go to the admin panel and open **Competitions**.
2. Click **New competition**.
3. Fill in **Name**, **Year**, and optionally **Judging date**.
4. Save. The competition starts in **Draft** status.

Do not attach entries yet. The draft stage is for setup only.

## Step 2: Attach styles

1. Open the competition in the admin panel.
2. Go to the **Styles** tab.
3. Search and attach every style you want entrants to register against.

Entries reject any style not attached to the competition (`Entry::booted()` enforces this at save time). Attach styles before opening registration.

## Step 3: Open registration

1. Open the competition edit page.
2. Click **Move to Open** in the header.

The status changes to **Open**. Members can now register entries.

**Verification:** the status badge on the edit page shows "Open" (blue).

## Step 4: Distribute the entry link

1. On the competition edit page, open the **Entries** tab. The entry form URL is shown at the top with a Copy button.
2. Copy and share it with members.

Members who follow the link and are logged in can submit entries.

## Step 5: Close registration and begin judging

When all entries are in:

1. Open the competition edit page.
2. Click **Move to Judging**.
3. Read the confirmation dialog — it warns that registration will lock and bottle numbers will be assigned.
4. Confirm.

The system:
- Calls `BottleNumberAssignmentService::assign()`, which assigns sequential bottle numbers starting at 1, ordered by `entries.id`.
- Updates status to **Judging**.

**Verification:** each entry now has a bottle number. Check via the **Entries** tab.

**Troubleshooting:** if bottle numbers appear missing after the transition, reload the Entries tab. The assignment runs in a transaction; if it fails, the status stays Open and no bottle numbers change.

## Step 6: Set up judges

1. Open the competition edit page.
2. Add judges in the Judges field on the **Dashboard** tab, or copy the link next to that field and send it to judges (it works once the competition is in Judging).

A member can also click **Register as judge** on the judging screen (`POST /competitions/{competition}/judges`, allowed while open or judging); **Leave as judge** removes them. A judge cannot score their own entries; the judging screen enforces this by excluding those entries from their list.

## Step 7: Judge the competition

1. Click **Open Judging Screen** in the header of the edit page.
2. Share the judging screen URL with all judges.

Judges fill in scoresheets at `/judging/{competition}`. Scores auto-save to IndexedDB and sync to the server. See the judging screen documentation for details: `../judging/reference.md`.

## Step 8: Close the competition

When all scoresheets are submitted:

1. Open the competition edit page.
2. Click **Move to Closed** and confirm.

The status changes to **Closed**. Results become visible to all members.

**Verification:** click **View Results** in the header. The results index page lists all members with entries. Open any member's sheet to confirm scores are present.

**Troubleshooting:** if a member's results page shows no scores, check that the judge submitted (not just saved as draft) their scoresheets. Only `submitted` scoresheets count toward rankings. See [results reference](results-reference.md).

## Verification

After closing a competition, confirm:

- Every entry has a `bottle_number` set.
- Every expected scoresheet has `status = submitted` in the database.
- The results index at `/competitions/{id}/results` is accessible to members.

## Related

- [Lifecycle reference](lifecycle-reference.md)
- [Lifecycle explanation](lifecycle-explanation.md)
- [Bottle assignment reference](bottle-assignment-reference.md)
- [Ranking service reference](ranking-service-reference.md)
- [Results how-to](results-howto.md)
