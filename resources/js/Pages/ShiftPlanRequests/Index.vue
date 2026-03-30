<template>
    <AppLayout :title="$t('Shift plan approvals')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        {{ $t('Shift plan requests') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 max-w-2xl">
                        {{ $t('Here you can see all shift plan approval requests grouped by craft.') }}
                    </p>
                </div>
            </div>

            <!-- Kein Craft / keine Requests -->
            <div
                v-if="!crafts.length"
                class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center"
            >
                <p class="text-sm text-gray-500">
                    {{ $t('No crafts available.') }}
                </p>
            </div>

            <!-- Cards pro Craft -->
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="craft in crafts"
                    :key="craft.id"
                    class="group flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm hover:border-indigo-200 hover:shadow-md transition"
                >
                    <!-- Craft Header -->
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-white to-gray-50 rounded-t-2xl"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold text-white shadow-sm"
                                :style="{ backgroundColor: craft.color || '#4f46e5' }"
                            >
                                {{ craft.abbreviation }}
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">
                                    {{ craft.name }}
                                </h2>
                                <p class="text-xs text-gray-500">
                                    <span v-if="craft.assignable_by_all">
                                        {{ $t('Assignable by all planners') }}
                                    </span>
                                    <span v-else>
                                        {{ $t('Restricted assignment') }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600"
                            >
                                <IconCalendarWeek class="h-4 w-4 mr-1" />
                                {{ $t('Requests') }}: {{ craft.shift_plan_requests.length }}
                            </span>
                        </div>
                    </div>

                    <!-- Requests-Liste -->
                    <div class="flex-1">
                        <div v-if="craft.shift_plan_requests.length" class="max-h-[500px] overflow-y-auto">
                            <div class="divide-y divide-gray-100">
                                <template v-for="(item, idx) in requestsWithTodayLine(craft)" :key="idx">
                                    <!-- Heute-Linie -->
                                    <div
                                        v-if="item.type === 'today-line'"
                                        class="flex items-center gap-2 px-4 py-1"
                                    >
                                        <div class="flex-1 border-t border-rose-300/60"></div>
                                        <span class="text-[10px] font-medium text-rose-400 uppercase tracking-wide">{{ $t('Today') }}</span>
                                        <div class="flex-1 border-t border-rose-300/60"></div>
                                    </div>

                                    <!-- Request-Eintrag -->
                                    <button
                                        v-else
                                        type="button"
                                        class="w-full text-left px-4 py-3 flex items-center justify-between hover:bg-indigo-50/60 transition"
                                        @click="goToRequest(item.data.id)"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        KW {{ item.data.week_number }} / {{ item.data.year }}
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium',
                                                            statusClasses(item.data.status)
                                                        ]"
                                                    >
                                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                                                        {{ $t(item.data.status) }}
                                                    </span>
                                                </div>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    {{ $t('Requested at') }}:
                                                    {{ formatDateTime(item.data.requested_at) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-400">
                                                {{ $t('Details') }}
                                            </span>
                                            <IconChevronRight class="h-4 w-4 text-gray-400 group-hover:text-indigo-500" />
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Keine Requests fuer dieses Craft -->
                        <div
                            v-else
                            class="px-4 py-6 text-center text-xs text-gray-400"
                        >
                            {{ $t('No shift plan requests for this craft yet.') }}
                        </div>

                        <!-- Vergangene Genehmigungen -->
                        <div v-if="craft.past_approved_count > 0" class="border-t border-gray-100 px-4 py-2">
                            <button
                                type="button"
                                class="text-xs font-medium text-gray-500 hover:text-indigo-600 transition"
                                @click="togglePastSection(craft.id, 'approved')"
                            >
                                {{ $t('Past approvals') }} ({{ craft.past_approved_count }})
                            </button>
                            <div v-if="pastSections[craft.id]?.approved?.open">
                                <button
                                    v-for="req in (pastRequests[craft.id]?.approved || [])"
                                    :key="req.id"
                                    type="button"
                                    class="w-full text-left px-2 py-2 flex items-center justify-between hover:bg-indigo-50/60 transition rounded"
                                    @click="goToRequest(req.id)"
                                >
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900">
                                                KW {{ req.week_number }} / {{ req.year }}
                                            </span>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                                                {{ $t('approved') }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $t('Requested at') }}: {{ formatDateTime(req.requested_at) }}
                                        </p>
                                    </div>
                                    <IconChevronRight class="h-4 w-4 text-gray-400" />
                                </button>
                                <button
                                    v-if="hasMorePast(craft.id, 'approved', craft.past_approved_count)"
                                    type="button"
                                    class="mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition"
                                    @click="loadMorePast(craft.id, 'approved')"
                                >
                                    {{ $t('Load more') }}
                                </button>
                            </div>
                        </div>

                        <!-- Vergangene Ablehnungen -->
                        <div v-if="craft.past_rejected_count > 0" class="border-t border-gray-100 px-4 py-2">
                            <button
                                type="button"
                                class="text-xs font-medium text-gray-500 hover:text-indigo-600 transition"
                                @click="togglePastSection(craft.id, 'rejected')"
                            >
                                {{ $t('Past rejections') }} ({{ craft.past_rejected_count }})
                            </button>
                            <div v-if="pastSections[craft.id]?.rejected?.open">
                                <button
                                    v-for="req in (pastRequests[craft.id]?.rejected || [])"
                                    :key="req.id"
                                    type="button"
                                    class="w-full text-left px-2 py-2 flex items-center justify-between hover:bg-indigo-50/60 transition rounded"
                                    @click="goToRequest(req.id)"
                                >
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900">
                                                KW {{ req.week_number }} / {{ req.year }}
                                            </span>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200">
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                                                {{ $t('rejected') }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $t('Requested at') }}: {{ formatDateTime(req.requested_at) }}
                                        </p>
                                    </div>
                                    <IconChevronRight class="h-4 w-4 text-gray-400" />
                                </button>
                                <button
                                    v-if="hasMorePast(craft.id, 'rejected', craft.past_rejected_count)"
                                    type="button"
                                    class="mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition"
                                    @click="loadMorePast(craft.id, 'rejected')"
                                >
                                    {{ $t('Load more') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import { reactive } from "vue";
import axios from "axios";
import {
    IconCalendarWeek,
    IconChevronRight,
} from "@tabler/icons-vue";

const props = defineProps({
    crafts: {
        type: Array,
        required: true,
    },
});

// ISO Wochennummer berechnen
const getISOWeekAndYear = (date) => {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil(((d - yearStart) / 86400000 + 1) / 7);
    return { week: weekNo, year: d.getUTCFullYear() };
};

const { week: currentWeek, year: currentYear } = getISOWeekAndYear(new Date());

// Requests mit Heute-Linie (Sortierung: Zukunft oben → Vergangenheit unten)
const requestsWithTodayLine = (craft) => {
    const requests = craft.shift_plan_requests;
    if (!requests.length) return [];

    const isFuture = (r) => r.year > currentYear || (r.year === currentYear && r.week_number > currentWeek);

    const result = [];
    let lineInserted = false;

    for (let i = 0; i < requests.length; i++) {
        const req = requests[i];
        const nextReq = requests[i + 1];

        result.push({ type: 'request', data: req });

        // Linie einfügen wenn aktueller Request in Zukunft und nächster in Vergangenheit/Gegenwart
        if (!lineInserted && isFuture(req) && nextReq && !isFuture(nextReq)) {
            result.push({ type: 'today-line' });
            lineInserted = true;
        }
    }

    if (!lineInserted) {
        // Alle in der Vergangenheit → Linie ganz oben
        if (!isFuture(requests[0])) {
            result.unshift({ type: 'today-line' });
        }
        // Alle in der Zukunft → Linie ganz unten
        if (isFuture(requests[requests.length - 1])) {
            result.push({ type: 'today-line' });
        }
    }

    return result;
};

// Vergangene Anfragen: Toggle + Lazy-Load
const pastSections = reactive({});
const pastRequests = reactive({});

const togglePastSection = (craftId, status) => {
    if (!pastSections[craftId]) {
        pastSections[craftId] = {};
    }
    if (!pastSections[craftId][status]) {
        pastSections[craftId][status] = { open: false };
    }

    const section = pastSections[craftId][status];
    section.open = !section.open;

    if (section.open && !pastRequests[craftId]?.[status]?.length) {
        loadPastRequests(craftId, status, 0);
    }
};

const loadPastRequests = async (craftId, status, offset) => {
    const { data } = await axios.get(route('shifts.approvals.past-requests', { craft: craftId }), {
        params: { status, offset },
    });

    if (!pastRequests[craftId]) {
        pastRequests[craftId] = {};
    }
    if (!pastRequests[craftId][status]) {
        pastRequests[craftId][status] = [];
    }

    if (offset === 0) {
        pastRequests[craftId][status] = data.requests;
    } else {
        pastRequests[craftId][status].push(...data.requests);
    }
};

const loadMorePast = (craftId, status) => {
    const currentCount = pastRequests[craftId]?.[status]?.length || 0;
    loadPastRequests(craftId, status, currentCount);
};

const hasMorePast = (craftId, status, totalCount) => {
    const loaded = pastRequests[craftId]?.[status]?.length || 0;
    return loaded < totalCount;
};

// Status -> Tailwind-Klassen
const statusClasses = (status) => {
    switch (status) {
        case "pending":
            return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
        case "approved":
        case "accepted":
            return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
        case "rejected":
        case "denied":
            return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
        default:
            return "bg-gray-100 text-gray-600";
    }
};

const formatDateTime = (value) => {
    if (!value) return "-";
    const date = new Date(value);
    return date.toLocaleString(undefined, {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const goToRequest = (id) => {
    router.get(route("shift-plan-requests.show", id));
};
</script>
