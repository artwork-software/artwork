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

            <BiCoreDataSection
                :bi-data="biData"
                :can-edit="canEditComponent"
                :project-id="project.id"
                @updated="fetchData"
            />

            <BiVisitorSection
                :bi-data="biData"
                :event-data="eventData"
                :project-events="projectEvents"
                :can-edit="canEditComponent"
                :project-id="project.id"
                @updated="fetchData"
            />

            <BiSoldTicketsSection
                :bi-data="biData"
                :event-data="eventData"
                :project-events="projectEvents"
                :can-edit="canEditComponent"
                :project-id="project.id"
                @updated="fetchData"
            />

            <BiRevenueSection
                :bi-data="biData"
                :event-data="eventData"
                :project-events="projectEvents"
                :can-edit="canEditComponent"
                :project-id="project.id"
                @updated="fetchData"
            />

            <BiRoomCapacitySection
                :room-capacities="roomCapacities"
                :project-rooms="projectRooms"
                :can-edit="canEditComponent"
                :project-id="project.id"
                @updated="fetchData"
            />

            <BiTagCountsSection :tag-counts="tagCounts" />

            <BiDerivedValuesSection :derived-values="derivedValues" />

            <BiTimeEffortSection
                :time-efforts="timeEfforts"
                :can-edit="canEditComponent"
                :project-id="project.id"
                @updated="fetchData"
            />

            <BiSnapshotSection
                :snapshots="snapshots"
                :can-edit="canEditComponent"
                :project-id="project.id"
                :current="{ bi_data: biData, derived_values: derivedValues, tag_counts: tagCounts }"
                @updated="fetchData"
            />

            <BiCustomFieldsSection
                :fields="biCustomFields"
                :field-values="biCustomFieldValues"
                :can-edit="canEditComponent"
                :project-id="project.id"
                @updated="fetchData"
            />

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
import { ref, onMounted } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { usePermission } from "@/Composeables/Permission.js";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BiCoreDataSection from "@/Pages/Projects/Components/BiComponents/BiCoreDataSection.vue";
import BiVisitorSection from "@/Pages/Projects/Components/BiComponents/BiVisitorSection.vue";
import BiSoldTicketsSection from "@/Pages/Projects/Components/BiComponents/BiSoldTicketsSection.vue";
import BiRevenueSection from "@/Pages/Projects/Components/BiComponents/BiRevenueSection.vue";
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

const fetchData = async () => {
    try {
        const response = await axios.get(route('projects.bi.show', props.project.id));
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
