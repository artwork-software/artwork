<template>
    <ArtworkBaseModal
        :title="$t('BI Export')"
        :description="$t('Select columns and projects to export.')"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('Columns') }}</h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-64 overflow-y-auto">
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
import { ref } from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    project: { type: Object, required: true },
    tagCounts: { type: Array, default: () => [] },
    biCustomFields: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const isExporting = ref(false);
const dateFrom = ref('');
const dateTo = ref('');

const staticColumns = [
    { key: 'project_name', label: 'Project name' },
    { key: 'project_state', label: 'Project status' },
    { key: 'visitors', label: 'Visitors' },
    { key: 'sold_tickets', label: 'Sold tickets' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'is_new_production', label: 'New production' },
    { key: 'is_co_production', label: 'Co-production' },
    { key: 'is_own_production', label: 'Own production' },
    { key: 'is_germany_premiere', label: 'Germany premiere' },
    { key: 'premiere_date', label: 'Premiere date' },
    { key: 'contract_count', label: 'Contracts' },
    { key: 'event_count', label: 'Events' },
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

const doExport = async () => {
    isExporting.value = true;
    try {
        const response = await axios.post(route('bi.export.cache'), {
            project_ids: [props.project.id],
            columns: selectedColumns.value,
            date_from: dateFrom.value || null,
            date_to: dateTo.value || null,
        });

        const token = response.data.token;
        window.open(route('bi.export.download', token), '_blank');
        emit('close');
    } catch (error) {
        console.error('Export error', error);
    } finally {
        isExporting.value = false;
    }
};
</script>
