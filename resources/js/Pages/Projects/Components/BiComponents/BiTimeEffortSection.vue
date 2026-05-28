<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">{{ $t('Time efforts') }}</h3>
        </div>

        <div v-if="canEdit" class="flex items-end gap-3 mb-4 max-w-xl">
            <BaseInput
                id="effort_label"
                v-model="newLabel"
                :label="$t('Label')"
                class="flex-1"
            />
            <ArtworkBaseListbox
                :model-value="newBucket"
                @update:model-value="newBucket = $event"
                :items="bucketOptions"
                by="value"
                option-label="label"
                :label="$t('Effort')"
                :placeholder="$t('Select')"
                class="w-40"
            />
            <BaseUIButton @click="addEffort" :label="$t('Add')" is-add-button :disabled="!newLabel || !newBucket" />
        </div>

        <div class="overflow-x-auto" v-if="timeEfforts.length > 0">
            <table class="min-w-full divide-y divide-gray-300 text-sm">
                <thead>
                    <tr>
                        <th class="py-2 pr-3 text-left font-semibold text-gray-900">{{ $t('Label') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Effort') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Created by') }}</th>
                        <th class="py-2 px-3 text-right font-semibold text-gray-900 print:hidden" v-if="canEdit"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="effort in timeEfforts" :key="effort.id">
                        <td class="py-2 pr-3 text-gray-700">{{ effort.label }}</td>
                        <td class="py-2 px-3 text-gray-500">{{ effort.effort_bucket }}</td>
                        <td class="py-2 px-3 text-gray-500">{{ effort.user?.first_name }} {{ effort.user?.last_name }}</td>
                        <td class="py-2 px-3 text-right print:hidden" v-if="canEdit">
                            <button @click="deleteEffort(effort.id)" class="text-xs text-red-500 hover:text-red-700">
                                {{ $t('Delete') }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('No time efforts recorded.') }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';

const props = defineProps({
    timeEfforts: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const newLabel = ref('');
const newBucket = ref(null);

const bucketOptions = [
    { value: '0-10', label: '0-10' },
    { value: '10-25', label: '10-25' },
    { value: '25-50', label: '25-50' },
    { value: '50-100', label: '50-100' },
    { value: '100+', label: '100+' },
];

const addEffort = async () => {
    if (!newLabel.value || !newBucket.value) return;
    try {
        await axios.post(route('projects.bi.time-efforts.store', props.projectId), {
            label: newLabel.value,
            effort_bucket: newBucket.value.value,
        });
        newLabel.value = '';
        newBucket.value = null;
        emit('updated');
    } catch (error) {
        console.error('Error adding time effort', error);
    }
};

const deleteEffort = async (effortId) => {
    if (!confirm('Delete this time effort entry?')) return;
    try {
        await axios.delete(route('projects.bi.time-efforts.destroy', [props.projectId, effortId]));
        emit('updated');
    } catch (error) {
        console.error('Error deleting time effort', error);
    }
};
</script>
