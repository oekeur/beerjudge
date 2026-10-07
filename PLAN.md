# Plan: SRM beer color visualization on judging screen

**Goal:** Display an SRM color visualization in the style info modal on the judging screen: a horizontal gradient strip that extends slightly beyond the style's min/max range, with two beer glass SVGs at the boundary positions, and reduced opacity on the out-of-range portions. Judges get a visual reference for what color the beer should be and what out-of-range looks like.
**Out of scope:** SRM display elsewhere (Filament admin, results page); interactive SRM picker; SRM editing on the judging screen; styles without SRM data get a placeholder, not an error.
**Constraints:** Judging screen is Blade + Alpine.js — no Livewire, no additional JS packages. SRM min/max are already in the database but not exposed to the frontend.

---

## Phase 1: Expose SRM data to the frontend

**Outcome:** Each entry object in the Alpine `serverData` payload includes `srm_min` and `srm_max` (nullable floats), and the controller test covers this.

Tasks:
- [x] In `JudgingController::show()`, add `srm_min` and `srm_max` to the per-entry data map (the entry's `style` relation is already eager-loaded; just add the two fields).
- [x] Run `php artisan test --compact --filter=Judging` to confirm no regressions.
- [x] Add assertions to the judging controller test that both SRM fields appear in the response payload for entries whose style has SRM data.

Unknowns:
- Does the current eager-load of `style` on the entries query include `srm_min`/`srm_max`, or are they excluded by a `$hidden` list? Check the Style model.
- [RISK] Some styles have null SRM (e.g. custom styles). The frontend must handle null gracefully on both fields independently.

---

## Phase 2: SRM-to-color conversion

**Outcome:** A small JavaScript utility function `srmToHex(srm)` is available in `judging.js`, returning a hex color string from a numeric SRM value using a lookup table.

Tasks:
- [x] Add `srmToHex(srm)` to `resources/js/judging.js` (or extract to `resources/js/srm.js` and import it). The function should clamp SRM to 1–40 and return a hex string.
- [x] Use a hardcoded SRM 1–40 lookup table (more accurate than the Morey equation at extremes, equally small).
- [x] Round float SRM values to nearest integer before lookup.

Unknowns:
- No significant unknowns; lookup table approach is well-established.

---

## Phase 3: Extended gradient strip with boundary glasses

**Outcome:** A horizontal visualization renders in the style info modal showing: a gradient band extended ~3 SRM units beyond the style's min and max, two beer glass SVGs positioned at the actual min and max, and reduced opacity (e.g. 30%) on the portions outside the glasses. When SRM is null, the section is hidden.

**Visual layout (left to right):**
```
[faded grad] [glass srm_min] [--gradient--] [glass srm_max] [faded grad]
                SRM 9                              SRM 12
```

Tasks:
- [x] Compute display bounds: `displayMin = max(1, srm_min - padding)`, `displayMax = min(40, srm_max + padding)` where padding = `max(3, Math.round((srm_max - srm_min) * 0.3))`.
- [x] Build the gradient strip as a CSS `linear-gradient` on a `<div>`, sampling `srmToHex()` at each integer step across the display range.
- [x] Overlay two reduced-opacity mask `<div>`s covering the out-of-bounds left and right portions (position absolute, `opacity-30`, same background color as the strip at that edge — or a dark/light neutral).
- [x] Position two beer glass SVGs at the min/max points using percentage-based `left` offsets computed from the display range.
- [x] Glass SVG: simple pint glass silhouette (tapered body, foam top, highlight stripe). Fill = `srmToHex(srm_min/max)`. Size: ~32px wide × 64px tall.
- [x] Labels below each glass: "SRM {value}". Use `text-amber-700 dark:text-amber-400`.
- [x] When only one SRM value is set, render a flat bar in that value's colour, with the min glass when only `srm_min` is set and no glass when only `srm_max` is set (see TASK-57). When both are null, hide the section with `x-show`.
- [x] [RISK] Confirm SVG `fill` attribute binding works in Alpine before committing to the full layout.

Unknowns:
- [RISK] Alpine scope inside the style info bottom sheet: the sheet uses a separate `styleInfo` Alpine object. SRM values are on the entry, not in `styleInfo`. Verify whether `entry` is accessible in the modal template scope, or whether srm_min/srm_max need to be added to the `styleInfo` object when `openStyleInfo()` is called.
- Padding formula: fixed ±3 SRM may be too narrow for wide-range styles (SRM 2–30) and too wide for narrow ones (SRM 11–12). The proportional fallback (`max(3, 30% of range)`) handles this but needs smoke-testing.
- Out-of-bounds masking: semi-transparent overlay vs. reduced opacity on the gradient itself. Overlay div is simpler; opacity on the gradient requires splitting it into three CSS segments.

---

## Phase 4: Integrate into the style info modal

**Outcome:** The style info bottom sheet in `judging/show.blade.php` gains a "Color" section above the text content, hidden when both SRM values are null.

Tasks:
- [x] Locate the style info modal template and add the visualization component above the existing description/history/style-comparison sections.
- [x] Run `npm run build` and smoke-test on at least two styles: one with a narrow SRM range and one with a wide range.
- [x] Verify dark mode: gradient colors are inherently dark-mode-safe (they're literal beer colors); labels and glass outlines need `dark:` variants.

Unknowns:
- None beyond what Phase 3 surfaces.

---

## Open decisions

- [x] **Visualization approach:** Extended gradient strip with two boundary glasses and reduced opacity outside the range. (Decided 2026-06-19)
- [x] **Layout placement:** Modal only. (Decided 2026-06-19)
- [x] **SRM lookup table vs Morey equation:** Lookup table. (Decided in brainstorm)
- [x] **Single value display:** When only one of `srm_min` / `srm_max` is set, the section draws a full-width flat bar in that value's colour (`judging.js:301`), with no gradient and no white masks (both need `hasBoth`). One glass is drawn, labelled with the value. This works for a min-only style. A max-only style draws no glass, because the glass is tied to `srmMin !== null` (`show.blade.php:432`) and the max glass needs `hasBoth` (`show.blade.php:441`); tracked in a separate task. No seeded style is min-only or max-only.
- [ ] **Padding formula:** Confirm `max(3, round(range * 0.3))` feels right after smoke-testing. The formula is applied at `judging.js:279`, then the pad is clamped to SRM 1-40 and equalised to the smaller side at `judging.js:280-283`, so near the ends of the scale the visible padding is smaller. Whether it feels right is still Oscar's smoke test.
