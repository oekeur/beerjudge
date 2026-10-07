# How to judge a competition

Score your assigned beer entries and submit your scoresheets before the competition closes.

## Prerequisites

- An active member account
- An active member account (register as a judge in step 1 if an admin has not added you)
- The competition must be in `judging` status

## Steps

### 1. Register as a judge

Either an admin adds you as a judge, or you open the judging screen once judging starts and click **Register as judge**. **Leave as judge** on the same screen removes you.

### 2. Open the judging screen

Once the competition moves to judging status, a **Judge entries** link appears on the home page. Click it, or navigate directly to `/judging/{competition-id}`.

You will see all eligible entries in tasting order (`#1`, `#2`, ...), set by the admin. Use the **Order** toggle to switch to **My ranking**, which sorts by your own scores. Your own entries are excluded — you cannot score beer you brewed.

### 3. Score an entry

Click any entry card to expand the scoresheet.

Score each of the five BJCP sections using the numbered buttons:

| Section | Max |
|---------|-----|
| Aroma | 12 |
| Appearance | 3 |
| Flavour | 20 |
| Mouthfeel | 5 |
| Overall Impression | 10 |

Click the **info icon** next to any section heading to open the BJCP style guideline for that section. The appearance section includes an SRM colour gradient showing the expected colour range for the style.

Add tasting notes in the text area beneath each score row. Notes save automatically as you type.

Expand **Off-Flavor Descriptors** to tick any faults present (diacetyl, acetaldehyde, DMS, etc.).

Your scoresheet saves to the browser automatically after every change. The sync badge in the entry header shows the save state:

| Badge | Meaning |
|-------|---------|
| Saving (pulsing amber) | Writing to server |
| Saved (grey) | Draft saved |
| Submitted (green) | Final, locked |
| Retry (red) | Network error — tap to retry |

### 4. Submit a scoresheet

When all five sections have a score, click **Submit** at the bottom of the scoresheet. The card collapses to a read-only summary showing your scores and the scoring band (e.g., Very Good, 30–37).

A submitted scoresheet is locked in the UI. To change a score after submitting, click **Reopen to edit** — this sets the sheet back to draft and lets you edit it again. You can resubmit as many times as you like until the competition closes.

### 5. Track progress (admin only)

If you are an admin, a collapsible panel at the top of the screen shows how many judges have submitted for each entry (`submitted / eligible`, where eligible judges exclude the entry's brewer). This refreshes every 30 seconds.

### 6. Work offline

The judging screen works without a network connection. Every scoresheet is written to local storage (IndexedDB) first. When the network returns, pending sheets sync automatically. If a sync fails, it retries after 5 seconds. You can also tap the red **Retry** badge on a specific entry to force an immediate retry.

## Verification

A green **Submitted** badge on an entry confirms the scoresheet reached the server. You can also check your submitted scores by collapsing and reopening the entry card — a submitted card shows the read-only summary view.

## Troubleshooting

**The judging screen shows HTTP 403.**
The competition is not in `judging` status. Contact the admin to advance the competition phase.

**The Retry badge stays red after tapping.**
The server may be unreachable. The sheet is safe in local storage and will sync when connectivity returns. Check your network connection.

**A score section shows a red ring after clicking Submit.**
That section has no score set. Fill in all five sections before submitting.

**You cannot see an entry you expected.**
Judges do not see their own entries. If you brewed the entry, you are excluded from scoring it.

## Related

- [Judging screen reference](reference.md)
- [Why the judging screen is offline-first](explanation-offline-sync.md)
