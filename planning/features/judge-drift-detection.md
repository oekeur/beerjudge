# Feature: Judge Drift Detection

## Context

During competitions, judges score 5–10 beers in sequence, all judges scoring the same beer at the same time and moving through entries together as a group. All judges score all beers. With 5–10 judges present, every entry ends up with a full set of cross-judge scores by the time the group moves on.

Two things can go wrong:
1. A judge's scores drift upward over the session as the evening progresses (intoxication, palate fatigue, leniency creep).
2. A judge scores consistently differently from the group — acceptable up to a point, but worth reviewing post-event.

The existing `JudgeVarianceWidget` in Filament already flags per-entry spread > 7 points. This feature extends that with drift-aware signals.

---

## Goals

- **Live nudge during judging:** alert a judge when their own scores are trending upward relative to their earlier scores that evening. Informational, not blocking. The message should be non-judgmental: "your scores seem to be climbing — if the beers are genuinely better, great; if not, now you know."
- **Post-event drift view in Filament:** per-judge score trajectory (absolute drift over submission order) and per-judge relative drift vs. group consensus, surfaced per competition.
- **Extend the existing variance story:** the current widget shows spread per entry; this feature adds a per-judge lens across the full competition.

---

## Non-Goals

- No automated score adjustments or corrections.
- No live relative-drift nudge (showing a judge how they compare to others during scoring). That belongs post-event only.
- No persistent "drift score" stored on the judge or member record — computed on-demand from scoresheets.
- No notifications or emails about drift.
- No changes to how scores are calculated or ranked.

---

## Data foundation

All the data needed already exists:

```
scoresheets: id, entry_id, judge_id, total (computed), submitted_at, status
```

Submission order is derived from `submitted_at`. Since all judges score simultaneously and move through entries as a group, `submitted_at` ordering reliably reflects the sequence within a judge's session.

Cross-judge consensus is the mean `total` across all submitted scoresheets for a given entry. Because all judges score all entries simultaneously, prior entries always have a complete set of scores by the time any judge submits their next one.

---

## Signal 1: Absolute individual drift (live + post-event)

**What it measures:** a judge's own scores trending upward over the session, regardless of what others scored.

**How to compute:**
- Take all submitted scoresheets for this judge in this competition, ordered by `submitted_at`.
- Compare the rolling average of the last 3 scores to the rolling average of the first 3 scores.
- At 5 submissions, this compares submissions 1–2 (or 1–3) against 3–5 (or 3–5). Thin but meaningful.
- Drift threshold: +5 points difference between early and late average (tunable).

**Live surface:** a nudge on the judging screen after the judge submits their Nth scoresheet (minimum 4 submitted before the nudge can trigger). See Live Nudge options below.

**Post-event surface:** per-judge score timeline in Filament — score over submission order, with a trend line or slope value.

---

## Signal 2: Relative drift vs. group consensus (post-event only)

**What it measures:** a judge's scores diverging from the group average across their submissions — catching judges who are consistently harsh or lenient outliers, or whose delta vs. consensus is widening over the session.

**How to compute:**
- For each submitted scoresheet by the judge, compute `delta = judge_score - mean_score_for_that_entry`.
- Plot delta over submission order.
- A widening positive delta over the session = relative drift (not just a consistent outlier).
- A flat non-zero delta = consistent outlier (already partially caught by the existing variance widget).

**Post-event surface:** per-judge relative drift view in Filament. Show the delta sequence, average delta, and whether it's widening.

Note: the existing `JudgeVarianceWidget` flags per-entry spread > 7 pts. This feature adds the per-judge cross-entry view. They complement each other.

---

## Live Nudge options

These apply only to Signal 1 (absolute individual drift). Relative drift is post-event only.

**Option A: Persistent banner**
After submission N (when drift threshold is crossed), a dismissable banner appears at the top of the judging screen. Stays visible for subsequent scoresheets. Low friction; easy to miss or ignore.

*Message:* "Your scores seem to be running higher than at the start of the evening. If the beers are genuinely better, great — if not, worth a pause."

**Option B: Pre-submit score preview**
Before the judge submits, show their current scoresheet total alongside their session average: "your avg so far: 34 — this scoresheet: 41." No warning language, just the numbers. Lets them self-correct before submitting.

Works well combined with Option A: the banner signals the pattern, the preview gives a moment to reconsider on each subsequent entry.

**Option C: Post-submit confirmation modal**
If drift is detected, a lightweight dialog appears after submitting — one tap to dismiss. More interrupting than a banner, harder to miss. Risk: annoying for judges who've genuinely tasted better beers.

**Option D: Ambient sparkline**
A small score history chart always visible in a corner of the judging screen — no threshold, no warning. Judges self-monitor. Drift becomes visually obvious without any system intervention. Requires no drift detection logic.

**Option E: Post-session debrief only**
After all scoresheets are submitted, show the judge a "your score trajectory tonight" summary before they leave. No interruption during judging. Can't help mid-session but avoids friction entirely.

**Recommended starting point:** Option A (persistent banner) + Option B (pre-submit preview) combined. The banner creates awareness; the preview gives the judge a recalibration moment on each subsequent entry without blocking them.

---

## Implementation sketch

### Live nudge (judging screen)

Two viable approaches:

**Server-side (recommended):** The existing `POST /scoresheets` endpoint already returns a JSON response. Extend it with a `drift_warning` field when drift is detected. Alpine reads the response and shows the nudge. All drift logic lives server-side; the judging screen stays stateless.

**Client-side:** Alpine already holds submitted scores in state (`judgingApp()`). Compute drift locally from that state after each submission. No server round-trip. Simpler but can only compute absolute drift (no cross-judge comparison, which is fine — live nudge is Signal 1 only).

Either works. Server-side is cleaner if drift thresholds ever need tuning without a JS deploy.

### Post-event Filament view

Extend the existing competition detail view or add a new tab/page. Options:

- New tab on the competition page (alongside the existing variance tab)
- Extend `JudgeVarianceWidget` with a "Judge Drift" section
- Dedicated Filament page per competition

Per-judge row showing: name, number of scoresheets submitted, score sequence (comma-separated or sparkline), slope/trend, average delta vs. consensus.

---

## Thresholds (tunable)

| Signal | Default threshold | Notes |
|--------|-------------------|-------|
| Absolute drift trigger | +5 pts (last 3 avg vs. first 3 avg) | Minimum 4 submissions before evaluating |
| Relative drift flag | > 3 pts average delta vs. consensus | Consistent outlier threshold |
| Relative drift "widening" | delta growing > 2 pts per beer over last 3 | Drift vs. outlier distinction |

These should be constants in a config or service class, not hardcoded.

---

## Open questions

- Should the live nudge be dismissable once and not return, or keep reappearing each submission while drift persists?
- Should judges see their own post-event drift view, or only admins?
- What's the minimum number of submissions before the drift computation is shown at all? (Suggested: 4)
- Should the pre-submit score preview (Option B) be always-on, or only trigger when drift is detected?
