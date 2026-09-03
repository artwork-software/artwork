<template>
    <div>
        <div v-if="canEdit" class="flex items-end gap-3 mb-4 max-w-xl print:hidden">
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
                :label="$t('Hours (estimate)')"
                :placeholder="$t('Select')"
                class="w-40"
            />
            <BaseUIButton @click="addEffort" :label="$t('Add')" is-add-button :disabled="!newLabel || !newBucket" />
        </div>

        <div class="overflow-x-auto" v-if="timeEfforts.length > 0">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead>
                    <tr>
                        <th class="py-2 pr-3 text-left font-semibold text-text">{{ $t('Label') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-text">{{ $t('Hours (estimate)') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-text">{{ $t('Created by') }}</th>
                        <th class="py-2 px-3 text-right font-semibold text-text print:hidden" v-if="canEdit"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    <tr v-for="effort in timeEfforts" :key="effort.id">
                        <td class="py-2 pr-3 text-text-muted">{{ effort.label }}</td>
                        <td class="py-2 px-3 text-text-subtle">{{ bucketLabel(effort.effort_bucket) }}</td>
                        <td class="py-2 px-3 text-text-subtle">{{ effort.user?.first_name }} {{ effort.user?.last_name }}</td>
                        <td class="py-2 px-3 text-right print:hidden" v-if="canEdit">
                            <button @click="effortToDelete = effort" class="text-xs text-danger hover:text-danger">
                                {{ $t('Delete') }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-text-subtle">{{ $t('No time efforts recorded.') }}</p>

        <ArtworkBaseDeleteModal
            v-if="effortToDelete"
            :title="$t('Delete time effort?')"
            :description="`${effortToDelete.label} — ${$t('This time effort entry will be removed permanently.')}`"
            @close="effortToDelete = null"
            @delete="deleteEffort"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import ArtworkBaseDeleteModal from '@/Artwork/Modals/ArtworkBaseDeleteModal.vue';
import { useBiSaveFeedback } from '@/Composeables/BiSaveFeedback.js';

const props = defineProps({
    timeEfforts: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const newLabel = ref('');
const newBucket = ref(null);

// Buckets sind Stunden (BiDerivedValuesService::EFFORT_HOURS) — die Einheit muss sichtbar sein
const bucketOptions = [
    { value: '0-10', label: '0–10 h' },
    { value: '10-25', label: '10–25 h' },
    { value: '25-50', label: '25–50 h' },
    { value: '50-100', label: '50–100 h' },
    { value: '100+', label: 'über 100 h' },
];

const bucketLabel = (value) => bucketOptions.find(option => option.value === value)?.label ?? value;

const effortToDelete = ref(null);

const biSave = useBiSaveFeedback();

const addEffort = async () => {
    if (!newLabel.value || !newBucket.value) return;
    const ok = await biSave.run(
        () => axios.post(route('projects.bi.time-efforts.store', props.projectId), {
            label: newLabel.value,
            effort_bucket: newBucket.value.value,
        })
    );
    if (ok) {
        newLabel.value = '';
        newBucket.value = null;
        emit('updated');
    }
};

const deleteEffort = async () => {
    const effortId = effortToDelete.value?.id;
    effortToDelete.value = null;
    if (!effortId) return;
    const ok = await biSave.run(
        () => axios.delete(route('projects.bi.time-efforts.destroy', [props.projectId, effortId]))
    );
    if (ok) {
        emit('updated');
    }
};
</script>
