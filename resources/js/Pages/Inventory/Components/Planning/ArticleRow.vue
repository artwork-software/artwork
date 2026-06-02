<template>
    <div
        class="flex border-b border-zinc-200 relative article-row-cv"
        :style="{ paddingBottom: `${ROW_PADDING}px` }"
    >
        <div
            class="sticky left-0 z-20 bg-white px-4 py-2 text-xs text-zinc-900 font-medium border-r border-zinc-200 w-[220px] min-w-[220px]"
        >
            {{ article.name }}
        </div>
        <div
            v-for="(date, idx) in dates"
            :key="date.date"
            @click="onCellClick(date.date)"
            class="text-xs px-2 py-2 text-center border-r border-zinc-200 min-w-24 max-w-24 w-24 flex items-center justify-center cursor-pointer transition relative"
            :class="[
                cellValue(date.date) < 0
                    ? 'bg-red-100'
                    : (date.isWeekend ? 'bg-zinc-50' : 'bg-white'),
                isToday(date.date)
                    ? 'ring-1 ring-indigo-300 ring-inset'
                    : (cellValue(date.date) < 0 ? 'hover:bg-red-200' : 'hover:bg-zinc-50')
            ]"
        >
            <div class="inline-flex items-center gap-1">
                <span
                    class="tabular-nums"
                    :class="{ 'text-red-800 font-semibold': cellValue(date.date) < 0 }"
                >
                    {{ cellValue(date.date) }}
                </span>
                <IconRouteSquare
                    v-if="usedDates.has(date.date)"
                    class="size-3 text-zinc-500"
                />
            </div>
            <!-- Per-cell overflow badge (issues in hidden lanes) -->
            <span
                v-if="overflowByCol[idx] > 0"
                class="absolute bottom-0.5 right-1 inline-flex items-center justify-center h-3 min-w-[14px] px-1 rounded-sm bg-zinc-800 text-white text-[9px] font-semibold pointer-events-none"
                :title="$t('More issues in this cell')"
            >+{{ overflowByCol[idx] }}</span>
        </div>

        <!-- Bars overlay -->
        <div
            class="absolute pointer-events-none"
            :style="{
                left: '220px',
                right: '0',
                bottom: '2px',
                height: `${BAR_OVERLAY_HEIGHT}px`,
            }"
        >
            <button
                v-for="bar in visibleBars"
                :key="bar.key"
                type="button"
                class="absolute pointer-events-auto rounded-sm transition-opacity focus:outline-none focus:ring-2 focus:ring-indigo-400"
                :style="barStyle(bar)"
                :class="[
                    isHighlighted(bar.issue) ? '' : 'opacity-30',
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
import { IconRouteSquare } from '@tabler/icons-vue';
import {
    CELL_WIDTH,
    BAR_HEIGHT,
    BAR_GAP,
    MAX_VISIBLE_LANES,
    BAR_OVERLAY_HEIGHT,
    ROW_PADDING,
    colorForIssue,
} from './planningBars.js';

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

// B7: usedFlag derived from issues instead of shipped from backend.
// Returns the set of visible date strings on which this article is used.
const usedDates = computed(() => {
    const set = new Set();
    for (const bar of clampedIssues.value) {
        for (let i = bar.startIdx; i <= bar.endIdx; i++) {
            set.add(props.dates[i].date);
        }
    }
    return set;
});

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

const visibleBars = computed(() => laneAssignments.value
    .filter((bar) => bar.lane < MAX_VISIBLE_LANES)
    .map((bar) => ({
        ...bar,
        key: `bar-${bar.issue.id}`,
        color: colorForIssue(bar.issue),
    }))
);

// Per-column count of bars that would be hidden (lane >= MAX_VISIBLE_LANES)
const overflowByCol = computed(() => {
    const counts = new Array(props.dates.length).fill(0);
    for (const bar of laneAssignments.value) {
        if (bar.lane < MAX_VISIBLE_LANES) continue;
        for (let i = bar.startIdx; i <= bar.endIdx; i++) {
            counts[i] += 1;
        }
    }
    return counts;
});

const barStyle = (bar) => {
    const left = bar.startIdx * CELL_WIDTH + 2;
    const width = (bar.endIdx - bar.startIdx + 1) * CELL_WIDTH - 4;
    const top = bar.lane * (BAR_HEIGHT + BAR_GAP);

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
