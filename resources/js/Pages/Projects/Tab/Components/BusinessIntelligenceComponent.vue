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
                <div class="sm:flex-auto">
                    <span class="block text-2xl font-bold text-gray-900">{{ $t('Business Intelligence') }}</span>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center gap-x-4 print:hidden" v-if="canEditComponent">
                    <BaseUIButton @click="showExportModal = true" :label="$t('Export')" icon="IconFileExport" v-if="canExport || hasAdminRole()"/>
                </div>
            </div>

            <!-- Kennzahlen auf einen Blick -->
            <BiKpiHeader
                :summary="metricsSummary"
                :event-data="eventData"
                :project-events="projectEvents"
            />

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
                    <span class="font-medium">{{ dataQuality.missing.map(item => $t(item)).join(', ') }}</span>
                    — {{ $t('evaluations stay incomplete until these are recorded.') }}
                </p>
            </div>

            <div class="space-y-4">
                <BiSectionCard :title="$t('Audience & revenue')" :icon="IconUsers" :completeness="audienceCompleteness">
                    <BiAudienceRevenueSection
                        :bi-data="biData"
                        :metrics-summary="metricsSummary"
                        :event-data="eventData"
                        :project-events="projectEvents"
                        :room-capacities="roomCapacities"
                        :project-rooms="projectRooms"
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
                        :current="{ bi_data: biData, event_data: eventData, derived_values: derivedValues, tag_counts: tagCounts }"
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

            <BiExportModal
                v-if="showExportModal"
                :project="project"
                :tag-counts="tagCounts"
                :bi-custom-fields="biCustomFields"
                :default-date-from="projectPeriod?.from ?? null"
                :default-date-to="projectPeriod?.to ?? null"
                @close="showExportModal = false"
            />
        </template>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { usePage } from "@inertiajs/vue3";
import {
    IconUsers,
    IconMasksTheater,
    IconArmchair,
    IconCalculator,
    IconClockHour4,
    IconCamera,
    IconForms,
} from '@tabler/icons-vue';
import { usePermission } from "@/Composeables/Permission.js";
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

const props = defineProps({
    project: { type: Object, required: true },
    canEditComponent: { type: Boolean, default: false },
});

const { can, hasAdminRole } = usePermission(usePage().props);
const canExport = can('can export bi data');

const isLoading = ref(true);
const loadError = ref(null);
const showExportModal = ref(false);

const metricsSummary = ref({});
const biData = ref(null);
const eventData = ref([]);
const roomCapacities = ref([]);
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

const fetchData = async () => {
    try {
        const response = await axios.get(route('projects.bi.show', props.project.id));
        metricsSummary.value = response.data.metrics_summary || {};
        biData.value = response.data.bi_data;
        eventData.value = response.data.event_data;
        roomCapacities.value = response.data.room_capacities;
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
        loadError.value = 'Error loading BI data.';
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchData);
</script>
