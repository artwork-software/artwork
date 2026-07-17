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
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex items-center gap-1.5 pb-2">
                            <button
                                v-for="preset in rangePresets"
                                :key="preset.key"
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition"
                                :class="activePreset === preset.key
                                    ? 'border-indigo-600 bg-indigo-600 text-white'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                                @click="applyPreset(preset)"
                            >
                                {{ $t(preset.label) }}
                            </button>
                        </div>
                        <BaseInput type="date" id="bi_dash_from" v-model="dateFrom" :label="$t('From')" class="w-40" />
                        <BaseInput type="date" id="bi_dash_to" v-model="dateTo" :label="$t('To')" class="w-40" />
                        <BaseUIButton :label="$t('Apply')" @click="reload()" :disabled="loading" hide-icon />
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

            <!-- Datenlücken: Projekte ohne erfasste BI-Zahlen -->
            <div v-if="dataGaps.length > 0" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm font-medium text-amber-900 mb-1.5">
                    {{ dataGaps.length }} {{ $t('projects with events but no BI figures — they pull every total towards zero.') }}
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="gap in visibleGaps"
                        :key="gap.project_id"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-full bg-white/80 border border-amber-200 px-2.5 py-0.5 text-xs text-amber-900 hover:bg-white"
                        @click="quickEntryGap = gap"
                    >
                        <IconPencil class="size-3" />
                        {{ gap.project_name }}
                    </button>
                    <button
                        v-if="dataGaps.length > gapLimit"
                        type="button"
                        class="rounded-full px-2.5 py-0.5 text-xs text-amber-700 hover:underline"
                        @click="showAllGaps = !showAllGaps"
                    >
                        {{ showAllGaps ? $t('Show less') : `+${dataGaps.length - gapLimit} ${$t('more')}` }}
                    </button>
                </div>
            </div>

            <BiQuickEntryModal
                v-if="quickEntryGap"
                :project-id="quickEntryGap.project_id"
                :project-name="quickEntryGap.project_name"
                :project-link="route('projects.tab', { project: quickEntryGap.project_id, projectTab: firstProjectTabId })"
                @close="quickEntryGap = null"
                @saved="onQuickEntrySaved"
            />

            <!-- KPI tiles -->
            <div>
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                    <div v-for="kpi in kpiTiles" :key="kpi.key" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                        <p class="text-xs text-gray-500 inline-flex items-center gap-1">
                            {{ $t(kpi.label) }}
                            <ToolTipComponent
                                v-if="kpi.tooltip"
                                direction="bottom"
                                :tooltip-text="kpi.tooltip"
                                icon="IconInfoCircle"
                                icon-size="h-3.5 w-3.5"
                            />
                        </p>
                        <p class="text-xl font-semibold text-gray-900 mt-1">{{ kpi.value }}</p>
                        <p v-if="kpi.delta !== null" :class="['text-xs mt-1', kpi.delta >= 0 ? 'text-emerald-600' : 'text-rose-600']">
                            {{ kpi.delta >= 0 ? '▲' : '▼' }} {{ kpi.deltaText }} {{ $t('vs. previous year') }}
                        </p>
                        <p v-if="kpi.note" class="text-[10px] text-indigo-600 mt-0.5 leading-tight">{{ kpi.note }}</p>
                    </div>
                </div>
                <p v-if="yoyExcludedCount > 0" class="text-xs text-gray-400 mt-1.5">
                    {{ yoyExcludedCount }} {{ $t('project(s) with time-neutral total figures are not part of the year-over-year comparison.') }}
                </p>
            </div>

            <!-- Monatliche Entwicklung -->
            <div v-if="monthlyChart" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('Monthly trend (per-event figures)') }}</h4>
                <BiChart type="bar" :data="monthlyChart" :options="monthlyOptions" height="300px" />
            </div>

            <!-- Charts by category -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('Revenue by category') }}</h4>
                    <BiChart v-if="hasCategoryData" type="doughnut" :data="revenueChart" :options="categoryChartOptions" />
                    <p v-else class="text-sm text-gray-400 py-8 text-center">{{ $t('No data available.') }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('Visitors by category') }}</h4>
                    <BiChart v-if="hasCategoryData" type="bar" :data="visitorsChart" :options="categoryChartOptions" />
                    <p v-else class="text-sm text-gray-400 py-8 text-center">{{ $t('No data available.') }}</p>
                </div>
            </div>

            <!-- Aufwand vs. Ertrag -->
            <div v-if="scatterChart" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h4 class="text-sm font-medium text-gray-700">{{ $t('Effort vs. output') }}</h4>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-rose-500 inline-block"></span>{{ $t('High effort, low output') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-emerald-500 inline-block"></span>{{ $t('Low effort, high output') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-indigo-400 inline-block"></span>{{ $t('High effort, high output') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-gray-400 inline-block"></span>{{ $t('Low effort, low output') }}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mb-3">{{ $t('Compared against the median of all productions in the period. Click a point to open the project.') }}</p>
                <BiChart type="scatter" :data="scatterChart" :options="scatterOptions" height="320px" />
            </div>

            <!-- Drilldown table -->
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h4 class="text-sm font-medium text-gray-700">{{ $t('Internal steering (effort vs. output)') }}</h4>
                    <button
                        v-if="categoryFilter"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 border border-indigo-200 px-3 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                        @click="categoryFilter = null"
                    >
                        {{ $t('Category (Sector)') }}: {{ categoryFilter }}
                        <IconX class="size-3.5" />
                    </button>
                </div>
                <div class="overflow-x-auto max-h-[32rem] overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-white z-10 shadow-[0_1px_0_0_#e5e7eb]">
                            <tr class="text-left text-xs text-gray-500">
                                <th v-for="col in columns" :key="col.key" class="px-3 py-2 cursor-pointer whitespace-nowrap bg-white" @click="sortBy(col.key)">
                                    <span class="inline-flex items-center gap-1">
                                        {{ $t(col.label) }}
                                        <span v-if="sortKey === col.key">{{ sortAsc ? '▲' : '▼' }}</span>
                                        <span v-if="col.key === 'effort_score'" @click.stop>
                                            <ToolTipComponent
                                                direction="left"
                                                :tooltip-text="effortScoreTooltip"
                                                icon="IconInfoCircle"
                                                icon-size="h-3.5 w-3.5"
                                            />
                                        </span>
                                    </span>
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
                                <td class="px-3 py-2">
                                    <span
                                        v-if="row.visitors_estimated"
                                        class="text-indigo-700"
                                        v-tooltip.top="{ value: $t('Estimated from sold tickets'), appendTo: 'body', class: 'aw-tooltip' }"
                                    >≈ {{ formatInt(row.visitors) }}</span>
                                    <template v-else>{{ formatInt(row.visitors) }}</template>
                                </td>
                                <td class="px-3 py-2">{{ formatCurrency(row.revenue) }}</td>
                                <td class="px-3 py-2">
                                    <div v-if="row.occupancy !== null" class="flex items-center gap-2">
                                        <div class="h-1.5 w-14 rounded-full bg-gray-100 overflow-hidden shrink-0">
                                            <div
                                                class="h-full rounded-full"
                                                :class="occupancyBarClass(row.occupancy)"
                                                :style="{ width: Math.min(row.occupancy, 100) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs whitespace-nowrap">{{ formatPercent(row.occupancy) }}</span>
                                    </div>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-3 py-2">{{ row.performances }}</td>
                                <td class="px-3 py-2">{{ row.contracts_per_performance ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.bookings_per_performance ?? '—' }}</td>
                                <td class="px-3 py-2">{{ row.tasks_docs_per_production }}</td>
                                <td class="px-3 py-2 font-medium">{{ row.effort_score }}</td>
                            </tr>
                            <tr v-if="sortedProjects.length === 0">
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
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { IconChartHistogram, IconX, IconPencil } from '@tabler/icons-vue';
import BiQuickEntryModal from '@/Pages/Projects/Components/BiComponents/BiQuickEntryModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BiChart from '@/Artwork/Charts/BiChart.vue';
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    dashboard: { type: Object, required: true },
    firstProjectTabId: { type: [Number, String], default: null },
});

