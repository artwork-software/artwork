<template>
    <AppLayout :title="$t('BI Dashboard')">
        <div class="artwork-container space-y-6">
            <ToolbarHeader
                :icon="IconChartHistogram"
                :title="$t('BI Dashboard')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
                :description="$t('Business intelligence overview')"
                :search-enabled="false"
            >
                <template #actions>
                    <div class="flex items-end gap-3">
                        <BaseInput type="date" id="bi_dash_from" v-model="dateFrom" :label="$t('From')" class="w-40" />
                        <BaseInput type="date" id="bi_dash_to" v-model="dateTo" :label="$t('To')" class="w-40" />
                        <BaseUIButton :label="$t('Apply')" @click="reload" :disabled="loading" hide-icon />
                    </div>
                </template>
            </ToolbarHeader>

            <!-- Onboarding hint: no tag linked to event types -->
            <div v-if="!tagsLinked" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-center justify-between gap-4">
                <span>{{ $t('No BI tags are linked to event types yet. Performances and event days will stay at zero until you assign them.') }}</span>
                <Link :href="route('event_types.management')" class="shrink-0 font-medium text-amber-900 hover:underline">
                    {{ $t('Configure BI tags') }}
                </Link>
            </div>

            <!-- KPI tiles -->
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                <div v-for="kpi in kpiTiles" :key="kpi.key" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                    <p class="text-xs text-gray-500">{{ $t(kpi.label) }}</p>
                    <p class="text-xl font-semibold text-gray-900 mt-1">{{ kpi.value }}</p>
                    <p v-if="kpi.delta !== null" :class="['text-xs mt-1', kpi.delta >= 0 ? 'text-emerald-600' : 'text-rose-600']">
                        {{ kpi.delta >= 0 ? '▲' : '▼' }} {{ Math.abs(kpi.delta).toFixed(1) }} % {{ $t('vs. previous year') }}
                    </p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('Revenue by category') }}</h4>
                    <BiChart v-if="hasCategoryData" type="doughnut" :data="revenueChart" />
                    <p v-else class="text-sm text-gray-400 py-8 text-center">{{ $t('No data available.') }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('Visitors by category') }}</h4>
                    <BiChart v-if="hasCategoryData" type="bar" :data="visitorsChart" />
                    <p v-else class="text-sm text-gray-400 py-8 text-center">{{ $t('No data available.') }}</p>
                </div>
            </div>

            <!-- Drilldown table -->
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('Internal steering (effort vs. output)') }}</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                                <th v-for="col in columns" :key="col.key" class="px-3 py-2 cursor-pointer whitespace-nowrap" @click="sortBy(col.key)">
                                    {{ $t(col.label) }}
                                    <span v-if="sortKey === col.key">{{ sortAsc ? '▲' : '▼' }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in sortedProjects" :key="row.project_id" class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-3 py-2">
                                    <Link :href="route('projects.tab', { project: row.project_id, projectTab: firstProjectTabId })" class="text-indigo-600 hover:underline">
                                        {{ row.project_name }}
                                    </Link>
                                </td>
                                <td class="px-3 py-2 text-gray-600">{{ row.category || '—' }}</td>
                                <td class="px-3 py-2">{{ formatInt(row.visitors) }}</td>
                                <td class="px-3 py-2">{{ formatCurrency(row.revenue) }}</td>
                                <td class="px-3 py-2">{{ row.occupancy !== null ? row.occupancy.toFixed(1) + ' %' : '—' }}</td>
                                <td class="px-3 py-2">{{ row.performances }}</td>
                                <td class="px-3 py-2">{{ row.contracts_per_performance ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.bookings_per_performance ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.tasks_docs_per_production }}</td>
                                <td class="px-3 py-2 font-medium">{{ row.effort_score }}</td>
                            </tr>
                            <tr v-if="projects.length === 0">
                                <td :colspan="columns.length" class="px-3 py-8 text-center text-gray-400">{{ $t('No data available.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { IconChartHistogram } from '@tabler/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BiChart from '@/Artwork/Charts/BiChart.vue';

const props = defineProps({
    dashboard: { type: Object, required: true },
    firstProjectTabId: { type: [Number, String], default: null },
});

const firstProjectTabId = computed(() => props.firstProjectTabId);

const kpis = computed(() => props.dashboard.kpis ?? {});
const previousKpis = computed(() => props.dashboard.previous_kpis ?? {});
const byCategory = computed(() => props.dashboard.by_category ?? []);
const projects = computed(() => props.dashboard.projects ?? []);
const tagsLinked = computed(() => props.dashboard.tags_linked !== false);

const dateFrom = ref(props.dashboard.range?.from ?? '');
const dateTo = ref(props.dashboard.range?.to ?? '');
const loading = ref(false);

const numberFmt = new Intl.NumberFormat('de-DE');
const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const formatInt = (v) => numberFmt.format(v ?? 0);
const formatCurrency = (v) => currencyFmt.format(v ?? 0);

const delta = (key) => {
    const prev = previousKpis.value[key];
    const cur = kpis.value[key];
    if (!prev || prev === 0 || cur === null || cur === undefined) return null;
    return (cur - prev) / prev * 100;
};

const kpiTiles = computed(() => [
    { key: 'visitors', label: 'Visitors', value: formatInt(kpis.value.visitors), delta: delta('visitors') },
    { key: 'revenue', label: 'Revenue', value: formatCurrency(kpis.value.revenue), delta: delta('revenue') },
    { key: 'occupancy', label: 'Occupancy rate', value: kpis.value.occupancy !== null && kpis.value.occupancy !== undefined ? kpis.value.occupancy.toFixed(1) + ' %' : '—', delta: delta('occupancy') },
    { key: 'event_days', label: 'Event days', value: formatInt(kpis.value.event_days), delta: delta('event_days') },
    { key: 'performances', label: 'Performances', value: formatInt(kpis.value.performances), delta: delta('performances') },
    { key: 'project_count', label: 'Productions', value: formatInt(kpis.value.project_count), delta: null },
]);

const palette = ['#6366f1', '#22c55e', '#f59e0b', '#ec4899', '#06b6d4', '#a855f7', '#ef4444', '#14b8a6', '#eab308', '#3b82f6'];

const hasCategoryData = computed(() => byCategory.value.length > 0);

const revenueChart = computed(() => ({
    labels: byCategory.value.map(c => c.category),
    datasets: [{
        data: byCategory.value.map(c => c.revenue),
        backgroundColor: byCategory.value.map((_, i) => palette[i % palette.length]),
    }],
}));

const visitorsChart = computed(() => ({
    labels: byCategory.value.map(c => c.category),
    datasets: [{
        label: 'Visitors',
        data: byCategory.value.map(c => c.visitors),
        backgroundColor: '#6366f1',
    }],
}));

const columns = [
    { key: 'project_name', label: 'Production' },
    { key: 'category', label: 'Category (Sector)' },
    { key: 'visitors', label: 'Visitors' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'occupancy', label: 'Occupancy rate' },
    { key: 'performances', label: 'Performances' },
    { key: 'contracts_per_performance', label: 'Contracts / performance' },
    { key: 'bookings_per_performance', label: 'Bookings / performance' },
    { key: 'tasks_docs_per_production', label: 'Tasks + documents' },
    { key: 'effort_score', label: 'Effort score' },
];

const sortKey = ref('effort_score');
const sortAsc = ref(false);

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = true;
    }
};

const sortedProjects = computed(() => {
    const rows = [...projects.value];
    rows.sort((a, b) => {
        const av = a[sortKey.value];
        const bv = b[sortKey.value];
        if (av === null || av === undefined) return 1;
        if (bv === null || bv === undefined) return -1;
        if (typeof av === 'string') return sortAsc.value ? av.localeCompare(bv) : bv.localeCompare(av);
        return sortAsc.value ? av - bv : bv - av;
    });
    return rows;
});

const reload = () => {
    loading.value = true;
    router.get(route('bi.dashboard'), { date_from: dateFrom.value || null, date_to: dateTo.value || null }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { loading.value = false; },
    });
};
</script>
