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

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs space-y-3">
                <div class="flex flex-wrap items-end gap-3">
                    <ArtworkBaseListbox
                        class="w-64"
                        :model-value="selectedPreset"
                        @update:model-value="applyPreset"
                        :items="presetList"
                        by="id"
                        option-label="name"
                        :label="$t('Column preset')"
                        :placeholder="$t('Select preset')"
                    />
                    <BaseInput
                        id="bi_preset_name"
                        v-model="newPresetName"
                        :label="$t('Save current selection as preset')"
                        class="w-64"
                    />
                    <button
                        class="text-sm text-blue-600 hover:underline pb-2 disabled:opacity-40"
                        :disabled="!newPresetName || selectedColumns.length === 0"
                        @click="savePreset"
                    >
                        {{ $t('Save preset') }}
                    </button>
                    <button
                        v-if="selectedPreset"
                        class="text-sm text-rose-600 hover:underline pb-2"
                        @click="deletePreset"
                    >
                        {{ $t('Delete preset') }}
                    </button>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700">{{ $t('Columns') }}</h4>
                    <div class="flex gap-3 text-xs">
                        <button class="text-blue-600 hover:underline" @click="selectAllColumns">
                            {{ $t('Select all') }}
                        </button>
                        <button class="text-gray-500 hover:underline" @click="selectedColumns = []">
                            {{ $t('Clear') }}
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-80 overflow-y-auto">
                    <label
                        v-for="col in availableColumns"
                        :key="col.key"
                        class="flex items-center gap-2 text-sm cursor-pointer"
                    >
                        <input type="checkbox" v-model="selectedColumns" :value="col.key" class="rounded border-gray-300" />
                        {{ $t(col.label) }}
                    </label>
                </div>
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

const availableColumns = [...props.columns, ...props.tagColumns, ...props.customFieldColumns];

const selectedProjects = ref([]);
const selectedColumns = ref(props.columns.map(c => c.key));
const dateFrom = ref(props.defaultDateFrom ?? '');
const dateTo = ref(props.defaultDateTo ?? '');
const isExporting = ref(false);
const exportError = ref(false);

const presetList = ref([...props.presets]);
const selectedPreset = ref(null);
const newPresetName = ref('');

const applyPreset = (preset) => {
    selectedPreset.value = preset;
    if (preset?.columns) {
        const valid = new Set(availableColumns.map(c => c.key));
        selectedColumns.value = preset.columns.filter(key => valid.has(key));
    }
};

const savePreset = async () => {
    if (!newPresetName.value || selectedColumns.value.length === 0) return;
    try {
        const response = await axios.post(route('bi.export.presets.store'), {
            name: newPresetName.value,
            columns: selectedColumns.value,
        });
        presetList.value.push(response.data);
        selectedPreset.value = response.data;
        newPresetName.value = '';
    } catch (error) {
        console.error('Error saving preset', error);
    }
};

const deletePreset = async () => {
    if (!selectedPreset.value) return;
    try {
        await axios.delete(route('bi.export.presets.destroy', selectedPreset.value.id));
        presetList.value = presetList.value.filter(p => p.id !== selectedPreset.value.id);
        selectedPreset.value = null;
    } catch (error) {
        console.error('Error deleting preset', error);
    }
};

const canExport = computed(() => selectedProjects.value.length > 0 && selectedColumns.value.length > 0);

const selectAllColumns = () => {
    selectedColumns.value = availableColumns.map(c => c.key);
};

const doExport = async () => {
    if (!canExport.value) return;
    isExporting.value = true;
    exportError.value = false;
    try {
        const response = await axios.post(route('bi.export.cache'), {
            project_ids: selectedProjects.value.map(p => (typeof p === 'object' ? p.id : p)),
            columns: selectedColumns.value,
            date_from: dateFrom.value || null,
            date_to: dateTo.value || null,
        });
        await pollAndDownload(response.data.token);
    } catch (error) {
        console.error('BI export error', error);
        exportError.value = true;
    } finally {
        isExporting.value = false;
    }
};

const pollAndDownload = (token) => new Promise((resolve) => {
    let attempts = 0;
    const maxAttempts = 120;
    const check = async () => {
        attempts++;
        try {
            const { data } = await axios.get(route('bi.export.status', token));
            if (data.status === 'ready') {
                window.open(route('bi.export.download', token), '_blank');
                return resolve();
            }
            if (data.status === 'failed' || data.status === 'unknown') {
                exportError.value = true;
                return resolve();
            }
        } catch (error) {
            // transient error – keep polling until the attempt budget is exhausted
        }
        if (attempts >= maxAttempts) {
            exportError.value = true;
            return resolve();
        }
        setTimeout(check, 1500);
    };
    check();
});
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
