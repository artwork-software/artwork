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
                <p v-if="kpi.notApplicable" class="text-xs text-gray-400 mt-2 italic">
                    {{ $t('Not relevant') }}
                </p>
                <template v-else>
                    <p class="text-lg font-semibold mt-1" :class="kpi.value === null ? 'text-gray-300' : 'text-gray-900'">
                        <template v-if="kpi.estimated">≈ </template>{{ kpi.value ?? '–' }}
                    </p>
                    <p v-if="kpi.estimated" class="text-[10px] text-indigo-600 leading-tight">
                        {{ $t('estimated from sold tickets') }}
                    </p>
                </template>
            </div>
        </div>

        <!-- Quoten-Reihe: erscheint nur, wenn Kategoriewerte erfasst sind -->
        <div v-if="quotaTiles.length > 0" class="mt-3 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <div
                v-for="kpi in quotaTiles"
                :key="kpi.key"
                class="rounded-xl border border-gray-100 bg-gray-50/60 p-3"
                v-tooltip.top="kpi.tooltip ? { value: kpi.tooltip, appendTo: 'body', class: 'aw-tooltip' } : undefined"
            >
                <span class="text-xs text-gray-500 truncate block">{{ $t(kpi.label) }}</span>
                <p class="text-base font-semibold mt-1" :class="kpi.value === null ? 'text-gray-300' : 'text-gray-800'">
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

const kpiTiles = computed(() => {
    const s = props.summary;
    const ticketsNa = !!s.sold_tickets_not_applicable;
    const revenueNa = !!s.revenue_not_applicable;

    return [
        {
            key: 'visitors', label: 'Visitors', icon: IconUsers,
            value: formatInt(s.visitors),
            estimated: !!s.visitors_estimated,
            notApplicable: !!s.visitors_not_applicable,
        },
        {
            key: 'sold_tickets', label: 'Sold tickets', icon: IconTicket,
            value: formatInt(s.sold_tickets),
            notApplicable: ticketsNa,
        },
        {
            key: 'revenue', label: 'Revenue', icon: IconCurrencyEuro,
            value: formatCurrency(s.revenue),
            notApplicable: revenueNa,
        },
        {
            key: 'occupancy', label: 'Occupancy rate', icon: IconGauge,
            value: formatPercent(s.occupancy),
            notApplicable: ticketsNa,
        },
        {
            key: 'avg_price', label: 'Average ticket price', icon: IconTag,
            value: formatCurrency(s.avg_price),
            notApplicable: ticketsNa || revenueNa,
        },
        {
            key: 'performances', label: 'Performances', icon: IconMasksTheater,
            value: formatInt(s.performances),
        },
    ];
});

// Zweite Kachel-Reihe mit Quoten/abgeleiteten Kennzahlen — nur wenn
// Kategoriewerte erfasst sind (tickets_issued) bleibt sie nicht leer
const quotaTiles = computed(() => {
    const s = props.summary;
    if (s.tickets_issued === null || s.tickets_issued === undefined) return [];

    const noShow = s.no_show_rate;

    return [
        { key: 'tickets_issued', label: 'Tickets issued', value: formatInt(s.tickets_issued) },
        { key: 'free_tickets_rate', label: 'Free ticket rate', value: formatPercent(s.free_tickets_rate) },
        { key: 'reduced_tickets_rate', label: 'Reduced ticket rate', value: formatPercent(s.reduced_tickets_rate) },
        { key: 'paying_rate', label: 'Paying rate', value: formatPercent(s.paying_rate) },
        {
            key: 'no_show_rate',
            label: 'No-show rate',
            // Negative Quote = mehr Besucher*innen als Karten (Überbesetzung) → "–" mit Erklärung
            value: (noShow !== null && noShow !== undefined && noShow < 0) ? null : formatPercent(noShow),
            tooltip: (noShow !== null && noShow !== undefined && noShow < 0)
                ? t('More visitors than issued tickets recorded — no-show rate not meaningful.')
                : null,
        },
        { key: 'seat_occupancy', label: 'Seat occupancy (incl. free tickets)', value: formatPercent(s.seat_occupancy) },
    ];
});

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
