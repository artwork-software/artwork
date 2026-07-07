<template>
    <div class="mb-8">
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <div
                v-for="kpi in kpiTiles"
                :key="kpi.key"
                class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm"
            >
                <div class="flex items-center gap-1.5">
                    <component :is="kpi.icon" class="size-4 text-gray-400 shrink-0" />
                    <span class="text-xs text-gray-500 truncate">{{ $t(kpi.label) }}</span>
                </div>
                <p class="text-lg font-semibold mt-1" :class="kpi.value === null ? 'text-gray-300' : 'text-gray-900'">
                    {{ kpi.value ?? '–' }}
                </p>
            </div>
        </div>

        <div v-if="chartData" class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden">
            <h4 class="text-xs font-medium text-gray-500 mb-2">{{ $t('Per-event trend') }}</h4>
            <BiChart type="bar" :data="chartData" :options="chartOptions" height="220px" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    IconUsers,
    IconTicket,
    IconCurrencyEuro,
    IconGauge,
    IconTag,
    IconMasksTheater,
} from '@tabler/icons-vue';
import BiChart from '@/Artwork/Charts/BiChart.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
});

const numberFmt = new Intl.NumberFormat('de-DE');
const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });

const formatInt = (v) => (v === null || v === undefined) ? null : numberFmt.format(v);
const formatCurrency = (v) => (v === null || v === undefined) ? null : currencyFmt.format(v);
const formatPercent = (v) => (v === null || v === undefined) ? null : `${Number(v).toFixed(1).replace('.', ',')} %`;

const kpiTiles = computed(() => [
    { key: 'visitors', label: 'Visitors', icon: IconUsers, value: formatInt(props.summary.visitors) },
    { key: 'sold_tickets', label: 'Sold tickets', icon: IconTicket, value: formatInt(props.summary.sold_tickets) },
    { key: 'revenue', label: 'Revenue', icon: IconCurrencyEuro, value: formatCurrency(props.summary.revenue) },
    { key: 'occupancy', label: 'Occupancy rate', icon: IconGauge, value: formatPercent(props.summary.occupancy) },
    { key: 'avg_price', label: 'Average ticket price', icon: IconTag, value: formatCurrency(props.summary.avg_price) },
    { key: 'performances', label: 'Performances', icon: IconMasksTheater, value: formatInt(props.summary.performances) },
]);

// d.m.Y H:i aus dem Backend → Zeitstempel für die chronologische Sortierung
const parseDate = (value) => {
    if (!value) return 0;
    const [datePart, timePart = '00:00'] = String(value).split(' ');
    const [day, month, year] = datePart.split('.');
    const [hour, minute] = timePart.split(':');
    return new Date(+year, (+month || 1) - 1, +day || 1, +hour || 0, +minute || 0).getTime();
};

const chartRows = computed(() => {
    const byEventId = new Map(props.eventData.map(e => [e.event_id, e]));

    return props.projectEvents
        .map((event) => {
            const entry = byEventId.get(event.id);
            return {
                ts: parseDate(event.start_time),
                label: (event.start_time || '').split(' ')[0] || event.name,
                visitors: entry?.visitors ?? null,
                soldTickets: entry?.sold_tickets ?? null,
                revenue: entry?.revenue != null ? Number(entry.revenue) : null,
            };
        })
        .filter(row => row.visitors !== null || row.soldTickets !== null || row.revenue !== null)
        .sort((a, b) => a.ts - b.ts);
});

const chartData = computed(() => {
    const rows = chartRows.value;
    if (rows.length < 2) return null;

    const datasets = [];

    if (rows.some(r => r.visitors !== null)) {
        datasets.push({
            label: t('Visitors'),
            data: rows.map(r => r.visitors),
            backgroundColor: '#6366f1',
            yAxisID: 'y',
        });
    }

    if (rows.some(r => r.soldTickets !== null)) {
        datasets.push({
            label: t('Sold tickets'),
            data: rows.map(r => r.soldTickets),
            backgroundColor: '#22c55e',
            yAxisID: 'y',
        });
    }

    if (rows.some(r => r.revenue !== null)) {
        datasets.push({
            type: 'line',
            label: t('Revenue'),
            data: rows.map(r => r.revenue),
            borderColor: '#f59e0b',
            backgroundColor: '#f59e0b',
            tension: 0.3,
            yAxisID: 'y1',
        });
    }

    if (datasets.length === 0) return null;

    return { labels: rows.map(r => r.label), datasets };
});

const chartOptions = computed(() => {
    const hasRevenue = chartData.value?.datasets.some(d => d.yAxisID === 'y1');

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { font: { family: 'inherit' }, boxWidth: 12 } },
        },
        scales: {
            y: { beginAtZero: true, position: 'left' },
            ...(hasRevenue ? {
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { callback: (v) => currencyFmt.format(v) },
                },
            } : {}),
        },
    };
});
</script>
