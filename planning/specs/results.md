# Results

Results are computed from submitted scoresheets. Each participant gets a printable per-entry breakdown plus the full competition standings.

## Ranking

An entry's score is the average of all submitted scoresheet totals. Entries rank descending by that average across the whole competition — placement is competition-wide, not per style.

**Tiebreaker:** averaged overall-impression score (BJCP convention). If two entries have the same averaged total, the one with the higher averaged `overall` score places higher.

Because the average is computed from current scoresheet values, editing a scoresheet (while the competition is still in `judging`) updates the ranking automatically.

## Access control

**Deviation from original PRD:** the PRD specified a `published_at` field that admins would set to release results to members after a sanity check. This was dropped. Access is now purely status-based:

| Viewer | `draft` | `open` | `judging` | `closed` |
|---|---|---|---|---|
| Admin | yes | yes | yes | yes |
| Regular member | — | — | — | yes |

Admins see results at any status. Regular members see results only once a competition reaches `closed`. The admin still controls timing by deciding when to advance the competition to `closed`.

## Results sheet (per participant)

`GET /competitions/{competition}/results/{member}` — individual result sheet for one member.

**Part 1 — the member's own entries:** for each beer, the style, the averaged total, its score band, placement, and a breakdown of every judge's scores (aroma/3, appearance/3, flavour/20, mouthfeel/5, overall/10) with per-section notes. Descriptors flagged by judges are shown.

**Part 2 — full standings:** every entry ranked by average total, with brewer names revealed (blind judging is over). Includes averaged scores and placement for all entries.

Print-styled Blade view. The print button triggers the browser print dialog.

## Results index

`GET /competitions/{competition}/results` — lists all members who have entries in the competition, each linking to their individual result sheet. Serves as the navigation layer for batch printing (admin prints each participant's sheet from this list).

Access rules match the individual sheet: admins at any status, members only when closed.

## Score bands (display)

Shown alongside averaged totals on the results sheet for context:

| Band | Range |
|---|---|
| Outstanding | 45–50 |
| Excellent | 38–44 |
| Very Good | 30–37 |
| Good | 21–29 |
| Fair | 14–20 |
| Problematic | 0–13 |

## Judge variance flagging

The Filament Competition > Results tab shows a variance table. For each entry with multiple submitted scoresheets, it displays the spread (max − min) of the submitted totals. Entries with a spread over roughly 7 points are flagged, so an admin can prompt a re-taste or discussion before closing the competition.

No scores are adjusted. The flag is informational only. No `reviewed_at` field exists yet to mark a flag as handled.

## Views

Both results views use `<x-app-head>` for their page head. `results/show` passes the print stylesheet (`@media print`) via the component slot.

## Out of scope

PDF generation (dompdf). Per-style rankings or category placements. Automated email of results links (planned — see roadmap). Per-member score history across competitions (planned — see roadmap). Awards, medals, or best-in-show designations.
