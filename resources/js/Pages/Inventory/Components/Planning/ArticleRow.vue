<template>
    <div
        class="flex border-b border-border-subtle relative article-row-cv"
    >
        <div
            class="sticky left-0 z-20 bg-white px-4 py-2 text-xs text-text font-medium border-r border-border-subtle w-[220px] min-w-[220px]"
        >
            {{ article.name }}
        </div>
        <div
            v-for="(date, idx) in dates"
            :key="date.date"
            @click="onCellClick(date.date)"
            :style="cellBarStyle"
            class="text-xs px-2 pt-2 text-center border-r border-border-subtle min-w-24 max-w-24 w-24 flex items-center justify-center cursor-pointer transition relative"
            :class="[ cellValue(date.date) < 0
                    ? 'bg-danger-surface'
                    : (date.isWeekend ? 'bg-surface-sunken' : 'bg-white'),
                isToday(date.date)
                    ? 'ring-1 ring-accent-200 ring-inset'
                    : (cellValue(date.date) < 0 ? 'hover:bg-danger-surface' : 'hover:bg-surface-sunken'),
                isWeekStart(date.date) ? 'border-l-2 border-l-zinc-800' : ''
            ]"
        >
            <div class="inline-flex items-center gap-1">
                <span
                    class="tabular-nums"
                    :class="{ 'text-danger font-semibold': cellValue(date.date) < 0 }"
                >
                    {{ cellValue(date.date) }}
                </span>
            </div>
        </div>

        <!-- Bars overlay — grows with the number of overlapping issues so every
             material issue is always visible (and hoverable), no overflow badge. -->
        <div
            class="absolute pointer-events-none"
            :style="{
                left: '220px',
                right: '0',
                bottom: '2px',
                height: `${barsAreaHeight}px`,
            }"
        >
            <button
                v-for="bar in visibleBars"
                :key="bar.key"
                type="button"
                class="absolute pointer-events-auto rounded-sm transition-opacity focus:outline-none focus:ring-2 focus:ring-accent-600"
                :style="barStyle(bar)"
                :class="[ isHighlighted(bar.issue) ? '' : 'opacity-30',
                    'hover:brightness-110'
                ]"
                @click.stop="onBarClick(bar)"
                @mouseenter="onBarEnter($event, bar.issue)"
                @mouseleave="onBarLeave"
            ></button>
        </div>
    </div>
</template>

<script setup>
import { computed, inject } from 'vue';
import {
    CELL_WIDTH,
    BAR_HEIGHT,
    BAR_GAP,
    colorForIssue,
} from './planningBars.js';

// Vertical distance between two stacked bars (lane stride).
const LANE_STRIDE = BAR_HEIGHT + BAR_GAP;

const props = defineProps({
    article: { type: Object, required: true },
    dates: { type: Array, required: true },
    availability: { type: Object, required: true },
    issuesForArticle: { type: Array, default: () => [] },
    highlightedProjectIds: { type: Array, default: () => [] },
});

const emit = defineEmits(['cellClick', 'barClick', 'barHover', 'barLeave']);

// F4: shared per-page computeds — provided by the parent so they're built once
// rather than once per article row.
const dateIndexMap = inject('planningDateIndex', computed(() => {
    const m = new Map();
    props.dates.forEach((d, idx) => m.set(d.date, idx));
    return m;
}));
const rangeBounds = inject('planningRangeBounds', computed(() => ({
    first: props.dates[0]?.date ?? null,
    last:  props.dates[props.dates.length - 1]?.date ?? null,
})));

// Calendar-week metadata (provided once per page). Used to draw the thicker
// black divider on the first day of each ISO week.
const weekMeta = inject('planningWeekMeta', computed(() => new Map()));
const isWeekStart = (date) => weekMeta.value.get(date)?.isWeekStart ?? false;

// Number of lanes actually used by this article's overlapping issues. The row
// grows to fit them all so no issue is ever hidden behind an overflow badge.
const lanesUsed = computed(() => {
    let max = -1;
    for (const bar of laneAssignments.value) {
        if (bar.lane > max) max = bar.lane;
    }
    return max + 1; // 0 when the article has no issues in range
});

// Height of the reserved bar strip at the bottom of each cell.
const barsAreaHeight = computed(() => lanesUsed.value * LANE_STRIDE);

