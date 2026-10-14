# How to judge a competition

Score your assigned beer entries and submit your scoresheets before the competition closes.

## Prerequisites

- An active member account
- An active member account (participate in step 1 if an admin has not added you)
- The competition must be in `judging` status

## Steps

### 1. Participate

Only members on the competition's judge list can score. Either an admin adds you as a judge, or you open the judging screen once judging starts and click **Participate**; until then the screen shows the Participate prompt instead of the entries. **Stop participating** on the same screen removes you, while the competition is open or judging and also after you have submitted. Sheets you already submitted stay stored and keep counting in the ranking, but you no longer get a judge report.

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

Click the **info icon** next to any section heading to open the style guideline (BJCP or Brewers Association) for that section. The appearance section includes an SRM colour gradient showing the expected colour range for the style.

Add tasting notes in the text area beneath each score row. Notes save automatically as you type.

Expand **Off-Flavor Descriptors** to tick any faults present (diacetyl, acetaldehyde, DMS, etc.).

Your scoresheet saves to the browser automatically after every change. The sync badge in the entry header shows the save state:

| Badge | Meaning |
|-------|---------|
| Saving (pulsing amber) | Writing to server |
| Saved (grey) | Draft saved |
| Submitted (green) | Final, locked |
| Waiting for connection (grey) | No connection. The sheet is safe on your device and syncs by itself when the connection returns |
| Retry (red) | The server answered with an error. Tap to retry |

### 4. Submit a scoresheet

When all five sections have a score, click **Submit** at the bottom of the scoresheet. The card collapses to a read-only summary showing your scores and the scoring band (e.g., Very Good, 30–37).

A submitted scoresheet is locked in the UI. To change a score after submitting, click **Reopen to edit** — this sets the sheet back to draft and lets you edit it again. You can resubmit as many times as you like until the competition closes.

### 5. Track progress (admin only)

If you are an admin, a collapsible panel at the top of the screen shows how many judges have submitted for each entry (`submitted / eligible`, where eligible judges are the current judges plus anyone who already submitted on that entry, minus the entry's brewer). This refreshes every 30 seconds.

### 6. Work offline

Keep the judging screen open: the page itself is not cached, so it must be open before the connection drops. If you lose the connection while it is open, keep scoring. Every scoresheet is written to local storage (IndexedDB) first, and sheets you submit show **Waiting for connection** until the network returns, then sync on their own without a reload. Do not reload the page while offline; it will not load. Your sheets stay on the device and sync the next time you open the screen with a connection.

Open the judging screen in one tab at a time. If you open it in a second tab, that tab shows **Judging is open in another tab.** and stays read-only. Tap **Use this tab** to continue there; the first tab then shows the same message, and anything it had queued is sent from the new tab.

If the server answers with an error, the badge turns red and reads **Retry**. The app retries after 5 seconds; you can also tap the badge to retry at once.

## Verification

A green **Submitted** badge on an entry confirms the scoresheet reached the server. **Waiting for connection** means it has not yet. You can also check your submitted scores by collapsing and reopening the entry card — a submitted card shows the read-only summary view.

## Troubleshooting

**The judging screen shows HTTP 403.**
The competition is not in `judging` status. Contact the admin to advance the competition phase.

**The badge reads Waiting for connection and does not clear.**
The device is offline or the server is unreachable. The sheet is safe in local storage and syncs when connectivity returns. Check your network connection and keep the page open.

**The Retry badge stays red after tapping.**
The server keeps answering with an error. The sheet is safe in local storage. Tell an admin if it persists.

**A score section shows a red ring after clicking Submit.**
That section has no score set. Fill in all five sections before submitting.

**You cannot see an entry you expected.**
Judges do not see their own entries. If you brewed the entry, you are excluded from scoring it.

## Related

- [Judging screen reference](reference.md)
- [Why the judging screen is offline-first](explanation-offline-sync.md)
