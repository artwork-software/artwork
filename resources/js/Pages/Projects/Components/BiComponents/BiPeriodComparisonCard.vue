<template>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm print:hidden">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-2 text-left"
            @click="expanded = !expanded"
        >
            <span class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                <IconChevronDown class="size-4 transition-transform" :class="{ '-rotate-90': !expanded }" />
                {{ $t('Period comparison') }}
            </span>
            <span class="text-xs text-gray-400">{{ $t('e.g. revival vs. premiere run') }}</span>
        </button>

        <div v-show="expanded" class="mt-3">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">{{ $t('Period A') }}</p>
                    <div class="flex items-end gap-2">
                        <BaseInput type="date" id="bi_cmp_a_from" v-model="aFrom" :label="$t('From')" class="w-38" />
                        <BaseInput type="date" id="bi_cmp_a_to" v-model="aTo" :label="$t('To')" class="w-38" />
                    </div>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">
                        {{ $t('Period B') }}
                        <button type="button" class="ml-1 text-indigo-600 hover:underline" @click="setBPreviousYear">
                            {{ $t('Previous year') }}
                        </button>
                        <button type="button" class="ml-1 text-indigo-600 hover:underline" @click="setBPreviousPeriod">
                            {{ $t('Previous period') }}
                        </button>
                    </p>
                    <div class="flex items-end gap-2">
                        <BaseInput type="date" id="bi_cmp_b_from" v-model="bFrom" :label="$t('From')" class="w-38" />
                        <BaseInput type="date" id="bi_cmp_b_to" v-model="bTo" :label="$t('To')" class="w-38" />
                    </div>
                </div>
                <BaseUIButton
                    :label="$t('Compare')"
                    hide-icon
                    :disabled="loading || !aFrom || !aTo || !bFrom || !bTo"
                    @click="runComparison"
                />
            </div>

            <p v-if="error" class="mt-2 text-xs text-rose-600">{{ $t('Error loading BI data.') }}</p>

            <div v-if="result" class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                            <th class="py-1.5 pr-3 font-medium">{{ $t('Key figure') }}</th>
                            <th class="py-1.5 px-3 font-medium">A · {{ rangeLabel(result.a.range) }}</th>
                            <th class="py-1.5 px-3 font-medium">B · {{ rangeLabel(result.b.range) }}</th>
                            <th class="py-1.5 px-3 font-medium">{{ $t('Difference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in comparisonRows" :key="row.key">
                            <td class="py-2 pr-3 text-gray-700">{{ $t(row.label) }}</td>
                            <td class="py-2 px-3 font-medium text-gray-900">{{ row.a ?? '–' }}</td>
                            <td class="py-2 px-3 text-gray-600">{{ row.b ?? '–' }}</td>
                            <td class="py-2 px-3" :class="row.deltaClass">{{ row.delta ?? '–' }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="mt-1.5 text-[11px] text-gray-400">
                    {{ $t('Metrics in TOTAL mode are period-neutral — the comparison is only meaningful for per-event figures.') }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { IconChevronDown } from '@tabler/icons-vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    projectId: { type: Number, required: true },
    projectPeriod: { type: Object, default: null },
});

const expanded = ref(false);
const loading = ref(false);
const error = ref(false);
const result = ref(null);

const aFrom = ref(props.projectPeriod?.from ?? '');
const aTo = ref(props.projectPeriod?.to ?? '');
const bFrom = ref('');
const bTo = ref('');

watch(() => props.projectPeriod, (period) => {
    if (!aFrom.value && period?.from) aFrom.value = period.from;
    if (!aTo.value && period?.to) aTo.value = period.to;
});

const isoDate = (d) => {
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${month}-${day}`;
};

const setBPreviousYear = () => {
    if (!aFrom.value || !aTo.value) return;
    const from = new Date(aFrom.value + 'T00:00:00');
    const to = new Date(aTo.value + 'T00:00:00');
    from.setFullYear(from.getFullYear() - 1);
    to.setFullYear(to.getFullYear() - 1);
    bFrom.value = isoDate(from);
    bTo.value = isoDate(to);
};

const setBPreviousPeriod = () => {
    if (!aFrom.value || !aTo.value) return;
    const from = new Date(aFrom.value + 'T00:00:00');
    const to = new Date(aTo.value + 'T00:00:00');
    const lengthMs = to - from;
    const cmpTo = new Date(from);
    cmpTo.setDate(cmpTo.getDate() - 1);
    const cmpFrom = new Date(cmpTo.getTime() - lengthMs);
    bFrom.value = isoDate(cmpFrom);
    bTo.value = isoDate(cmpTo);
};

const runComparison = async () => {
    loading.value = true;
    error.value = false;
    try {
        const [a, b] = await Promise.all([
            axios.get(route('projects.bi.metrics-summary', props.projectId), {
                params: { date_from: aFrom.value, date_to: aTo.value },
            }),
            axios.get(route('projects.bi.metrics-summary', props.projectId), {
                params: { date_from: bFrom.value, date_to: bTo.value },
            }),
        ]);
        result.value = { a: a.data, b: b.data };
    } catch (e) {
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const numberFmt = new Intl.NumberFormat('de-DE');
const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const percentFmt = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 1 });

const rangeLabel = (range) => {
    const fmt = (iso) => {
        if (!iso) return '';
        const [y, m, d] = iso.split('-');
        return `${d}.${m}.${y}`;
    };
    return `${fmt(range?.from)} – ${fmt(range?.to)}`;
};

const metricDefs = [
    { key: 'visitors', label: 'Visitors', format: 'int' },
    { key: 'sold_tickets', label: 'Sold tickets', format: 'int' },
    { key: 'revenue', label: 'Revenue', format: 'currency' },
    { key: 'occupancy', label: 'Occupancy rate', format: 'percent', deltaInPoints: true },
    { key: 'performances', label: 'Performances', format: 'int' },
    { key: 'event_days', label: 'Event days', format: 'int' },
];

const formatBy = (format, value) => {
    if (value === null || value === undefined) return null;
    if (format === 'currency') return currencyFmt.format(value);
    if (format === 'percent') return `${percentFmt.format(value)} %`;
    return numberFmt.format(value);
};

const comparisonRows = computed(() => {
    if (!result.value) return [];

    return metricDefs.map((def) => {
        const aValue = result.value.a.summary?.[def.key] ?? null;
        const bValue = result.value.b.summary?.[def.key] ?? null;

        let delta = null;
        let deltaClass = 'text-gray-300';
        if (aValue !== null && bValue !== null) {
            const raw = aValue - bValue;
            // Raten in Prozentpunkten, Zähl-/Summenwerte als Differenz
            delta = def.deltaInPoints
                ? `${raw > 0 ? '+' : ''}${percentFmt.format(raw)} ${t('percentage points')}`
                : `${raw > 0 ? '+' : ''}${formatBy(def.format, raw)}`;
            deltaClass = raw >= 0 ? 'text-emerald-600' : 'text-rose-600';
        }

        return {
            key: def.key,
            label: def.label,
            a: formatBy(def.format, aValue),
            b: formatBy(def.format, bValue),
            delta,
            deltaClass,
        };
    });
});
</script>
