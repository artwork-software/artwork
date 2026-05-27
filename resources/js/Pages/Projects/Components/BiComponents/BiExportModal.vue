<template>
    <ArtworkBaseModal
        :title="$t('BI Export')"
        :description="$t('Select columns and projects to export.')"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 pb-3">
                <ArtworkBaseListbox
                    class="w-56"
                    :model-value="selectedPreset"
                    @update:model-value="applyPreset"
                    :items="presets"
                    by="id"
                    option-label="name"
                    :label="$t('Column preset')"
                    :placeholder="$t('Select preset')"
                />
                <BaseInput id="bi_modal_preset_name" v-model="newPresetName" :label="$t('Save as preset')" class="w-48" />
                <button
                    class="text-sm text-blue-600 hover:underline pb-2 disabled:opacity-40"
                    :disabled="!newPresetName || selectedColumns.length === 0"
                    @click="savePreset"
                >
                    {{ $t('Save preset') }}
                </button>
            </div>

            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('Columns') }}</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-64 overflow-y-auto">
                    <BaseCheckbox
                        v-for="col in availableColumns"
                        :key="col.key"
                        :model-value="selectedColumns.includes(col.key)"
                        @update:model-value="v => toggleColumn(col.key, v)"
                        :label="$t(col.label)"
                        description=""
                    />
                </div>
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

            <div class="flex justify-end gap-3 pt-4">
                <button @click="$emit('close')" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('Cancel') }}</button>
                <BaseUIButton
                    @click="doExport"
                    :label="$t('Download Excel')"
                    :disabled="selectedColumns.length === 0 || isExporting"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';

const props = defineProps({
    project: { type: Object, required: true },
    tagCounts: { type: Array, default: () => [] },
    biCustomFields: { type: Array, default: () => [] },
    defaultDateFrom: { type: String, default: null },
    defaultDateTo: { type: String, default: null },
});

const emit = defineEmits(['close']);

const isExporting = ref(false);
const dateFrom = ref(props.defaultDateFrom ?? '');
const dateTo = ref(props.defaultDateTo ?? '');

const staticColumns = [
    { key: 'project_name', label: 'Project name' },
    { key: 'artists', label: 'Artist / Group' },
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
    { key: 'premiere_date', label: 'Premiere date' },
    { key: 'production_type', label: 'Production type' },
    { key: 'season_year', label: 'Year' },
    { key: 'is_new_production', label: 'New production' },
    { key: 'is_co_production', label: 'Co-production' },
    { key: 'is_own_production', label: 'Own production' },
    { key: 'is_germany_premiere', label: 'Germany premiere' },
    { key: 'contract_count', label: 'Contracts' },
    { key: 'event_count', label: 'Events' },
    { key: 'booking_count', label: 'Bookings' },
    { key: 'task_total', label: 'Tasks total' },
    { key: 'task_open', label: 'Tasks open' },
    { key: 'task_done', label: 'Tasks done' },
    { key: 'document_count', label: 'Documents' },
    { key: 'department_count', label: 'Departments involved' },
    { key: 'user_count', label: 'People involved' },
    { key: 'time_efforts', label: 'Time efforts' },
];

const tagColumns = props.tagCounts.map(t => ({
    key: 'tag_' + t.tag_id,
    label: t.tag_name_de,
}));

const customFieldColumns = props.biCustomFields.map(f => ({
    key: 'custom_field_' + f.id,
    label: f.name,
}));

const availableColumns = [...staticColumns, ...tagColumns, ...customFieldColumns];

const selectedColumns = ref(availableColumns.map(c => c.key));

const toggleColumn = (key, checked) => {
    if (checked) {
        if (!selectedColumns.value.includes(key)) {
            selectedColumns.value.push(key);
        }
    } else {
        selectedColumns.value = selectedColumns.value.filter(k => k !== key);
    }
};

const presets = ref([]);
const selectedPreset = ref(null);
const newPresetName = ref('');

onMounted(async () => {
    try {
        const response = await axios.get(route('bi.export.presets.index'));
        presets.value = response.data;
    } catch (error) {
        console.error('Error loading presets', error);
    }
});

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
        presets.value.push(response.data);
        selectedPreset.value = response.data;
        newPresetName.value = '';
    } catch (error) {
        console.error('Error saving preset', error);
    }
};

const doExport = async () => {
    isExporting.value = true;
    try {
        const response = await axios.post(route('bi.export.cache'), {
            project_ids: [props.project.id],
            columns: selectedColumns.value,
            date_from: dateFrom.value || null,
            date_to: dateTo.value || null,
        });

        await pollAndDownload(response.data.token);
        emit('close');
    } catch (error) {
        console.error('Export error', error);
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
                window.location.href = route('bi.export.download', token);
                return resolve();
            }
            if (data.status === 'failed' || data.status === 'unknown') {
                return resolve();
            }
        } catch (error) {
            // transient error – keep polling until the attempt budget is exhausted
        }
        if (attempts >= maxAttempts) {
            return resolve();
        }
        setTimeout(check, 1500);
    };
    check();
});
</script>
