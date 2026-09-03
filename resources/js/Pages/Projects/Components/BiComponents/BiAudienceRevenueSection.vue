<template>
    <div>
        <!-- Modus + Gesamtwert je Kennzahl -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
            <div
                v-for="metric in metrics"
                :key="metric.key"
                class="rounded-lg border border-border-subtle p-3"
                :class="{ 'bg-surface-sunken opacity-75': notApplicable[metric.key] }"
            >
                <!-- Kopf: Kennzahl-Name in eigener Zeile, Erfassungsart-Schalter darunter —
                     nebeneinander brach "Pro Termin" in den Schalter hinein -->
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5 mb-2">
                    <span class="text-sm font-medium text-text">{{ $t(metric.label) }}</span>
                    <SwitchDualLabel
                        v-if="canEdit && !notApplicable[metric.key] && metric.switchRoute"
                        class="whitespace-nowrap"
                        :model-value="modes[metric.key] === 'per_event'"
                        :left-label="$t('Total')"
                        :right-label="$t('Per event')"
                        :tooltip-text="$t('Total: one figure for the whole production. Per event: one figure per performance — needed for period comparisons and the monthly trend. Switching discards the figures of the other mode.')"
                        size="sm"
                        @change="isPerEvent => onToggleChange(metric, isPerEvent)"
                    />
                    <span v-else-if="!notApplicable[metric.key] && metric.switchRoute" class="text-xs text-text-subtle">
                        {{ modes[metric.key] === 'per_event' ? $t('Per event') : $t('Total') }}
                    </span>
                </div>

                <template v-if="notApplicable[metric.key]">
                    <span class="inline-flex rounded-full bg-border-subtle px-2.5 py-0.5 text-xs font-medium text-text-muted">
                        {{ $t('Not relevant for this project') }}
                    </span>
                </template>
                <template v-else>
                    <BaseInput
                        v-if="modes[metric.key] === 'total'"
                        type="number"
                        :id="'bi_total_' + scope + '_' + metric.key"
                        v-model.number="totals[metric.key]"
                        :label="$t(metric.totalLabel)"
                        :disabled="!canEdit"
                        :min="0"
                        :step="metric.key === 'revenue' || metric.key === 'costs' ? 0.01 : 1"
                        @change="saveTotal(metric)"
                    />
                    <p v-else class="text-xs text-text-subtle">
                        {{ $t('Recorded per event in the table below.') }}
                        <span class="font-medium text-text-muted">{{ perEventSummary(metric) }}</span>
                    </p>
                    <p
                        v-if="metric.key === 'visitors' && visitorsEstimated"
                        class="mt-1.5 text-xs text-accent-600"
                    >
                        ≈ {{ $t('Currently estimated from sold tickets') }}: {{ formatInt(metricsSummary.visitors) }}
                    </p>

                    <!-- Umsatz-Vorschlag aus dem Budget-Modul (Quelle je Scope) -->
                    <div
                        v-if="metric.key === 'revenue' && revenueSuggestion !== null && modes.revenue === 'total'"
                        class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs print:hidden"
                    >
                        <span class="text-text-subtle">
                            {{ scope === 'plan' ? $t('Income side of the project budget') : $t('Revenue from Sage bookings') }}:
                            <span class="font-medium text-text-muted">{{ currencyFmt.format(revenueSuggestion) }}</span>
                        </span>
                        <button
                            v-if="canEdit && Number(totals.revenue ?? NaN) !== revenueSuggestion"
                            type="button"
                            class="rounded-md border border-accent-200 bg-accent-50 px-2 py-0.5 font-medium text-accent-700 hover:bg-accent-100 transition"
                            @click="adoptRevenueSuggestion"
                        >
                            {{ $t('Adopt value') }}
                        </button>
                        <span v-else-if="biData?.revenue_source" class="text-success">
                            ✓ {{ $t('adopted') }}
                        </span>
                    </div>

                    <!-- Kosten-Vorschlag aus dem Budget-Modul (Quelle je Scope) -->
                    <div
                        v-if="metric.key === 'costs' && costSuggestion !== null"
                        class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs print:hidden"
                    >
                        <span class="text-text-subtle">
                            {{ scope === 'plan' ? $t('Expense side of the project budget') : $t('Costs from Sage bookings') }}:
                            <span class="font-medium text-text-muted">{{ currencyFmt.format(costSuggestion) }}</span>
                        </span>
                        <button
                            v-if="canEdit && Number(totals.costs ?? NaN) !== costSuggestion"
                            type="button"
                            class="rounded-md border border-accent-200 bg-accent-50 px-2 py-0.5 font-medium text-accent-700 hover:bg-accent-100 transition"
                            @click="adoptCostSuggestion"
                        >
                            {{ $t('Adopt value') }}
                        </button>
                        <span v-else-if="biData?.costs_source" class="text-success">
                            ✓ {{ $t('adopted') }}
                        </span>
                    </div>
                </template>

                <BaseCheckbox
                    v-if="canEdit"
                    :id="'bi_na_' + scope + '_' + metric.key"
                    :model-value="notApplicable[metric.key]"
                    @update:model-value="checked => toggleNotApplicable(metric, checked)"
                    :label="$t('Not relevant for this project')"
                    class="mt-2 print:hidden"
                />
            </div>
        </div>

        <!-- Optionale Aufschlüsselung nach Besucher*innen-Kategorien -->
        <div
            v-if="visibleCategories.length > 0 && !notApplicable.sold_tickets"
            class="mb-4 rounded-lg border border-border-subtle"
        >
            <button
                type="button"
                class="flex w-full items-center justify-between gap-2 p-3 text-left"
                @click="showCategories = !showCategories"
            >
                <span class="flex items-center gap-2 text-sm font-medium text-text">
                    <IconChevronDown class="size-4 transition-transform" :class="{ '-rotate-90': !showCategories }" />
                    {{ $t('Break down by category') }}
                    <span class="text-xs font-normal text-text-subtle">({{ $t('optional') }})</span>
                </span>
                <span v-if="categoryTotalSum !== null" class="text-xs text-text-subtle">
                    {{ $t('Tickets issued') }}: {{ formatInt(categoryTotalSum) }}
                </span>
            </button>

            <div v-show="showCategories" class="px-3 pb-3">
                <template v-if="modes.sold_tickets === 'total'">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div v-for="category in visibleCategories" :key="category.id">
                            <BaseInput
                                type="number"
                                :id="'bi_cat_' + scope + '_' + category.id"
                                v-model.number="categoryTotals[category.id]"
                                :label="category.name"
                                without-translation
                                :disabled="!canEdit || !category.is_active"
                                :min="0"
                                :step="1"
                                @change="saveCategoryTotal(category)"
                            />
                            <p class="mt-0.5 text-[11px] text-text-subtle">
                                {{ pricingTypeLabel(category.pricing_type) }}
                                <span v-if="!category.is_active"> · {{ $t('deactivated') }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Abgleich: Direktwert vs. Summe der zahlenden Kategorien -->
                    <div
                        v-if="soldTicketsMismatch"
                        class="mt-3 flex flex-wrap items-center gap-2 rounded-md border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning print:hidden"
                    >
                        <span>
                            {{ $t('Sum of paying categories') }} ({{ formatInt(paidCategorySum) }})
                            ≠ {{ $t('Total sold tickets') }} ({{ formatInt(totals.sold_tickets) }})
                        </span>
                        <button
                            v-if="canEdit"
                            type="button"
                            class="rounded-md border border-warning-border bg-white px-2 py-0.5 font-medium text-warning hover:bg-warning-surface transition"
                            @click="adoptPaidCategorySum"
                        >
                            {{ $t('Adopt sum') }}
                        </button>
                    </div>
                </template>
                <template v-else>
                    <BaseCheckbox
                        :id="'bi_show_category_columns_' + scope"
                        v-model="showCategoryColumns"
                        :label="$t('Show category columns in the events table')"
                        class="print:hidden"
                    />
                    <p class="mt-1 text-xs text-text-subtle">
                        {{ $t('Categories follow the per-event mode of sold tickets and are recorded in the table below.') }}
                    </p>
                </template>
            </div>
        </div>

        <!-- Eine Tabelle für alle Pro-Termin-Kennzahlen -->
        <BiEventMetricsTable
            v-if="tableFields.length > 0"
            :event-data="eventData"
            :project-events="projectEvents"
            :can-edit="canEdit"
            :project-id="projectId"
            :fields="tableFields"
            :category-values="audienceCategoryValues"
            :scope="scope"
            :show-occupancy="showOccupancy"
            :effective-capacities="effectiveCapacities"
            @updated="$emit('updated')"
        />

        <BiModeSwitchModal
            v-if="showModeModal"
            :metric-label="$t(pendingSwitch?.metric?.label ?? '')"
            :entry-count="pendingSwitch?.entryCount ?? 0"
            @confirm="confirmModeSwitch"
            @close="showModeModal = false"
        />
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { IconChevronDown } from '@tabler/icons-vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import SwitchDualLabel from '@/Artwork/Toggles/SwitchDualLabel.vue';
import BiEventMetricsTable from '@/Pages/Projects/Components/BiComponents/BiEventMetricsTable.vue';
import BiModeSwitchModal from '@/Pages/Projects/Components/BiComponents/BiModeSwitchModal.vue';
import { useTranslation } from '@/Composeables/Translation.js';
import { useBiSaveFeedback } from '@/Composeables/BiSaveFeedback.js';

const t = useTranslation();
const biSave = useBiSaveFeedback();

const props = defineProps({
    biData: { type: Object, default: null },
    metricsSummary: { type: Object, default: () => ({}) },
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
    roomCapacities: { type: Array, default: () => [] },
    projectRooms: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
    audienceCategories: { type: Array, default: () => [] },
    audienceCategoryValues: { type: Array, default: () => [] },
    // Plan/Ist-Dimension: bestimmt Datensatz für Anzeige und Speichern
    scope: { type: String, default: 'actual' },
    // Umsatz-Vorschläge aus dem Budget-Modul ({plan, actual})
    budgetSuggestions: { type: Object, default: null },
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
    {
        // Kosten werden nur als Projekt-Gesamtwert erfasst (kein per-Event-Modus)
        key: 'costs',
        label: 'Costs',
        totalLabel: 'Total costs',
        modeField: null,
        totalField: 'costs_total',
        naField: 'costs_not_applicable',
        switchRoute: null,
    },
];

const modes = reactive({ visitors: 'total', sold_tickets: 'total', revenue: 'total', costs: 'total' });
const totals = reactive({ visitors: null, sold_tickets: null, revenue: null, costs: null });
const notApplicable = reactive({ visitors: false, sold_tickets: false, revenue: false, costs: false });

const showModeModal = ref(false);
const pendingSwitch = ref(null);

watch(() => props.biData, (val) => {
    if (!val) return;
    metrics.forEach((metric) => {
        modes[metric.key] = metric.modeField ? (val[metric.modeField] ?? 'total') : 'total';
        // Fokussierte Eingabe nicht überschreiben: der Refetch nach einem Save
        // (auch aus anderen Sektionen) darf laufendes Tippen nicht zurücksetzen
        const input = document.getElementById('bi_total_' + props.scope + '_' + metric.key);
        if (!input || document.activeElement !== input) {
            totals[metric.key] = val[metric.totalField];
        }
        notApplicable[metric.key] = !!val[metric.naField];
    });
}, { immediate: true });

const visitorsEstimated = computed(() => !!props.metricsSummary?.visitors_estimated);

const perEventFields = computed(() => metrics
    .filter(metric => modes[metric.key] === 'per_event' && !notApplicable[metric.key])
    .map(metric => ({ key: metric.key, label: metric.label })));

// --- Besucher*innen-Kategorien (optionale Aufschlüsselung) ---

// Aktive Kategorien plus deaktivierte, für die noch Werte existieren (read-only)
const visibleCategories = computed(() => props.audienceCategories.filter((category) => {
    if (category.is_active) return true;
    return props.audienceCategoryValues.some(
        v => v.bi_audience_category_id === category.id && v.quantity !== null
    );
}));

const showCategories = ref(false);
const showCategoryColumns = ref(localStorage.getItem('bi_show_category_columns') === '1');
watch(showCategoryColumns, v => localStorage.setItem('bi_show_category_columns', v ? '1' : '0'));

// Gesamtwerte je Kategorie (event_id null) — gleiche Fokus-Schutz-Mechanik wie totals
const categoryTotals = reactive({});
watch(() => props.audienceCategoryValues, (values) => {
    const totalsFromServer = new Map(
        (values ?? [])
            .filter(v => v.event_id === null || v.event_id === undefined)
            .map(v => [v.bi_audience_category_id, v.quantity])
    );
    props.audienceCategories.forEach((category) => {
        const input = document.getElementById('bi_cat_' + props.scope + '_' + category.id);
        if (!input || document.activeElement !== input) {
            categoryTotals[category.id] = totalsFromServer.get(category.id) ?? null;
        }
    });
    // Aufschlüsselung initial aufklappen, wenn bereits Werte erfasst sind
    if (totalsFromServer.size > 0 || (values ?? []).length > 0) {
        showCategories.value = true;
    }
}, { immediate: true });

const recordedCategoryTotals = computed(() => props.audienceCategories
    .map(category => ({ category, value: categoryTotals[category.id] }))
    .filter(({ value }) => value !== null && value !== undefined && value !== ''));

const categoryTotalSum = computed(() => {
    if (modes.sold_tickets !== 'total' || recordedCategoryTotals.value.length === 0) return null;
    return recordedCategoryTotals.value.reduce((sum, { value }) => sum + Number(value), 0);
});

const paidCategorySum = computed(() => {
    const paid = recordedCategoryTotals.value.filter(({ category }) => category.pricing_type !== 'free');
    if (paid.length === 0) return null;
    return paid.reduce((sum, { value }) => sum + Number(value), 0);
});

const soldTicketsMismatch = computed(() =>
    modes.sold_tickets === 'total'
    && paidCategorySum.value !== null
    && totals.sold_tickets !== null && totals.sold_tickets !== undefined && totals.sold_tickets !== ''
    && Number(totals.sold_tickets) !== paidCategorySum.value
);

const pricingTypeLabel = (type) => ({
    full: t('Full price'),
    reduced: t('Reduced'),
    free: t('Free'),
}[type] ?? type);

const saveCategoryTotal = async (category) => {
    const raw = categoryTotals[category.id];
    const ok = await biSave.run(
        () => axios.put(route('projects.bi.upsert-category-values', props.projectId), {
            scope: props.scope,
            values: [{
                bi_audience_category_id: category.id,
                event_id: null,
                quantity: raw === '' || raw === null || raw === undefined ? null : Number(raw),
            }],
        })
    );
    if (ok) {
        emit('updated');
    }
};

const adoptPaidCategorySum = async () => {
    totals.sold_tickets = paidCategorySum.value;
    await saveTotal(metrics.find(m => m.key === 'sold_tickets'));
};

// --- Umsatz-Vorschlag aus dem Budget-Modul ---

const revenueSuggestion = computed(() => {
    const suggestion = props.scope === 'plan'
        ? props.budgetSuggestions?.plan
        : props.budgetSuggestions?.actual;
    return suggestion ?? null;
});

// --- Kosten-Vorschlag aus dem Budget-Modul ---

const costSuggestion = computed(() => {
    const suggestion = props.scope === 'plan'
        ? props.budgetSuggestions?.costs_plan
        : props.budgetSuggestions?.costs_actual;
    return suggestion ?? null;
});

const adoptCostSuggestion = async () => {
    totals.costs = costSuggestion.value;
    const ok = await biSave.run(
        () => axios.put(route('projects.bi.update-data', props.projectId), {
            costs_total: costSuggestion.value,
            costs_source: props.scope === 'plan' ? 'budget_expense' : 'sage',
            scope: props.scope,
        })
    );
    if (ok) {
        emit('updated');
    }
};

const adoptRevenueSuggestion = async () => {
    totals.revenue = revenueSuggestion.value;
    const ok = await biSave.run(
        () => axios.put(route('projects.bi.update-data', props.projectId), {
            revenue_total: revenueSuggestion.value,
            revenue_source: props.scope === 'plan' ? 'budget_income' : 'sage',
            scope: props.scope,
        })
    );
    if (ok) {
        emit('updated');
    }
};

// Kategorie-Spalten in der Termin-Tabelle (nur im Pro-Termin-Modus der Tickets)
const categoryFields = computed(() => {
    if (modes.sold_tickets !== 'per_event' || !showCategoryColumns.value) return [];
    return props.audienceCategories
        .filter(category => category.is_active)
        .map(category => ({
            key: 'cat_' + category.id,
            label: category.name,
            translate: false,
            categoryId: category.id,
        }));
});

const tableFields = computed(() => [...perEventFields.value, ...categoryFields.value]);

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

// Anzahl der Werte, die beim Verlassen des aktuellen Modus verworfen würden
const currentModeEntryCount = (metric) => {
    if (modes[metric.key] === 'total') {
        const total = totals[metric.key];
        return total !== null && total !== undefined && total !== '' ? 1 : 0;
    }
    return props.eventData.filter(
        e => e[metric.key] !== null && e[metric.key] !== undefined
    ).length;
};

const onToggleChange = (metric, isPerEvent) => {
    const mode = isPerEvent ? 'per_event' : 'total';
    const entryCount = currentModeEntryCount(metric);
    pendingSwitch.value = { metric, mode, entryCount };

    // Nichts zu verlieren → direkt wechseln, ohne Warnmodal-Reibung
    if (entryCount === 0) {
        confirmModeSwitch();
        return;
    }
    showModeModal.value = true;
};

const confirmModeSwitch = async () => {
    const { metric, mode } = pendingSwitch.value;
    showModeModal.value = false;
    const previousMode = modes[metric.key];
    modes[metric.key] = mode;
    const ok = await biSave.run(
        () => axios.put(route(metric.switchRoute, props.projectId), { mode, scope: props.scope })
    );
    if (ok) {
        emit('updated');
    } else {
        // Optimistischen Toggle zurückrollen, sonst divergieren UI und Server
        modes[metric.key] = previousMode;
    }
};

const toggleNotApplicable = async (metric, value) => {
    notApplicable[metric.key] = value;
    const ok = await biSave.run(
        () => axios.put(route('projects.bi.update-data', props.projectId), {
            [metric.naField]: value,
            scope: props.scope,
        })
    );
    if (ok) {
        emit('updated');
    } else {
        notApplicable[metric.key] = !value;
    }
};

const saveTotal = async (metric) => {
    const ok = await biSave.run(
        () => axios.put(route('projects.bi.update-data', props.projectId), {
            [metric.totalField]: totals[metric.key] === '' ? null : totals[metric.key],
            scope: props.scope,
        })
    );
    if (ok) {
        emit('updated');
    }
};
</script>
