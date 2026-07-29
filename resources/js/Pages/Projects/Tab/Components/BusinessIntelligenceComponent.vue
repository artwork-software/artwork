<template>
    <div class="print:break-before-auto">
        <div v-if="loadError" class="mb-2 text-xs text-rose-600">
            {{ $t(loadError) }}
        </div>
        <div v-else-if="isLoading" class="mb-2 text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>
        <template v-else>
            <div class="sm:flex sm:items-center mb-6">
                <div class="sm:flex-auto flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="block text-2xl font-bold text-gray-900">{{ $t('Business Intelligence') }}</span>
                    <!-- Ist/Plan-Umschalter -->
                    <div
                        v-if="canEditComponent || hasPlan"
                        class="flex rounded-lg border border-gray-200 p-0.5 print:hidden"
                    >
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="scope === 'actual' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                            @click="scope = 'actual'"
                        >
                            {{ $t('Actual') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1 text-xs font-medium transition"
                            :class="scope === 'plan' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                            @click="scope = 'plan'"
                        >
                            {{ $t('Plan') }}
                        </button>
                    </div>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-x-4 print:hidden" v-if="canEditComponent">
                    <BaseUIButton @click="showExportModal = true" :label="$t('Export')" icon="IconFileExport" v-if="canExport || hasAdminRole()"/>
                </div>
            </div>

            <!-- ================= PLAN-ANSICHT ================= -->
            <template v-if="scope === 'plan'">
                <div class="mb-4 rounded-xl border border-dashed border-indigo-300 bg-indigo-50/50 px-4 py-2.5 flex items-center gap-2">
                    <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                        {{ $t('Plan') }}
                    </span>
                    <span class="text-sm text-indigo-900">
                        {{ $t('You are editing plan values. Actual figures stay untouched.') }}
                    </span>
                </div>

                <!-- Schnellstart, solange kein Plan-Datensatz existiert -->
                <div v-if="!planData" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">{{ $t('Record plan values') }}</h4>
                    <p class="text-xs text-gray-500 mb-4 max-w-2xl">
                        {{ $t('Plan values hold the expected visitors, tickets and revenue for this project. Once actuals come in, the plan-vs-actual comparison appears in the actual view.') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <BaseUIButton :label="$t('Start empty')" hide-icon @click="initializePlan('empty')" />
                        <BaseUIButton
                            :label="$t('Copy structure from actuals')"
                            hide-icon
                            white
                            @click="initializePlan('copy_actual_structure')"
                        />
                        <BaseUIButton
                            :label="$t('Copy values from another project')"
                            hide-icon
                            white
                            @click="showPlanCopySearch = !showPlanCopySearch"
                        />
                    </div>
                    <div v-if="showPlanCopySearch" class="mt-4 max-w-md">
                        <BaseInput
                            id="bi_plan_copy_search"
                            v-model="planCopyQuery"
                            :label="$t('Search project')"
                            @update:model-value="searchPlanCopyProjects"
                        />
                        <div v-if="planCopyResults.length > 0" class="mt-2 divide-y divide-gray-100 rounded-md border border-gray-200">
                            <button
                                v-for="result in planCopyResults"
                                :key="result.id"
                                type="button"
                                class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                @click="initializePlan('copy_project', result.id)"
                            >
                                {{ result.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <BiKpiHeader
                        :summary="planMetricsSummary ?? {}"
                        :event-data="planEventData"
                        :project-events="projectEvents"
                    />

                    <BiSectionCard :title="$t('Audience & revenue')" :icon="IconUsers">
                        <BiAudienceRevenueSection
                            :bi-data="planData"
                            :metrics-summary="planMetricsSummary ?? {}"
                            :event-data="planEventData"
                            :project-events="projectEvents"
                            :room-capacities="roomCapacities"
                            :project-rooms="projectRooms"
                            :audience-categories="audienceCategories"
                            :audience-category-values="planAudienceCategoryValues"
                            :budget-suggestions="budgetSuggestions"
                            scope="plan"
                            :can-edit="canEditComponent"
                            :project-id="project.id"
                            @updated="fetchData"
                        />
                    </BiSectionCard>

                    <p class="mt-4 text-xs text-gray-400">
                        {{ $t('Production data, capacities, time effort and custom fields are maintained in the actual view — they apply to both.') }}
                    </p>
                </template>
            </template>

            <!-- ================= IST-ANSICHT ================= -->
            <template v-else>
            <!-- Kennzahlen auf einen Blick -->
            <BiKpiHeader
                :summary="metricsSummary"
                :event-data="eventData"
                :project-events="projectEvents"
            />

            <!-- Plan-Ist-Gegenüberstellung, sobald Planwerte existieren -->
            <BiPlanComparisonCard
                v-if="hasPlan"
                :plan-comparison="planComparison"
                :event-data="eventData"
                :plan-event-data="planEventData"
                :project-events="projectEvents"
            />

            <!-- Freier Zeitraumvergleich (A vs. B) auf Projektebene -->
            <div class="mb-6">
                <BiPeriodComparisonCard
                    :project-id="project.id"
                    :project-period="projectPeriod"
                />
            </div>

            <!-- Datenqualität: was fehlt für belastbare Auswertungen? -->
            <div
                v-if="canEditComponent && dataQuality.missing.length > 0"
                class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 print:hidden"
            >
                <div class="flex items-center justify-between gap-4 mb-2">
                    <span class="text-sm font-medium text-amber-900">
                        {{ $t('Data quality') }}: {{ dataQuality.filled }}/{{ dataQuality.total }} {{ $t('filled in') }}
                    </span>
                    <div class="h-1.5 w-32 rounded-full bg-amber-200/70 overflow-hidden shrink-0">
                        <div
                            class="h-full rounded-full bg-amber-500"
                            :style="{ width: (dataQuality.filled / dataQuality.total * 100) + '%' }"
                        ></div>
                    </div>
                </div>
                <p class="text-xs text-amber-800">
                    {{ $t('Still missing') }}:
                    <button
                        v-for="item in dataQuality.missing"
                        :key="item"
                        type="button"
                        class="ml-1.5 inline-flex items-center gap-1 rounded-full border border-amber-300 bg-white/70 px-2 py-0.5 font-medium text-amber-900 hover:bg-white transition"
                        @click="goToMissingItem(item)"
                    >
                        {{ $t(item) }}
                        <IconArrowDown class="size-3" />
                    </button>
                    <span class="ml-1">— {{ $t('evaluations stay incomplete until these are recorded.') }}</span>
                </p>
            </div>

            <!-- Positives Signal, wenn alles gepflegt ist -->
            <div
                v-else-if="canEditComponent && dataQuality.total > 0"
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 print:hidden flex items-center gap-2"
            >
                <IconCircleCheck class="size-4 text-emerald-600 shrink-0" />
                <span class="text-sm text-emerald-800">
                    {{ $t('Data quality') }}: {{ $t('All key figures are recorded.') }}
                </span>
            </div>

            <div class="space-y-4">
                <BiSectionCard ref="audienceCard" :title="$t('Audience & revenue')" :icon="IconUsers" :completeness="audienceCompleteness">
                    <BiAudienceRevenueSection
                        :bi-data="biData"
                        :metrics-summary="metricsSummary"
                        :event-data="eventData"
                        :project-events="projectEvents"
                        :room-capacities="roomCapacities"
                        :project-rooms="projectRooms"
                        :audience-categories="audienceCategories"
                        :audience-category-values="audienceCategoryValues"
                        :budget-suggestions="budgetSuggestions"
                        :can-edit="canEditComponent"
                        :project-id="project.id"
                        @updated="fetchData"
                    />
                </BiSectionCard>

                <BiSectionCard :title="$t('Production data')" :icon="IconMasksTheater">
                    <BiCoreDataSection
                        :bi-data="biData"
                        :can-edit="canEditComponent"
                        :project-id="project.id"
                        @updated="fetchData"
                    />
                </BiSectionCard>

                <BiSectionCard
                    ref="capacityCard"
                    :title="$t('Room capacities & utilisation')"
                    :icon="IconArmchair"
                    :completeness="projectRooms.length > 0 ? capacityCompleteness : null"
                >
                    <BiRoomCapacitySection
                        :room-capacities="roomCapacities"
                        :project-rooms="projectRooms"
                        :can-edit="canEditComponent"
                        :project-id="project.id"
                        @updated="fetchData"
                    />
                </BiSectionCard>

                <BiSectionCard :title="$t('Automatically derived')" :icon="IconCalculator">
                    <div class="space-y-4">
                        <BiTagCountsSection :tag-counts="tagCounts" />
                        <BiDerivedValuesSection :derived-values="derivedValues" />
                    </div>
                </BiSectionCard>

                <BiSectionCard :title="$t('Time efforts')" :icon="IconClockHour4" :default-open="timeEfforts.length > 0">
                    <BiTimeEffortSection
                        :time-efforts="timeEfforts"
                        :can-edit="canEditComponent"
                        :project-id="project.id"
                        @updated="fetchData"
                    />
                </BiSectionCard>

                <BiSectionCard :title="$t('Snapshots')" :icon="IconCamera" :default-open="snapshots.length > 0">
                    <BiSnapshotSection
                        :snapshots="snapshots"
                        :can-edit="canEditComponent"
                        :project-id="project.id"
                        :current="{ bi_data: biData, event_data: eventData, derived_values: derivedValues, tag_counts: tagCounts, category_values: audienceCategoryValues }"
                        :audience-categories="audienceCategories"
                        @updated="fetchData"
                    />
                </BiSectionCard>

                <BiSectionCard
                    v-if="biCustomFields.length > 0"
                    :title="$t('Custom fields')"
                    :icon="IconForms"
                    :completeness="customFieldsCompleteness"
                >
                    <BiCustomFieldsSection
                        :fields="biCustomFields"
                        :field-values="biCustomFieldValues"
                        :can-edit="canEditComponent"
                        :project-id="project.id"
                        @updated="fetchData"
                    />
                </BiSectionCard>
            </div>
            </template>

            <BiExportModal
                v-if="showExportModal"
                :project="project"
                :tag-counts="tagCounts"
                :bi-custom-fields="biCustomFields"
                :audience-categories="audienceCategories"
                :default-date-from="projectPeriod?.from ?? null"
                :default-date-to="projectPeriod?.to ?? null"
                @close="showExportModal = false"
            />

            <BiSaveIndicator :status="saveFeedback.status.value" />
        </template>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, nextTick } from 'vue';
import { usePage } from "@inertiajs/vue3";
import {
    IconUsers,
    IconMasksTheater,
    IconArmchair,
    IconCalculator,
    IconClockHour4,
    IconCamera,
    IconForms,
    IconArrowDown,
    IconCircleCheck,
} from '@tabler/icons-vue';
import { usePermission } from "@/Composeables/Permission.js";
import { provideBiSaveFeedback } from "@/Composeables/BiSaveFeedback.js";
import BiSaveIndicator from "@/Pages/Projects/Components/BiComponents/BiSaveIndicator.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BiKpiHeader from "@/Pages/Projects/Components/BiComponents/BiKpiHeader.vue";
import BiSectionCard from "@/Pages/Projects/Components/BiComponents/BiSectionCard.vue";
import BiAudienceRevenueSection from "@/Pages/Projects/Components/BiComponents/BiAudienceRevenueSection.vue";
import BiCoreDataSection from "@/Pages/Projects/Components/BiComponents/BiCoreDataSection.vue";
import BiRoomCapacitySection from "@/Pages/Projects/Components/BiComponents/BiRoomCapacitySection.vue";
import BiTagCountsSection from "@/Pages/Projects/Components/BiComponents/BiTagCountsSection.vue";
import BiDerivedValuesSection from "@/Pages/Projects/Components/BiComponents/BiDerivedValuesSection.vue";
import BiTimeEffortSection from "@/Pages/Projects/Components/BiComponents/BiTimeEffortSection.vue";
import BiSnapshotSection from "@/Pages/Projects/Components/BiComponents/BiSnapshotSection.vue";
import BiCustomFieldsSection from "@/Pages/Projects/Components/BiComponents/BiCustomFieldsSection.vue";
import BiExportModal from "@/Pages/Projects/Components/BiComponents/BiExportModal.vue";
import BiPlanComparisonCard from "@/Pages/Projects/Components/BiComponents/BiPlanComparisonCard.vue";
import BiPeriodComparisonCard from "@/Pages/Projects/Components/BiComponents/BiPeriodComparisonCard.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    project: { type: Object, required: true },
    canEditComponent: { type: Boolean, default: false },
});

const { can, hasAdminRole } = usePermission(usePage().props);
const canExport = can('can export bi data');

// Ein Statusindikator für alle Sektions-Saves (Sektionen injizieren den Runner)
const saveFeedback = provideBiSaveFeedback();

const isLoading = ref(true);
const loadError = ref(null);
const showExportModal = ref(false);

const metricsSummary = ref({});
const biData = ref(null);
const eventData = ref([]);
const roomCapacities = ref([]);
const audienceCategories = ref([]);
const audienceCategoryValues = ref([]);

// --- Plan/Ist ---
const scope = ref('actual');
const planData = ref(null);
const planEventData = ref([]);
const planMetricsSummary = ref(null);
const planComparison = ref(null);
const planAudienceCategoryValues = ref([]);
const budgetSuggestions = ref(null);

const hasPlan = computed(() => !!planComparison.value?.has_plan || !!planData.value);

const showPlanCopySearch = ref(false);
const planCopyQuery = ref('');
const planCopyResults = ref([]);

let planSearchTimer = null;
const searchPlanCopyProjects = () => {
    clearTimeout(planSearchTimer);
    if (!planCopyQuery.value || planCopyQuery.value.length < 2) {
        planCopyResults.value = [];
        return;
    }
    planSearchTimer = setTimeout(async () => {
        const { data } = await axios.get(route('projects.search'), { params: { query: planCopyQuery.value } });
        planCopyResults.value = (data ?? []).filter(p => p.id !== props.project.id).slice(0, 8);
    }, 300);
};

const initializePlan = async (mode, sourceProjectId = null) => {
    const ok = await saveFeedback.run(
        () => axios.post(route('projects.bi.plan.initialize', props.project.id), {
            mode,
            source_project_id: sourceProjectId,
        })
    );
    if (ok) {
        showPlanCopySearch.value = false;
        planCopyQuery.value = '';
        planCopyResults.value = [];
        await fetchData();
    }
};
const derivedValues = ref({});
const tagCounts = ref([]);
const timeEfforts = ref([]);
const snapshots = ref([]);
const projectEvents = ref([]);
const projectRooms = ref([]);
const biCustomFields = ref([]);
const biCustomFieldValues = ref({});
const projectPeriod = ref(null);

// Eine Kennzahl gilt als gepflegt, wenn im aktiven Modus mindestens ein Wert erfasst ist
const metricFilled = (metricKey, modeField, totalField) => {
    if (!biData.value) return false;
    if ((biData.value[modeField] ?? 'total') === 'total') {
        return biData.value[totalField] !== null && biData.value[totalField] !== undefined;
    }
    return eventData.value.some(e => e[metricKey] !== null && e[metricKey] !== undefined);
};

// Abgedeckt = erfasst ODER bewusst als "nicht relevant" markiert ODER (Besucher) geschätzt
const metricCovered = (metricKey, modeField, totalField, naField) => {
    if (biData.value?.[naField]) return true;
    if (metricKey === 'visitors' && metricsSummary.value?.visitors_estimated) return true;
    return metricFilled(metricKey, modeField, totalField);
};

const audienceCompleteness = computed(() => {
    const filled = [
        metricCovered('visitors', 'visitor_mode', 'visitors_total', 'visitors_not_applicable'),
        metricCovered('sold_tickets', 'sold_tickets_mode', 'sold_tickets_total', 'sold_tickets_not_applicable'),
        metricCovered('revenue', 'revenue_mode', 'revenue_total', 'revenue_not_applicable'),
    ].filter(Boolean).length;
    return { filled, total: 3 };
});

const capacityCompleteness = computed(() => {
    const overrides = new Map(roomCapacities.value.map(c => [c.room_id, c.capacity_override]));
    const filled = projectRooms.value.filter(
        room => (overrides.get(room.id) ?? room.default_capacity) != null
    ).length;
    return { filled, total: projectRooms.value.length };
});

const customFieldsCompleteness = computed(() => {
    const filled = biCustomFields.value.filter((field) => {
        const data = biCustomFieldValues.value[field.id]?.data;
        if (!data) return false;
        if (field.type === 'Checkbox') return true;
        if (field.type === 'DropDown') return !!data.selected;
        return !!(data.text && String(data.text).trim() !== '');
    }).length;
    return { filled, total: biCustomFields.value.length };
});

const dataQuality = computed(() => {
    const items = [
        { label: 'Visitors', filled: metricCovered('visitors', 'visitor_mode', 'visitors_total', 'visitors_not_applicable') },
        { label: 'Sold tickets', filled: metricCovered('sold_tickets', 'sold_tickets_mode', 'sold_tickets_total', 'sold_tickets_not_applicable') },
        { label: 'Revenue', filled: metricCovered('revenue', 'revenue_mode', 'revenue_total', 'revenue_not_applicable') },
        ...(projectRooms.value.length > 0
            ? [{ label: 'Room capacities', filled: capacityCompleteness.value.filled >= capacityCompleteness.value.total }]
            : []),
    ];
    return {
        filled: items.filter(i => i.filled).length,
        total: items.length,
        missing: items.filter(i => !i.filled).map(i => i.label),
    };
});

// Banner-Klick: zugehörige Karte aufklappen und hinscrollen
const audienceCard = ref(null);
const capacityCard = ref(null);

const missingItemTargets = {
    'Visitors': 'audienceCard',
    'Sold tickets': 'audienceCard',
    'Revenue': 'audienceCard',
    'Room capacities': 'capacityCard',
};

const goToMissingItem = async (item) => {
    const card = missingItemTargets[item] === 'capacityCard' ? capacityCard.value : audienceCard.value;
    if (!card) return;
    card.expand();
    await nextTick();
    card.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

// Sequenz-Guard: bei schnell aufeinanderfolgenden Saves darf eine überholte
// Antwort den State einer neueren nicht überschreiben
let fetchSeq = 0;

const fetchData = async () => {
    const seq = ++fetchSeq;
    try {
        const response = await axios.get(route('projects.bi.show', props.project.id));
        if (seq !== fetchSeq) return;
        metricsSummary.value = response.data.metrics_summary || {};
        biData.value = response.data.bi_data;
        eventData.value = response.data.event_data;
        roomCapacities.value = response.data.room_capacities;
        audienceCategories.value = response.data.audience_categories || [];
        audienceCategoryValues.value = response.data.audience_category_values || [];
        planData.value = response.data.plan_data || null;
        planEventData.value = response.data.plan_event_data || [];
        planMetricsSummary.value = response.data.plan_metrics_summary || null;
        planComparison.value = response.data.plan_comparison || null;
        planAudienceCategoryValues.value = response.data.plan_audience_category_values || [];
        budgetSuggestions.value = response.data.budget_suggestions || null;
        derivedValues.value = response.data.derived_values;
        tagCounts.value = response.data.tag_counts;
        timeEfforts.value = response.data.time_efforts;
        snapshots.value = response.data.snapshots;
        projectEvents.value = response.data.project_events;
        projectRooms.value = response.data.project_rooms;
        biCustomFields.value = response.data.bi_custom_fields || [];
        biCustomFieldValues.value = response.data.bi_custom_field_values || {};
        projectPeriod.value = response.data.project_period || null;
        loadError.value = null;
    } catch (error) {
        if (seq !== fetchSeq) return;
        loadError.value = 'Error loading BI data.';
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchData);
</script>