const firstProjectTabId = computed(() => props.firstProjectTabId);

const kpis = computed(() => props.dashboard.kpis ?? {});
// Jahresvergleich läuft über comparable_kpis (gleiches Zeitfenster, gleiche
// Ausschlussregeln wie previous_kpis) — nicht über die Anzeige-KPIs
const comparableKpis = computed(() => props.dashboard.comparable_kpis ?? props.dashboard.kpis ?? {});
const previousKpis = computed(() => props.dashboard.previous_kpis ?? {});
const yoyExcludedCount = computed(() => previousKpis.value.excluded_total_mode_projects ?? 0);
const scoreWeights = computed(() => props.dashboard.score_weights ?? null);
const byCategory = computed(() => props.dashboard.by_category ?? []);
const projects = computed(() => props.dashboard.projects ?? []);
const monthly = computed(() => props.dashboard.monthly ?? []);
const dataGaps = computed(() => props.dashboard.data_gaps ?? []);
const tagsLinked = computed(() => props.dashboard.tags_linked !== false);

const dateFrom = ref(props.dashboard.range?.from ?? '');
const dateTo = ref(props.dashboard.range?.to ?? '');
const loading = ref(false);
// Ohne explizite URL-Daten gilt serverseitig die Spielzeit → Preset als aktiv markieren
const initialQuery = new URLSearchParams(window.location.search);
const activePreset = ref(initialQuery.get('date_from') || initialQuery.get('date_to') ? null : 'playing_time');

