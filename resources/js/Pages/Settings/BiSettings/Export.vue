<template>
    <ProjectSettingsHeader
        :title="$t('BI Export')"
        :description="$t('Export business intelligence data for selected productions as Excel.')"
    >
        <div class="mt-4 space-y-6">
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs space-y-5">
                <ArtworkBaseListbox
                    :model-value="selectedProjects"
                    @update:model-value="value => selectedProjects = value"
                    :items="projects"
                    by="id"
                    option-label="name"
                    multiple
                    :label="$t('Productions')"
                    :placeholder="$t('Select productions')"
                />

                <div class="flex flex-wrap items-end gap-4">
                    <BaseInput
                        type="date"
                        id="bi_export_date_from"
                        v-model="dateFrom"
                        :label="$t('From')"
                        class="w-48"
                    />
                    <BaseInput
                        type="date"
                        id="bi_export_date_to"
                        v-model="dateTo"
                        :label="$t('To')"
                        class="w-48"
                    />
                    <p class="text-xs text-gray-500 pb-2">
                        {{ $t('Defaults to the configured playing time window.') }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs">
                <BiExportColumnPicker
                    v-model="selectedColumns"
                    :columns="availableColumns"
                    :presets="presets"
                    id-prefix="bi_settings"
                    max-height-class="max-h-80"
                />
            </div>

            <div class="flex items-center justify-end gap-4">
                <span v-if="isExporting" class="text-sm text-gray-500">{{ $t('The export is being generated…') }}</span>
                <span v-else-if="exportError" class="text-sm text-rose-600">{{ $t('The export could not be generated.') }}</span>
                <BaseUIButton
                    @click="doExport"
                    :label="$t('Download Excel')"
                    :disabled="!canExport || isExporting"
                />
            </div>
        </div>
    </ProjectSettingsHeader>
</template>

<script setup>
import { ref, computed } from 'vue';
import ProjectSettingsHeader from '@/Pages/Settings/Components/ProjectSettingsHeader.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BiExportColumnPicker from '@/Pages/Projects/Components/BiComponents/BiExportColumnPicker.vue';
import { useBiExport } from '@/Composeables/BiExport.js';

defineOptions({ name: 'BiSettingsExport' });

const props = defineProps({
    projects: { type: Array, default: () => [] },
    columns: { type: Array, default: () => [] },
    tagColumns: { type: Array, default: () => [] },
    customFieldColumns: { type: Array, default: () => [] },
    presets: { type: Array, default: () => [] },
    defaultDateFrom: { type: String, default: null },
    defaultDateTo: { type: String, default: null },
});

const { isExporting, exportError, runExport } = useBiExport();

const availableColumns = [
    ...props.columns,
    ...props.tagColumns.map(col => ({ ...col, translate: false })),
    ...props.customFieldColumns.map(col => ({ ...col, translate: false })),
];

const selectedProjects = ref([]);
const selectedColumns = ref(props.columns.map(c => c.key));
const dateFrom = ref(props.defaultDateFrom ?? '');
const dateTo = ref(props.defaultDateTo ?? '');

const canExport = computed(() => selectedProjects.value.length > 0 && selectedColumns.value.length > 0);

const doExport = async () => {
    if (!canExport.value) return;
    await runExport({
        project_ids: selectedProjects.value.map(p => (typeof p === 'object' ? p.id : p)),
        columns: selectedColumns.value,
        date_from: dateFrom.value || null,
        date_to: dateTo.value || null,
    });
};
</script>

<style scoped>
.shadow-xs {
    --tw-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
    --tw-shadow-colored: 0 1px 2px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000),
    var(--tw-shadow);
}
</style>
