# BottleNumberAssignmentService: design explanation

## The problem this solves

Homebrew judging is blind. Judges score beers without knowing who brewed them, which removes bias toward familiar brewers. To make this work, each physical bottle must carry an identifier that connects it to the judging system without revealing the brewer's identity.

Bottle numbers serve as that identifier. They are assigned at the moment judging begins and remain fixed for the rest of the competition.

## Why numbers are assigned at the `open` → `judging` transition

Bottle numbers need to be present before judging starts and stable during judging. They label the physical bottles and identify entries in the admin tables, the results sheets and the judge report; the judging screen does not show them (judges work from a per-judge tasting number, the position in `sort_order` excluding their own entries). The `moveToJudging` action is the natural moment because:

- Member registration is now locked, so the set of entries is complete (admins can still edit entries in the panel).
- Admins use the number to match a physical bottle to an entry in the admin tables.
- Assigning numbers earlier (during `open`) would mean numbers could become stale if entries are added or removed.

The assignment and the status update happen in sequence. If the assignment fails, the status stays `open` and no partial assignments persist (the transaction rolls back).

## Why sequential, not random

The current implementation assigns numbers in insertion-order sequence (1, 2, 3, ...). This is simple, deterministic, and easy to verify.

Random assignment would require generating a permutation of integers and checking for collisions. For 5-10 entries, this complexity is not warranted. Sequential numbers also make manual verification easier: after assigning, an admin can confirm that entries have consecutive numbers starting from 1.

The tradeoff is that sequential numbers correlate with registration order. A knowledgeable judge might infer from the number that "bottle 1 was the first entry submitted". In practice, this is not a meaningful leak for a club of 10 people where most members know approximately when others register.

If blind-ness against registration-order inference becomes a concern, the service could be updated to shuffle `$i` before iteration, or to draw from a pre-generated random permutation.

## Why `sort_order` and `bottle_number` are separate fields

An earlier design used a single field for both ordering and identification. This was separated because:

- Sort order controls the sequence in which judges work through entries on the judging screen. Admins set it by dragging rows in the Entries tab of the competition, or with the Sort by ABV button (ascending, entries without ABV last), to group similar styles or arrange a logical tasting progression.
- Bottle numbers are the physical label on the bottle. They must not change once bottles are prepared for judging.

If the two were the same field, an admin reordering entries on the judging screen would inadvertently relabel the physical bottles. The separation means an admin can adjust the judging sequence without touching the bottle labels.

## Related

- [Bottle assignment reference](bottle-assignment-reference.md)
- [Lifecycle reference](lifecycle-reference.md)
- [Lifecycle explanation](lifecycle-explanation.md)