// Nach einem Reload die effektiv angewandte Spanne in die Inputs spiegeln
watch(() => props.dashboard.range, (range) => {
    dateFrom.value = range?.from ?? '';
    dateTo.value = range?.to ?? '';
});

const gapLimit = 8;
const showAllGaps = ref(false);
const visibleGaps = computed(() => showAllGaps.value ? dataGaps.value : dataGaps.value.slice(0, gapLimit));

const quickEntryGap = ref(null);

const onQuickEntrySaved = () => {
    quickEntryGap.value = null;
    // Schreibzugriff hat die Dashboard-Cache-Version gebumpt → Reload liefert frische Zahlen
    reload(true);
};

const numberFmt = new Intl.NumberFormat('de-DE');
const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const percentFmt = new Intl.NumberFormat('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
const formatInt = (v) => numberFmt.format(v ?? 0);
const formatCurrency = (v) => currencyFmt.format(v ?? 0);
const formatPercent = (v) => `${percentFmt.format(v ?? 0)} %`;

// --- Zeitraum-Schnellwahl ---

const isoDate = (d) => {
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${month}-${day}`;
};

const rangePresets = [
    { key: 'playing_time', label: 'Season (default)' },
    { key: 'calendar_year', label: 'Calendar year' },
    { key: 'last_12_months', label: 'Last 12 months' },
];

const applyPreset = (preset) => {
    const now = new Date();
    if (preset.key === 'playing_time') {
        dateFrom.value = '';
        dateTo.value = '';
    } else if (preset.key === 'calendar_year') {
        dateFrom.value = `${now.getFullYear()}-01-01`;
        dateTo.value = `${now.getFullYear()}-12-31`;
    } else {
        const from = new Date(now);
        from.setFullYear(from.getFullYear() - 1);
        from.setDate(from.getDate() + 1);
        dateFrom.value = isoDate(from);
        dateTo.value = isoDate(now);
    }
    activePreset.value = preset.key;
    reload(true);
};

// Relative Änderung in % (Zähl-/Summen-KPIs), verglichen über comparable_kpis,
// damit aktuelle und Vorjahres-Basis denselben Ausschlussregeln folgen
const delta = (key) => {
    const prev = previousKpis.value[key];
    const cur = comparableKpis.value[key];
    if (!prev || prev === 0 || cur === null || cur === undefined) return null;
    return (cur - prev) / prev * 100;
};

// Differenz in Prozentpunkten — für Raten-KPIs wie die Auslastung, wo eine
// relative Änderung ("+12,5 %") garantiert falsch gelesen würde
const deltaPoints = (key) => {
    const prev = previousKpis.value[key];
    const cur = comparableKpis.value[key];
    if (prev === null || prev === undefined || cur === null || cur === undefined) return null;
    return cur - prev;
};

const relDeltaText = (d) => (d === null ? null : `${percentFmt.format(Math.abs(d))} %`);

const effortScoreTooltip = computed(() => {
    const w = scoreWeights.value;
    if (!w) {
        return t('Weighted proxy for internal effort: 2 × contracts + 1 × bookings + 1.5 × open tasks + 0.5 × documents + 0.1 × effort hours.');
    }
    const fmt = (v) => numberFmt.format(v);
    return `${t('Weighted proxy for internal effort')}: `
        + `${fmt(w.contracts)} × ${t('contracts')} + ${fmt(w.bookings)} × ${t('bookings')} + `
        + `${fmt(w.open_tasks)} × ${t('open tasks')} + ${fmt(w.documents)} × ${t('documents')} + `
        + `${fmt(w.effort_hours)} × ${t('effort hours')}.`;
});

const kpiTiles = computed(() => {
    const visitorsDelta = delta('visitors');
    const revenueDelta = delta('revenue');
    const occupancyDelta = deltaPoints('occupancy');
    const eventDaysDelta = delta('event_days');
    const performancesDelta = delta('performances');

    return [
        {
            key: 'visitors',
            label: 'Visitors',
            value: (kpis.value.visitors_estimated ? '≈ ' : '') + formatInt(kpis.value.visitors),
            delta: visitorsDelta,
            deltaText: relDeltaText(visitorsDelta),
            note: kpis.value.visitors_estimated ? t('partly estimated from sold tickets') : null,
            tooltip: kpis.value.visitors_estimated ? t('Estimated from sold tickets') : null,
        },
        {
            key: 'revenue',
            label: 'Revenue',
            value: formatCurrency(kpis.value.revenue),
            delta: revenueDelta,
            deltaText: relDeltaText(revenueDelta),
        },
        {
            key: 'occupancy',
            label: 'Occupancy rate',
            value: kpis.value.occupancy !== null && kpis.value.occupancy !== undefined ? formatPercent(kpis.value.occupancy) : '—',
            delta: occupancyDelta,
            deltaText: occupancyDelta !== null ? `${percentFmt.format(Math.abs(occupancyDelta))} ${t('percentage points')}` : null,
            tooltip: t('Sold tickets ÷ seat capacity of the rooms played in the selected period.'),
        },
        {
            key: 'event_days',
            label: 'Event days',
            value: formatInt(kpis.value.event_days),
            delta: eventDaysDelta,
            deltaText: relDeltaText(eventDaysDelta),
        },
        {
            key: 'performances',
            label: 'Performances',
            value: formatInt(kpis.value.performances),
            delta: performancesDelta,
            deltaText: relDeltaText(performancesDelta),
        },
        { key: 'project_count', label: 'Productions', value: formatInt(kpis.value.project_count), delta: null },
    ];
});

const palette = ['#6366f1', '#22c55e', '#f59e0b', '#ec4899', '#06b6d4', '#a855f7', '#ef4444', '#14b8a6', '#eab308', '#3b82f6'];

const hasCategoryData = computed(() => byCategory.value.length > 0);

// --- Monats-Zeitreihe ---

const monthLabel = (ym) => {
    const [year, month] = String(ym).split('-');
    return `${month}.${year}`;
};

const monthlyChart = computed(() => {
    const rows = monthly.value;
    if (!rows.length || !rows.some(r => r.visitors > 0 || r.revenue > 0 || r.prev_visitors > 0)) return null;

    return {
        labels: rows.map(r => monthLabel(r.month)),
        datasets: [
            {
                label: t('Visitors'),
                data: rows.map(r => r.visitors),
                backgroundColor: '#6366f1',
                yAxisID: 'y',
                order: 3,
            },
            {
                type: 'line',
                label: `${t('Visitors')} (${t('previous year')})`,
                data: rows.map(r => r.prev_visitors),
                borderColor: '#9ca3af',
                backgroundColor: '#9ca3af',
                borderDash: [5, 4],
                pointRadius: 2,
                tension: 0.3,
                yAxisID: 'y',
                order: 2,
                // Monate ohne Vorjahresdaten kommen als null → Linie aussetzen
                // statt eine irreführende 0-Linie zu zeichnen
                spanGaps: false,
            },
            {
                type: 'line',
                label: t('Revenue'),
                data: rows.map(r => r.revenue),
                borderColor: '#f59e0b',
                backgroundColor: '#f59e0b',
                pointRadius: 2,
                tension: 0.3,
                yAxisID: 'y1',
                order: 1,
            },
        ],
    };
});

const monthlyOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'inherit' }, boxWidth: 12 } },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    if (ctx.parsed.y === null) return null;
                    const formatted = ctx.dataset.yAxisID === 'y1'
                        ? currencyFmt.format(ctx.parsed.y)
                        : numberFmt.format(ctx.parsed.y);
                    return `${ctx.dataset.label}: ${formatted}`;
                },
            },
        },
    },
    scales: {
        y: { beginAtZero: true, position: 'left', ticks: { callback: (v) => numberFmt.format(v) } },
        y1: {
            beginAtZero: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: { callback: (v) => currencyFmt.format(v) },
        },
    },
};

// --- Kategorie-Charts (klickbar → filtert Tabelle) ---

const categoryFilter = ref(null);

const categoryChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { font: { family: 'inherit' } } },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    // Umsatz-Doughnut in €, Besucher-Bar mit Tausendertrennung
                    const value = ctx.parsed?.y ?? ctx.parsed;
                    const formatted = ctx.chart.config.type === 'doughnut'
                        ? currencyFmt.format(value)
                        : numberFmt.format(value);
                    return `${ctx.dataset.label ? ctx.dataset.label + ': ' : ''}${formatted}`;
                },
            },
        },
    },
    onClick: (event, elements, chart) => {
        if (!elements?.length) return;
        const label = chart.data.labels[elements[0].index];
        categoryFilter.value = categoryFilter.value === label ? null : label;
    },
}));

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
        label: t('Visitors'),
        data: byCategory.value.map(c => c.visitors),
        backgroundColor: '#6366f1',
    }],
}));

// --- Aufwand vs. Ertrag (Quadranten relativ zum Median) ---

const median = (values) => {
    if (!values.length) return 0;
    const sorted = [...values].sort((a, b) => a - b);
    const mid = Math.floor(sorted.length / 2);
    return sorted.length % 2 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;
};

const scatterRows = computed(() => projects.value.filter(
    row => row.effort_score > 0 || row.revenue > 0 || row.visitors > 0
));

const outputUsesRevenue = computed(() => scatterRows.value.some(row => row.revenue > 0));

const scatterChart = computed(() => {
    const rows = scatterRows.value;
    if (rows.length < 3) return null;

    const outputOf = (row) => outputUsesRevenue.value ? row.revenue : row.visitors;
    const effortMedian = median(rows.map(r => r.effort_score));
    const outputMedian = median(rows.map(outputOf));

    const quadrantColor = (row) => {
        const highEffort = row.effort_score > effortMedian;
        const highOutput = outputOf(row) > outputMedian;
        if (highEffort && !highOutput) return '#f43f5e';
        if (!highEffort && highOutput) return '#10b981';
        if (highEffort && highOutput) return '#818cf8';
        return '#9ca3af';
    };

    return {
        datasets: [{
            label: t('Productions'),
            data: rows.map(row => ({
                x: row.effort_score,
                y: outputOf(row),
                name: row.project_name,
                projectId: row.project_id,
            })),
            backgroundColor: rows.map(quadrantColor),
            pointRadius: 6,
            pointHoverRadius: 8,
        }],
    };
});

const scatterOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    const output = outputUsesRevenue.value ? formatCurrency(ctx.raw.y) : formatInt(ctx.raw.y);
                    return `${ctx.raw.name} — ${t('Effort score')}: ${ctx.raw.x}, ${outputUsesRevenue.value ? t('Revenue') : t('Visitors')}: ${output}`;
                },
            },
        },
    },
    onClick: (event, elements, chart) => {
        if (!elements?.length) return;
        const point = chart.data.datasets[elements[0].datasetIndex].data[elements[0].index];
        if (point?.projectId) {
            router.visit(route('projects.tab', { project: point.projectId, projectTab: firstProjectTabId.value }));
        }
    },
    scales: {
        x: { title: { display: true, text: t('Effort score') }, beginAtZero: true },
        y: {
            title: { display: true, text: outputUsesRevenue.value ? t('Revenue') : t('Visitors') },
            beginAtZero: true,
            ticks: outputUsesRevenue.value
                ? { callback: (v) => currencyFmt.format(v) }
                : { callback: (v) => numberFmt.format(v) },
        },
    },
}));

// --- Drilldown-Tabelle ---

const occupancyBarClass = (value) => {
    if (value >= 90) return 'bg-emerald-500';
    if (value >= 50) return 'bg-indigo-500';
    return 'bg-amber-500';
};

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
    let rows = [...projects.value];
    if (categoryFilter.value) {
        rows = rows.filter(row => (row.category || '—') === categoryFilter.value);
    }
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

const reload = (fromPreset = false) => {
    if (!fromPreset) {
        activePreset.value = null;
    }
    loading.value = true;
    router.get(route('bi.dashboard'), { date_from: dateFrom.value || null, date_to: dateTo.value || null }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { loading.value = false; },
    });
};
</script>