// The bar strip is reserved *inside* the flex cells (rather than as row padding)
// so the sticky article column stretches over the full row height and bars
// scroll behind it instead of bleeding over the article names. Rows without
// issues keep a compact, symmetric padding.
const cellBarStyle = computed(() => ({
    paddingBottom: lanesUsed.value > 0 ? `${barsAreaHeight.value + 4}px` : '8px',
}));

const clampedIssues = computed(() => {
    const { first, last } = rangeBounds.value;
    if (!first || !last) return [];

    return (props.issuesForArticle || [])
        .map((issue) => {
            const start = issue.start < first ? first : issue.start;
            const end = issue.end > last ? last : issue.end;
            if (end < first || start > last) return null;
            const startIdx = dateIndexMap.value.get(start);
            const endIdx = dateIndexMap.value.get(end);
            if (startIdx === undefined || endIdx === undefined) return null;
            return { issue, startIdx, endIdx };
        })
        .filter(Boolean)
        .sort((a, b) => a.startIdx - b.startIdx || a.issue.id - b.issue.id);
});

// F6: stable handler closures so the template doesn't allocate new functions
// on every render.
const onCellClick = (date) => emit('cellClick', { articleId: props.article.id, date });
const onBarClick = (bar) => emit('barClick', {
    articleId: props.article.id,
    issue: bar.issue,
    date: bar.issue.start,
});
const onBarEnter = (event, issue) => emit('barHover', { event, issue });
const onBarLeave = () => emit('barLeave');

// B7: cell value from compact availability payload.
// `availability` shape: { base: { articleId: int }, deltas: { date: { articleId: int }}}
const baseValue = computed(() => props.availability?.base?.[props.article.id] ?? 0);
const cellValue = (date) => {
    const delta = props.availability?.deltas?.[date]?.[props.article.id];
    if (delta !== undefined) return delta;
    return baseValue.value;
};

// Greedy lane assignment so overlapping issues stack vertically
const laneAssignments = computed(() => {
    const lanes = []; // lanes[i] = endIdx of last assigned issue in lane i
    return clampedIssues.value.map((bar) => {
        let lane = lanes.findIndex((endIdx) => endIdx < bar.startIdx);
        if (lane === -1) {
            lane = lanes.length;
            lanes.push(bar.endIdx);
        } else {
            lanes[lane] = bar.endIdx;
        }
        return { ...bar, lane };
    });
});

// All bars are rendered — the row height (barsAreaHeight) is sized to fit them.
const visibleBars = computed(() => laneAssignments.value
    .map((bar) => ({
        ...bar,
        key: `bar-${bar.issue.id}`,
        color: colorForIssue(bar.issue),
    }))
);

const barStyle = (bar) => {
    const left = bar.startIdx * CELL_WIDTH + 2;
    const width = (bar.endIdx - bar.startIdx + 1) * CELL_WIDTH - 4;
    const top = bar.lane * LANE_STRIDE;

    const base = {
        left: `${left}px`,
        width: `${width}px`,
        top: `${top}px`,
        height: `${BAR_HEIGHT}px`,
    };

    if (bar.issue.type === 'extern') {
        const lighter = bar.color + '66';
        base.backgroundImage = `repeating-linear-gradient(45deg, ${bar.color} 0, ${bar.color} 3px, ${lighter} 3px, ${lighter} 6px)`;
    } else {
        base.backgroundColor = bar.color;
    }

    return base;
};

const isHighlighted = (issue) => {
    if (!props.highlightedProjectIds || props.highlightedProjectIds.length === 0) {
        return true;
    }
    return issue.project_id !== null && props.highlightedProjectIds.includes(issue.project_id);
};

// F5: compute today's ISO once, then compare strings per cell.
const _today = new Date();
const todayIso = `${_today.getFullYear()}-${String(_today.getMonth() + 1).padStart(2, '0')}-${String(_today.getDate()).padStart(2, '0')}`;
const isToday = (date) => date === todayIso;
</script>

<style scoped>
/* F1: Let the browser skip paint/layout for rows that are off-screen.
   `contain-intrinsic-size` reserves the row's slot so the scrollbar
   stays stable and the layout doesn't jump as rows come in/out of view. */
.article-row-cv {
    content-visibility: auto;
    contain-intrinsic-size: auto 60px;
}
</style>
