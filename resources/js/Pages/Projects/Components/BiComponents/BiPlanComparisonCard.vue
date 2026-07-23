<template>
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h4 class="text-sm font-semibold text-gray-900">{{ $t('Plan vs. actual') }}</h4>
            <div class="flex rounded-lg border border-gray-200 p-0.5 print:hidden">
                <button
                    v-for="option in metricOptions"
                    :key="option.key"
                    type="button"
                    class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                    :class="chartMetric === option.key ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                    @click="chartMetric = option.key"
                >
                    {{ $t(option.label) }}
                </button>
            </div>
        </div>

        <!-- Delta-Tabelle je Kennzahl -->
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500">
                        <th class="py-1.5 pr-3 font-medium">{{ $t('Key figure') }}</th>
                        <th class="py-1.5 px-3 font-medium">{{ $t('Plan') }}</th>
                        <th class="py-1.5 px-3 font-medium">{{ $t('Actual') }}</th>
                        <th class="py-1.5 px-3 font-medium">{{ $t('Difference') }}</th>
                        <th class="py-1.5 px-3 font-medium">{{ $t('Attainment') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in comparisonRows" :key="row.key">
                        <td class="py-2 pr-3 text-gray-700">{{ $t(row.label) }}</td>
                        <td class="py-2 px-3 text-gray-900 font-medium">{{ row.plan ?? '–' }}</td>
                        <td class="py-2 px-3 text-gray-900 font-medium">{{ row.actual ?? '–' }}</td>
                        <td class="py-2 px-3" :class="row.diffClass">{{ row.diff ?? '–' }}</td>
                        <td class="py-2 px-3">
                            <span
                                v-if="row.attainment !== null"
                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="attainmentClass(row.attainmentRaw, row.key === 'costs')"
                            >
                                {{ row.attainment }}
                            </span>
                            <span v-else class="text-gray-300">–</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Verlauf: kumuliertes Ist gegen die Plan-Linie -->
        <div v-if="chartData" class="mt-4 print:hidden">
            <BiChart type="bar" :data="chartData" :options="chartOptions" height="240px" />
            <p class="mt-1 text-[11px] text-gray-400">
                {{ planIsPerEvent
                    ? $t('Plan line cumulated from per-event plan values.')
                    : $t('Plan line = straight line towards the plan total.') }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import BiChart from '@/Artwork/Charts/BiChart.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    planComparison: { type: Object, required: true },
    eventData: { type: Array, default: () => [] },
    planEventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
});

const numberFmt = new Intl.NumberFormat('de-DE');
const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const percentFmt = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 1 });

const metricOptions = [
    { key: 'visitors', label: 'Visitors' },
    { key: 'sold_tickets', label: 'Sold tickets' },
    { key: 'revenue', label: 'Revenue', currency: true },
];

// Kosten/Ergebnis nur in der Delta-Tabelle - für den Verlauf gibt es keine
// per-Event-Daten. invertDiff: bei Kosten ist ein Ist ÜBER Plan negativ zu werten.
const tableMetricOptions = [
    ...metricOptions,
    { key: 'costs', label: 'Costs', currency: true, invertDiff: true },
    { key: 'result', label: 'Result', currency: true },
];

const chartMetric = ref('visitors');

const formatValue = (key, value) => {
    if (value === null || value === undefined) return null;
    return ['revenue', 'costs', 'result'].includes(key) ? currencyFmt.format(value) : numberFmt.format(value);
};

const attainmentClass = (value, inverted = false) => {
    if (value === null || value === undefined) return 'bg-gray-100 text-gray-500';
    if (inverted) {
        if (value <= 100) return 'bg-emerald-100 text-emerald-700';
        if (value <= 120) return 'bg-amber-100 text-amber-700';
        return 'bg-rose-100 text-rose-700';
    }
    if (value >= 100) return 'bg-emerald-100 text-emerald-700';
    if (value >= 80) return 'bg-amber-100 text-amber-700';
    return 'bg-rose-100 text-rose-700';
};

