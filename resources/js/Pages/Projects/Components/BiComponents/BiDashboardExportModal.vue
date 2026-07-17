<template>
    <ArtworkBaseModal
        :title="$t('BI Export')"
        :description="$t('Select columns and projects to export.')"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <div>
                <ArtworkBaseListbox
                    :model-value="selectedCostCenters"
                    @update:model-value="value => selectedCostCenters = value"
                    :items="costCenterOptions"
                    by="id"
                    option-label="name"
                    multiple
                    :label="$t('Filter productions by cost unit')"
                    :placeholder="$t('All cost units')"
                />
                <p class="mt-1.5 text-xs text-gray-500">
                    {{ $t('Selecting cost units automatically selects their productions below.') }}
                </p>
            </div>

            <ArtworkBaseListbox
                :model-value="selectedProjects"
                @update:model-value="value => selectedProjects = value"
                :items="filteredProjects"
                by="id"
                option-label="name"
                multiple
                :label="$t('Productions')"
                :placeholder="$t('Select productions')"
            />

            <div class="flex items-center gap-4">
                <BaseInput
                    type="date"
                    id="bi_dash_export_date_from"
                    v-model="dateFrom"
                    :label="$t('From')"
                    class="w-44"
                />
                <BaseInput
                    type="date"
                    id="bi_dash_export_date_to"
                    v-model="dateTo"
                    :label="$t('To')"
                    class="w-44"
                />
            </div>

            <ArtworkBaseListbox
                :model-value="granularity"
                @update:model-value="value => granularity = value"
                :items="granularityOptions"
                by="id"
                option-label="name"
                :label="$t('Export structure')"
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

            <BiExportColumnPicker
                v-if="includesProjects"
                v-model="selectedColumns"
                :columns="availableColumns"
                :presets="options.presets"
                id-prefix="bi_dashboard"
                max-height-class="max-h-64"
            />

            <div class="flex items-center justify-end gap-3 pt-4">
                <span v-if="exportError" class="text-sm text-rose-600">{{ $t('The export could not be generated.') }}</span>
                <button @click="$emit('close')" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('Cancel') }}</button>
                <BaseUIButton
                    @click="doExport"
                    :label="$t('Download Excel')"
                    :disabled="!canExport || isExporting"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BiExportColumnPicker from '@/Pages/Projects/Components/BiComponents/BiExportColumnPicker.vue';
import { useBiExport } from '@/Composeables/BiExport.js';
import { useTranslation } from '@/Composeables/Translation.js';

const props = defineProps({
    // Payload aus BiDashboardController::buildExportOptions()
    options: { type: Object, required: true },
    defaultDateFrom: { type: String, default: null },
    defaultDateTo: { type: String, default: null },
});

const emit = defineEmits(['close']);

const { isExporting, exportError, runExport } = useBiExport();
const t = useTranslation();

const dateFrom = ref(props.defaultDateFrom ?? '');
const dateTo = ref(props.defaultDateTo ?? '');

const costCenterOptions = props.options.costCenters ?? [];
const selectedCostCenters = ref([]);

const filteredProjects = computed(() => {
    if (selectedCostCenters.value.length === 0) {
        return props.options.projects;
    }
    const costCenterIds = selectedCostCenters.value.map(costCenter => costCenter.id);
    return props.options.projects.filter(project => costCenterIds.includes(project.cost_center_id));
});

// Dashboard-Flow: standardmäßig alle (gefilterten) Produktionen exportieren
const selectedProjects = ref([...props.options.projects]);
watch(selectedCostCenters, () => {
    selectedProjects.value = [...filteredProjects.value];
});

const granularityOptions = [
    { id: 'projects', name: t('Project rows only') },
    { id: 'both', name: t('Projects and events (2 sheets)') },
    { id: 'events', name: t('Event rows only') },
];
const granularity = ref(granularityOptions[0]);

const includesProjects = computed(() => granularity.value?.id !== 'events');
const includesEvents = computed(() => granularity.value?.id !== 'projects');

const tagFilterOptions = [
    ...(props.options.tagColumns ?? []).map(col => ({
        id: parseInt(col.key.replace('tag_', ''), 10),
        name: col.label,
    })),
    { id: 'untagged', name: t('Events without BI tag') },
];
const selectedTagFilters = ref([]);

const availableColumns = [
    ...(props.options.columns ?? []),
    ...(props.options.tagColumns ?? []).map(col => ({ ...col, translate: false })),
    ...(props.options.customFieldColumns ?? []).map(col => ({ ...col, translate: false })),
];
const selectedColumns = ref((props.options.columns ?? []).map(c => c.key));

const canExport = computed(() =>
    selectedProjects.value.length > 0 && (!includesProjects.value || selectedColumns.value.length > 0)
);

const doExport = async () => {
    if (!canExport.value) return;
    const started = await runExport({
        project_ids: selectedProjects.value.map(project => project.id),
        columns: includesProjects.value ? selectedColumns.value : [],
        date_from: dateFrom.value || null,
        date_to: dateTo.value || null,
        granularity: granularity.value?.id ?? 'projects',
        event_tag_filter: includesEvents.value
            ? selectedTagFilters.value.map(item => item.id)
            : [],
    });
    if (started) {
        emit('close');
    }
};
</script>
