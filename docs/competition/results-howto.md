# How to view and share competition results

## Prerequisites

- A closed competition with at least one submitted scoresheet.
- Admin or member account (access rules differ; see [results reference](results-reference.md)).

## How to view your own results (member)

1. Log in.
2. Navigate to `/competitions/{id}/results/{your-member-id}`.

The page shows your entries with their placement, average score, BJCP band, and the individual scoresheet notes from each judge.

The full standings appear below your entries, with brewer names revealed.

**Access requirement:** the competition must be in `closed` status. The page returns 403 before that.

## How to view another member's results (admin only)

1. Log in as an admin.
2. Navigate to `/competitions/{id}/results/{member-id}`.

Admins bypass the status check and the member-identity check. This works at any status.

## How to view all participants at once (admin batch print)

1. Navigate to `/competitions/{id}/results`.
2. As an admin, the index page lists every member with entries below the standings, ordered by name. Non-admins do not see this list.
3. Open each member's sheet individually, or use the browser's print function on the index page.

There is no one-click "print all sheets" action. Open each member's URL and print it separately, or implement batch rendering via the index page.

## How to share results with members

Results are not emailed automatically. Share the URL manually:

```
https://{domain}/competitions/{competition_id}/results/{member_id}
```

Each member's URL resolves only for that member (or for admins). A member who opens another member's URL receives a 403 page.

To notify all members at once, collect their member IDs from the admin panel and send personalised links. (Email-on-publish is a planned enhancement; see the roadmap.)

## How to check that all scores are in

Before closing the competition, verify no scoresheets remain in draft:

1. Open the competition in the Filament admin panel.
2. Go to the **Dashboard** tab.

The progress table shows, per entry, how many eligible judges have submitted and which judges are still missing (an entry's own brewer is never counted). Contact the missing judges and ask them to submit. The **Results & Variance** tab only lists entries with at least two submitted sheets, flagging a score spread above 7 points.

Alternatively, query the database directly:

```sql
SELECT e.id, e.bottle_number, COUNT(s.id) AS submitted
FROM entries e
LEFT JOIN scoresheets s ON s.entry_id = e.id AND s.status = 'submitted'
WHERE e.competition_id = ?
GROUP BY e.id, e.bottle_number
HAVING submitted = 0;
```

## Verification

After sharing results:

- Log in as a member and confirm the results page loads at `closed` status.
- Confirm a 403 is returned when that member tries to access a different member's sheet.
- Confirm an admin can access any member's sheet at any status.

## Troubleshooting

**Member sees 403 even though the competition is closed.**
Check that the member is accessing their own URL (`/results/{their-id}`, not another member's ID).

**Scores appear as 0 or entries show no placement.**
The relevant scoresheets are probably still in `draft` status. Only submitted scoresheets count toward rankings. Ask judges to submit via the judging screen.

**Member's entry does not appear in the standings.**
The entry exists but has no submitted scoresheets. It is still included in the ranked list with `avg_total = 0` and `sheet_count = 0`, placed last.

## Related

- [Results reference](results-reference.md)
- [Ranking service reference](ranking-service-reference.md)
- [Lifecycle how-to](lifecycle-howto.md)
