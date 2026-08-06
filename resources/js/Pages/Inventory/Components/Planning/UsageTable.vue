<template>
    <div v-if="issues && issues.length" class="space-y-4">
        <div
            v-for="issue in issues"
            :key="issue.id"
            :ref="(el) => registerIssueRef(issue.id, el)"
            class="rounded-lg border p-4 shadow-sm bg-white flex justify-between transition-colors"
            :class="highlightIssueId === issue.id ? 'border-accent-600 ring-2 ring-accent-200' : 'border-border'"
        >
            <div>
                <div class="font-medium text-text">{{ issue.name }}</div>
                <div class="text-xs text-text-subtle">
                    <span>{{ splitDateTime(issue.start_date_time).date }}</span><span
                        v-if="splitDateTime(issue.start_date_time).time"
                        class="ml-1"
                        :class="timeClass(issue, 'start')"
                    >{{ splitDateTime(issue.start_date_time).time }}</span>
                    –
                    <span>{{ splitDateTime(issue.end_date_time).date }}</span><span
                        v-if="splitDateTime(issue.end_date_time).time"
                        class="ml-1"
                        :class="timeClass(issue, 'end')"
                    >{{ splitDateTime(issue.end_date_time).time }}</span>
                </div>

                <ul class="mt-2 divide-y text-sm text-text-muted">
                    <li
                        v-for="article in issue.articles"
                        :key="article.id"
                        class="flex justify-between py-1"
                    >
                        <span>{{ article.name }}</span>
                        <span class="flex items-center gap-2">
                            <span>{{ article.pivot.quantity }} {{ $t('Stk') }}</span>
                            <span
                                v-if="editingIssueId && issue.id === editingIssueId && editingArticleQuantity != null"
                                class="inline-flex items-center rounded-md bg-warning-surface px-1.5 py-0.5 text-[11px] font-medium text-warning ring-1 ring-inset ring-warning-border"
                            >
                                {{ $t('Current adjustment') }}: {{ editingArticleQuantity }} {{ $t('Stk') }}
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
            <div>
                <BaseUIButton size="sm" hide-icon @click="openMaterialIssueModal(issue.id)" type="button" :disabled="loadingIssueId === issue.id">
                    <IconLoader2 v-if="loadingIssueId === issue.id" class="size-4 animate-spin" />
                    <IconEdit v-else class="size-4" />
                    {{ $t('Edit')}}
                </BaseUIButton>
            </div>
        </div>
    </div>
    <div v-else class="text-sm text-text-subtle italic">
        {{ $t('No data available') }}
    </div>


    <IssueOfMaterialModal
        v-if="showIssueOfMaterialModal"
        @close="closeIssueOfMaterialModal"
        @saved="handleDataSaved"
        :issue-of-material="extern ? null : issueForModal"
        :is-extern-or-intern="extern"
        :extern-material-issue="extern ? issueForModal : null"
        :project="issueForModal?.project || null"
        :planning-date="props.planningDate"
    />
</template>

<script setup>

import { IconEdit, IconLoader2 } from '@tabler/icons-vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import {defineAsyncComponent, ref, watch, nextTick, computed} from 'vue';
import axios from 'axios';

const props = defineProps({
    issues: {
        type: Array,
        default: () => []
    },
    extern: {
        type: Boolean,
        default: false
    },
    editingIssueId: {
        type: Number,
        default: null
    },
    editingArticleQuantity: {
        type: Number,
        default: null
    },
    planningDate: {
        type: String,
        default: null
    },
    highlightIssueId: {
        type: Number,
        default: null
    }
});

const issueRefs = new Map();
const registerIssueRef = (id, el) => {
    if (el) {
        issueRefs.set(id, el);
    } else {
        issueRefs.delete(id);
    }
};

