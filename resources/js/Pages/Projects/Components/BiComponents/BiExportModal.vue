<template>
    <ArtworkBaseModal
        :title="$t('BI Export')"
        :description="$t('Select columns and projects to export.')"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <ArtworkBaseListbox
                :model-value="granularity"
                @update:model-value="value => granularity = value"
                :items="granularityOptions"
                by="id"
                option-label="name"
                :label="$t('Export structure')"
            />

            <BiExportColumnPicker
                v-if="includesProjects"
                v-model="selectedColumns"
                :columns="availableColumns"
                id-prefix="bi_modal"
                max-height-class="max-h-64"
            />

            <div v-if="includesEvents">
                <ArtworkBaseListbox
                    :model-value="selectedTagFilters"
                    @update:model-value="value => selectedTagFilters = value"
                    :items="tagFilterOptions"
                    by="id"
                    option-label="name"
                    multiple
                    :label="$t('Filter events by BI tags')"
                    :placeholder="$t('All events')"
                />
                <p class="mt-1.5 text-xs text-gray-500">
                    {{ $t('Without a selection, all events in the period are exported.') }}
                </p>
            </div>

            <div>
                <div class="flex items-center gap-4">
                    <BaseInput
                        type="date"
                        id="export_date_from"
                        v-model="dateFrom"
                        :label="$t('From')"
                        class="w-44"
                    />
                    <BaseInput
                        type="date"
                        id="export_date_to"
                        v-model="dateTo"
                        :label="$t('To')"
                        class="w-44"
                    />
                </div>
                <p class="mt-1.5 text-xs text-gray-500">
                    {{ $t('Defaults to the project period. Limits which events count towards per-event and count metrics.') }}
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
                <span v-if="exportError" class="text-sm text-rose-600">{{ $t('The export could not be generated.') }}</span>
                <button @click="$emit('close')" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('Cancel') }}</button>
                <BaseUIButton
                    @click="doExport"
                    :label="$t('Download Excel')"
                    :disabled="(includesProjects && selectedColumns.length === 0) || isExporting"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BiExportColumnPicker from '@/Pages/Projects/Components/BiComponents/BiExportColumnPicker.vue';
import { useBiExport } from '@/Composeables/BiExport.js';
import { useTranslation } from '@/Composeables/Translation.js';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    project: { type: Object, required: true },
    tagCounts: { type: Array, default: () => [] },
    biCustomFields: { type: Array, default: () => [] },
    audienceCategories: { type: Array, default: () => [] },
    defaultDateFrom: { type: String, default: null },
    defaultDateTo: { type: String, default: null },
});

const emit = defineEmits(['close']);

const { isExporting, exportError, runExport } = useBiExport();
const t = useTranslation();

const dateFrom = ref(props.defaultDateFrom ?? '');
const dateTo = ref(props.defaultDateTo ?? '');

const granularityOptions = [
    { id: 'both', name: t('Projects and events (2 sheets)') },
    { id: 'projects', name: t('Project rows only') },
    { id: 'events', name: t('Event rows only') },
];
const granularity = ref(granularityOptions[0]);

const includesProjects = computed(() => granularity.value?.id !== 'events');
const includesEvents = computed(() => granularity.value?.id !== 'projects');

const tagFilterOptions = [
    ...props.tagCounts.map(tagCount => ({ id: tagCount.tag_id, name: tagCount.tag_name_de || tagCount.tag_name })),
    { id: 'untagged', name: t('Events without BI tag') },
];
const selectedTagFilters = ref([]);

const staticColumns = [
    { key: 'project_name', label: 'Project name' },
    { key: 'artists', label: 'Artist / Group' },
    { key: 'cost_center', label: 'Cost bearer' },
    { key: 'rooms', label: 'Room' },
    { key: 'areas', label: 'Area' },
    { key: 'project_state', label: 'Project status' },
    { key: 'main_category', label: 'Category (Sector)' },
    { key: 'first_event_date', label: 'First performance' },
    { key: 'seats_capacity', label: 'Number of seats' },
    { key: 'visitors', label: 'Visitors' },
    { key: 'sold_tickets', label: 'Sold tickets' },
    { key: 'avg_price', label: 'Average price' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'occupancy_rate', label: 'Occupancy rate' },
    { key: 'tickets_issued', label: 'Tickets issued' },
    { key: 'free_tickets_rate', label: 'Free ticket rate' },
    { key: 'reduced_tickets_rate', label: 'Reduced ticket rate' },
    { key: 'paying_rate', label: 'Paying rate' },
    { key: 'no_show_rate', label: 'No-show rate' },
    { key: 'seat_occupancy', label: 'Seat occupancy (incl. free tickets)' },
    { key: 'premiere_date', label: 'Premiere date' },
    { key: 'production_type', label: 'Production type' },
    { key: 'season_year', label: 'Year' },
    { key: 'is_new_production', label: 'New production' },
    { key: 'is_co_production', label: 'Co-production' },
    { key: 'is_own_production', label: 'Own production' },
    { key: 'is_germany_premiere', label: 'Germany premiere' },
    { key: 'contract_count', label: 'Contracts' },
    { key: 'event_count', label: 'Events' },
    { key: 'task_total', label: 'Tasks total' },
    { key: 'task_open', label: 'Tasks open' },
    { key: 'task_done', label: 'Tasks done' },
    { key: 'document_count', label: 'Documents' },
    { key: 'department_count', label: 'Departments involved' },
    { key: 'user_count', label: 'People involved' },
    { key: 'time_efforts', label: 'Time efforts' },
    { key: 'effort_score', label: 'Effort score' },
    { key: 'contracts_per_performance', label: 'Contracts / performance' },
    { key: 'tasks_docs_per_production', label: 'Tasks + documents' },
    { key: 'plan_visitors', label: 'Plan visitors' },
    { key: 'plan_sold_tickets', label: 'Plan sold tickets' },
    { key: 'plan_revenue', label: 'Plan revenue' },
    { key: 'attainment', label: 'Attainment' },
];

const tagColumns = props.tagCounts.map(t => ({
    key: 'tag_' + t.tag_id,
    label: t.tag_name_de,
    translate: false,
}));

const customFieldColumns = props.biCustomFields.map(f => ({
    key: 'custom_field_' + f.id,
    label: f.name,
    translate: false,
}));

// Sage-basierte Spalten nur mit aktiver Schnittstelle anbieten
const sageColumns = usePage().props.sageApiEnabled
    ? [
        { key: 'booking_count', label: 'Bookings' },
        { key: 'bookings_per_performance', label: 'Bookings / performance' },
    ]
    : [];

const audienceCategoryColumns = (props.audienceCategories ?? []).map(category => ({
    key: 'audience_cat_' + category.id,
    label: category.name,
    translate: false,
}));

const availableColumns = [...staticColumns, ...sageColumns, ...tagColumns, ...customFieldColumns, ...audienceCategoryColumns];

const selectedColumns = ref(availableColumns.map(c => c.key));

const doExport = async () => {
    const started = await runExport({
        project_ids: [props.project.id],
        columns: includesProjects.value ? selectedColumns.value : [],
        date_from: dateFrom.value || null,
        date_to: dateTo.value || null,
        granularity: granularity.value?.id ?? 'both',
        event_tag_filter: includesEvents.value
            ? selectedTagFilters.value.map(item => item.id)
            : [],
    });
    if (started) {
        emit('close');
    }
};
</script>
