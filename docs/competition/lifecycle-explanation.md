# Competition lifecycle: design explanation

## The problem this solves

Running a homebrew competition requires three distinct phases where different groups of people need access to different things. Members register beers. Judges score them blind. Everyone sees the results once scoring is complete. If these phases overlap or if the wrong data is visible at the wrong time, the blind judging is compromised.

A four-state lifecycle enforces these boundaries: `draft` → `open` → `judging` → `closed`.

## Why four states and not two

The simplest version would be "registration open / registration closed". That was not sufficient because:

- Judges need the system to be in a known state before scoring begins (all entries present, bottle numbers assigned).
- Members must not see results until scoring is complete.
- Admins need to view results during judging for debugging, but members must not.

Two states collapse these concerns into one flag and require the controller to apply multiple conditions. Four states make each phase's access rules straightforward to read and test.

## Draft vs. Open

`draft` exists so an admin can configure a competition (attach styles, set the judging date) before any member knows it exists. If `open` were the initial state, members could theoretically register against an incomplete style list.

In practice, drafts are short-lived. The admin creates the competition, attaches styles, and opens it the same day. But the separation prevents accidental early exposure.

## The `judging` transition as an action point

Moving from `open` to `judging` is the most consequential transition. It does two things in sequence (assignment runs in its own transaction; if it throws, the status stays Open):

1. Assigns bottle numbers to every entry.
2. Locks registration.

Combining them in one action prevents the situation where bottle numbers are assigned but registration is still open (a new entry would have no number), or where registration closes but no numbers are assigned yet (the judging screen would show entries without identifiers).

The Filament action asks for confirmation because registration locks and bottle numbers are assigned. The Status field on the Overview tab can still change the status manually.

## No server-side state machine

The lifecycle transitions are enforced only by the Filament action buttons, and the Status field on the Overview tab bypasses them. There is no `canTransitionTo()` guard on the model itself. Updating `status` directly in the database, in tests, or via Tinker is possible.

This is a deliberate tradeoff. A 10-person club running four competitions per year does not need the complexity of a state machine library. The Filament action buttons are the only interface most admins will ever use, and they already enforce the correct order. Adding a model-level guard would increase complexity without adding meaningful protection for this scale.

If the app ever grows to support multiple clubs or automated status changes, a state machine would be worth adding.

## Why results are status-gated instead of date-gated

An earlier version used a `published_at` timestamp to control results visibility (that column was dropped in migration `2026_06_11_084850`). Status gating replaced it because:

- Status is already the source of truth for what the competition is doing.
- A date-gated approach requires a background job or cron to flip visibility, which adds infrastructure that this app deliberately avoids.
- Admins already manage status manually; making results visible is a natural part of closing the competition.

The tradeoff is that "schedule results for 9am tomorrow" is not possible. For a club that announces results in person after judging, this is not a real constraint.

## What happens to draft scoresheets at close

Nothing. The ranking service filters to `status = submitted` scoresheets. A judge who saved work but never submitted is excluded from the rankings. Admins checking results during `judging` status would see this immediately, but there is no automated warning. This is a known gap.

## Related

- [Lifecycle reference](lifecycle-reference.md)
- [Lifecycle how-to](lifecycle-howto.md)
- [Bottle assignment explanation](bottle-assignment-explanation.md)
- [Ranking service explanation](ranking-service-explanation.md)
