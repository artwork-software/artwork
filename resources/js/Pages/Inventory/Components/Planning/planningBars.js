// Shared constants + helpers for the planning bar overlay.
// Extracted from ArticleRow.vue (F4) so they're constructed ONCE per page
// load instead of once per article row.

// Distinguishable, accessible color palette — red is reserved for overbookings.
export const BAR_PALETTE = [
    '#0ea5e9', // sky
    '#22c55e', // green
    '#a855f7', // purple
    '#f59e0b', // amber
    '#14b8a6', // teal
    '#8b5cf6', // violet
    '#0891b2', // cyan
    '#65a30d', // lime
    '#db2777', // pink
    '#ea580c', // orange
    '#4338ca', // indigo
    '#0d9488', // teal-dark
];

// Layout constants for the bar overlay.
export const CELL_WIDTH = 96;          // px, matches Tailwind w-24
export const BAR_HEIGHT = 5;
export const BAR_GAP = 2;
export const MAX_VISIBLE_LANES = 4;
export const BAR_OVERLAY_HEIGHT = MAX_VISIBLE_LANES * (BAR_HEIGHT + BAR_GAP);
export const ROW_PADDING = BAR_OVERLAY_HEIGHT + 4;

/** Deterministic 32-bit hash of an issue id (number or string). */
export function hashIssueId(id) {
    let h = 0;
    const s = String(id);
    for (let i = 0; i < s.length; i++) {
        h = (h * 31 + s.charCodeAt(i)) >>> 0;
    }
    return h;
}

export function colorForIssue(issue) {
    return BAR_PALETTE[hashIssueId(issue.id) % BAR_PALETTE.length];
}
