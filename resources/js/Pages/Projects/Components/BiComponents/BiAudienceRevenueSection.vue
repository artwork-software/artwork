<template>
    <div>
        <!-- Modus + Gesamtwert je Kennzahl -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div
                v-for="metric in metrics"
                :key="metric.key"
                class="rounded-lg border border-gray-200 p-3"
                :class="{ 'bg-gray-50 opacity-75': notApplicable[metric.key] }"
            >
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-sm font-medium text-gray-900">{{ $t(metric.label) }}</span>
                    <SwitchDualLabel
                        v-if="canEdit && !notApplicable[metric.key]"
                        :model-value="modes[metric.key] === 'per_event'"
                        :left-label="$t('Total')"
                        :right-label="$t('Per event')"
                        size="sm"
                        @change="isPerEvent => onToggleChange(metric, isPerEvent)"
                    />
                    <span v-else-if="!notApplicable[metric.key]" class="text-xs text-gray-400">
                        {{ modes[metric.key] === 'per_event' ? $t('Per event') : $t('Total') }}
                    </span>
                </div>

                <template v-if="notApplicable[metric.key]">
                    <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                        {{ $t('Not relevant for this project') }}
                    </span>
                </template>
                <template v-else>
                    <BaseInput
                        v-if="modes[metric.key] === 'total'"
                        type="number"
                        :id="'bi_total_' + metric.key"
                        v-model.number="totals[metric.key]"
                        :label="$t(metric.totalLabel)"
                        :disabled="!canEdit"
                        :min="0"
                        :step="metric.key === 'revenue' ? 0.01 : 1"
                        @change="saveTotal(metric)"
                    />
                    <p v-else class="text-xs text-gray-500">
                        {{ $t('Recorded per event in the table below.') }}
                        <span class="font-medium text-gray-700">{{ perEventSummary(metric) }}</span>
                    </p>
                    <p
                        v-if="metric.key === 'visitors' && visitorsEstimated"
                        class="mt-1.5 text-xs text-indigo-600"
                    >
                        ≈ {{ $t('Currently estimated from sold tickets') }}: {{ formatInt(metricsSummary.visitors) }}
                    </p>
                </template>

                <label
                    v-if="canEdit"
                    class="mt-2 flex items-center gap-1.5 text-xs text-gray-400 cursor-pointer select-none print:hidden"
                >
                    <input
                        type="checkbox"
                        class="input-checklist !h-3.5 !w-3.5"
                        :checked="notApplicable[metric.key]"
                        @change="toggleNotApplicable(metric, $event.target.checked)"
                    />
                    {{ $t('Not relevant for this project') }}
                </label>
            </div>
        </div>

        <!-- Eine Tabelle für alle Pro-Termin-Kennzahlen -->
        <BiEventMetricsTable
            v-if="perEventFields.length > 0"
            :event-data="eventData"
            :project-events="projectEvents"
            :can-edit="canEdit"
            :project-id="projectId"
            :fields="perEventFields"
            :show-occupancy="showOccupancy"
            :effective-capacities="effectiveCapacities"
            @updated="$emit('updated')"
        />

        <BiModeSwitchModal
            v-if="showModeModal"
            @confirm="confirmModeSwitch"
            @close="showModeModal = false"
        />
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import SwitchDualLabel from '@/Artwork/Toggles/SwitchDualLabel.vue';
import BiEventMetricsTable from '@/Pages/Projects/Components/BiComponents/BiEventMetricsTable.vue';
import BiModeSwitchModal from '@/Pages/Projects/Components/BiComponents/BiModeSwitchModal.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    biData: { type: Object, default: null },
    metricsSummary: { type: Object, default: () => ({}) },
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
    roomCapacities: { type: Array, default: () => [] },
    projectRooms: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const metrics = [
    {
        key: 'visitors',
        label: 'Visitors',
        totalLabel: 'Total visitors',
        modeField: 'visitor_mode',
        totalField: 'visitors_total',
        naField: 'visitors_not_applicable',
        switchRoute: 'projects.bi.switch-visitor-mode',
    },
    {
        key: 'sold_tickets',
        label: 'Sold tickets',
        totalLabel: 'Total sold tickets',
        modeField: 'sold_tickets_mode',
        totalField: 'sold_tickets_total',
        naField: 'sold_tickets_not_applicable',
        switchRoute: 'projects.bi.switch-sold-tickets-mode',
    },
    {
        key: 'revenue',
        label: 'Revenue',
        totalLabel: 'Total revenue',
        modeField: 'revenue_mode',
        totalField: 'revenue_total',
        naField: 'revenue_not_applicable',
        switchRoute: 'projects.bi.switch-revenue-mode',
    },
];

const modes = reactive({ visitors: 'total', sold_tickets: 'total', revenue: 'total' });
const totals = reactive({ visitors: null, sold_tickets: null, revenue: null });
const notApplicable = reactive({ visitors: false, sold_tickets: false, revenue: false });

const showModeModal = ref(false);
const pendingSwitch = ref(null);

watch(() => props.biData, (val) => {
    if (!val) return;
    metrics.forEach((metric) => {
        modes[metric.key] = val[metric.modeField] ?? 'total';
        totals[metric.key] = val[metric.totalField];
        notApplicable[metric.key] = !!val[metric.naField];
    });
}, { immediate: true });

const visitorsEstimated = computed(() => !!props.metricsSummary?.visitors_estimated);

const perEventFields = computed(() => metrics
    .filter(metric => modes[metric.key] === 'per_event' && !notApplicable[metric.key])
    .map(metric => ({ key: metric.key, label: metric.label })));

const showOccupancy = computed(() => modes.sold_tickets === 'per_event' && !notApplicable.sold_tickets);

const effectiveCapacities = computed(() => {
    const overrides = new Map(props.roomCapacities.map(c => [c.room_id, c.capacity_override]));
    const result = {};
    props.projectRooms.forEach((room) => {
        result[room.id] = overrides.get(room.id) ?? room.default_capacity ?? null;
    });
    return result;
});

const numberFmt = new Intl.NumberFormat('de-DE');
const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });

const formatInt = (v) => numberFmt.format(v ?? 0);

const perEventSummary = (metric) => {
    const entries = props.eventData.filter(e => e[metric.key] !== null && e[metric.key] !== undefined);
    if (entries.length === 0) return t('No entries yet.');
    const sum = entries.reduce((acc, e) => acc + Number(e[metric.key] || 0), 0);
    const formatted = metric.key === 'revenue' ? currencyFmt.format(sum) : numberFmt.format(sum);
    return `${formatted} (${entries.length}/${props.projectEvents.length})`;
};

const onToggleChange = (metric, isPerEvent) => {
    pendingSwitch.value = { metric, mode: isPerEvent ? 'per_event' : 'total' };
    showModeModal.value = true;
};

const confirmModeSwitch = async () => {
    const { metric, mode } = pendingSwitch.value;
    showModeModal.value = false;
    modes[metric.key] = mode;
    try {
        await axios.put(route(metric.switchRoute, props.projectId), { mode });
        emit('updated');
    } catch (error) {
        console.error('Error switching input mode', error);
    }
};

const toggleNotApplicable = async (metric, value) => {
    notApplicable[metric.key] = value;
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), {
            [metric.naField]: value,
        });
        emit('updated');
    } catch (error) {
        console.error('Error toggling not-applicable flag', error);
    }
};

const saveTotal = async (metric) => {
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), {
            [metric.totalField]: totals[metric.key] === '' ? null : totals[metric.key],
        });
        emit('updated');
    } catch (error) {
        console.error('Error saving total value', error);
    }
};
</script>