watch(() => props.highlightIssueId, async (id) => {
    if (!id) return;
    await nextTick();
    const el = issueRefs.get(id);
    if (el && typeof el.scrollIntoView === 'function') {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}, { immediate: true });

const emit = defineEmits(['dataChanged', 'quantityUpdated']);

// Split a combined "<date> HH:mm" string (e.g. "29. Mai 2026 10:00") into its
// date and time parts so the time can be highlighted independently.
const splitDateTime = (value) => {
    if (!value) return { date: '', time: '' };
    const match = String(value).match(/^(.*?)\s+(\d{1,2}:\d{2})$/);
    if (match) {
        return { date: match[1], time: match[2] };
    }
    return { date: String(value), time: '' };
};

// A boundary time is "relevant" when it deviates from the full-day default
// (00:00 for start, 23:59 for end) — i.e. when it actually constrains the booking.
const isTimeRelevant = (time, bound) => {
    if (!time) return false;
    const fullDayDefault = bound === 'end' ? '23:59' : '00:00';
    return time !== fullDayDefault;
};

// Day key for a boundary (prefer the raw Y-m-d field, fall back to the formatted date).
const issueDayKey = (issue, bound) => {
    const raw = bound === 'end'
        ? (issue.end_date ?? issue.return_date)
        : (issue.start_date ?? issue.issue_date);
    if (raw) return String(raw).slice(0, 10);
    return splitDateTime(bound === 'end' ? issue.end_date_time : issue.start_date_time).date;
};

// Map: day → set of distinct issue ids that start/end on that day at a relevant time.
const relevantIssuesByDay = computed(() => {
    const map = {};
    for (const issue of props.issues || []) {
        const start = splitDateTime(issue.start_date_time);
        if (isTimeRelevant(start.time, 'start')) {
            (map[issueDayKey(issue, 'start')] ||= new Set()).add(issue.id);
        }
        const end = splitDateTime(issue.end_date_time);
        if (isTimeRelevant(end.time, 'end')) {
            (map[issueDayKey(issue, 'end')] ||= new Set()).add(issue.id);
        }
    }
    return map;
});

// Convert a "HH:mm[:ss]" time string to minutes since midnight.
const toMinutes = (time, fallback) => {
    if (!time) return fallback;
    const parts = String(time).split(':');
    if (parts.length < 2) return fallback;
    const value = (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
    return Math.max(0, Math.min(1440, value));
};

// The booking's active time window on a given day. Boundary days respect the
// booking's times; interior days (and missing times) span the full day.
const windowOnDay = (issue, dayKey) => {
    const startDay = (issue.start_date ?? issue.issue_date ?? '').slice(0, 10) || null;
    const endDay = ((issue.end_date ?? issue.return_date) ?? startDay)?.slice(0, 10) || null;
    if (!startDay || dayKey < startDay || dayKey > endDay) return null;
    const startMin = (dayKey === startDay) ? toMinutes(issue.start_time, 0) : 0;
    const endMin = (dayKey === endDay) ? toMinutes(issue.end_time, 1440) : 1440;
    return [startMin, endMin];
};

// Colour a boundary time only when its own time is relevant AND at least two
// distinct bookings have a relevant time on that same day. Then:
//   red  = its time window actually overlaps another booking that day (collision)
//   blue = the windows are time-separated on that day (mutually exclusive)
const timeClass = (issue, bound) => {
    const info = splitDateTime(bound === 'end' ? issue.end_date_time : issue.start_date_time);
    if (!isTimeRelevant(info.time, bound)) return '';

    const dayKey = issueDayKey(issue, bound);
    const sameDayRelevant = relevantIssuesByDay.value[dayKey];
    if (!sameDayRelevant || sameDayRelevant.size < 2) return '';

    const ownWindow = windowOnDay(issue, dayKey);
    if (!ownWindow) return '';

    let overlaps = false;
    for (const other of props.issues || []) {
        if (other.id === issue.id || !sameDayRelevant.has(other.id)) continue;
        const otherWindow = windowOnDay(other, dayKey);
        if (!otherWindow) continue;
        // Half-open overlap: touching endpoints (e.g. until 10:00 / from 10:00) do NOT collide.
        if (ownWindow[0] < otherWindow[1] && otherWindow[0] < ownWindow[1]) {
            overlaps = true;
            break;
        }
    }

    return overlaps ? 'italic text-danger' : 'italic text-accent-600';
};


const showIssueOfMaterialModal = ref(false);
const issueForModal = ref(null);
const loadingIssueId = ref(null);

const IssueOfMaterialModal = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue'),
    delay: 200,
})
const openMaterialIssueModal = async (issueId) => {
    const localIssue = props.issues.find(issue => issue.id === issueId) || null;

    if (!props.extern) {
        loadingIssueId.value = issueId;
        try {
            const response = await axios.get(route('issue-of-material.show', issueId));
            issueForModal.value = response.data;
        } catch (error) {
            console.error('Failed to fetch full issue data, using local fallback:', error);
            issueForModal.value = localIssue;
        } finally {
            loadingIssueId.value = null;
        }
    } else {
        issueForModal.value = localIssue;
    }

    showIssueOfMaterialModal.value = true;
};

const closeIssueOfMaterialModal = () => {
    showIssueOfMaterialModal.value = false;
    issueForModal.value = null;
    // Emit event to trigger data refresh in parent components
    emit('dataChanged');
};

const handleDataSaved = (quantityData) => {
    // Emit event with quantity data to directly update quantities in parent modal
    if (quantityData && quantityData.updatedArticles) {
        emit('quantityUpdated', quantityData);
    }
    // Also emit general data changed event for fallback
    emit('dataChanged');
};
</script>

<style scoped>

</style>