const comparisonRows = computed(() => tableMetricOptions.map((option) => {
    const entry = props.planComparison?.metrics?.[option.key] ?? {};
    const diff = entry.diff;
    const diffIsPositive = option.invertDiff ? diff <= 0 : diff >= 0;

    return {
        key: option.key,
        label: option.label,
        plan: formatValue(option.key, entry.plan),
        actual: formatValue(option.key, entry.actual),
        diff: diff !== null && diff !== undefined
            ? `${diff > 0 ? '+' : ''}${formatValue(option.key, diff)}`
            : null,
        diffClass: diff === null || diff === undefined
            ? 'text-gray-300'
            : (diffIsPositive ? 'text-emerald-600' : 'text-rose-600'),
        attainment: entry.attainment !== null && entry.attainment !== undefined
            ? `${percentFmt.format(entry.attainment)} %`
            : null,
        attainmentRaw: entry.attainment ?? null,
    };
}));

// d.m.Y H:i → Zeitstempel
const parseDate = (value) => {
    if (!value) return 0;
    const [datePart, timePart = '00:00'] = String(value).split(' ');
    const [day, month, year] = datePart.split('.');
    const [hour, minute] = timePart.split(':');
    return new Date(+year, (+month || 1) - 1, +day || 1, +hour || 0, +minute || 0).getTime();
};

const planIsPerEvent = computed(() =>
    props.planEventData.some(e => e[chartMetric.value] !== null && e[chartMetric.value] !== undefined)
);

const chartData = computed(() => {
    const metric = chartMetric.value;
    const planTotal = props.planComparison?.metrics?.[metric]?.plan;
    const actualByEvent = new Map(props.eventData.map(e => [e.event_id, e[metric]]));
    const planByEvent = new Map(props.planEventData.map(e => [e.event_id, e[metric]]));

    const rows = props.projectEvents
        .map(event => ({
            ts: parseDate(event.start_time),
            label: (event.start_time || '').split(' ')[0] || event.name,
            actual: actualByEvent.get(event.id) ?? null,
            plan: planByEvent.get(event.id) ?? null,
        }))
        .sort((a, b) => a.ts - b.ts);

    const withData = rows.filter(r => r.actual !== null || r.plan !== null);

    // Ohne Pro-Termin-Daten: schlichter Plan-vs-Ist-Balkenvergleich
    if (withData.length < 2) {
        const actualTotal = props.planComparison?.metrics?.[metric]?.actual;
        if (planTotal === null || planTotal === undefined) return null;
        return {
            labels: [t('Plan'), t('Actual')],
            datasets: [{
                label: t(metricOptions.find(o => o.key === metric).label),
                data: [planTotal, actualTotal ?? 0],
                backgroundColor: ['#94a3b8', '#6366f1'],
            }],
        };
    }

    // Kumuliertes Ist als Balken, Plan als Linie (kumuliert oder Zielgerade)
    let actualCum = 0;
    const actualSeries = withData.map((r) => {
        actualCum += Number(r.actual ?? 0);
        return actualCum;
    });

    let planSeries;
    if (planIsPerEvent.value) {
        let planCum = 0;
        planSeries = withData.map((r) => {
            planCum += Number(r.plan ?? 0);
            return planCum;
        });
    } else if (planTotal !== null && planTotal !== undefined) {
        const n = withData.length;
        planSeries = withData.map((_, i) => Math.round(planTotal * (i + 1) / n * 100) / 100);
    } else {
        planSeries = null;
    }

    const datasets = [{
        label: `${t('Actual')} (${t('cumulated')})`,
        data: actualSeries,
        backgroundColor: '#6366f1',
    }];

    if (planSeries) {
        datasets.push({
            type: 'line',
            label: t('Plan'),
            data: planSeries,
            borderColor: '#94a3b8',
            backgroundColor: '#94a3b8',
            borderDash: [6, 4],
            pointRadius: 0,
            tension: 0,
        });
    }

    return { labels: withData.map(r => r.label), datasets };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { boxWidth: 12 } },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: chartMetric.value === 'revenue'
                ? { callback: (v) => currencyFmt.format(v) }
                : {},
        },
    },
}));
</script>
