<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">{{ $t('Snapshots') }}</h3>
        </div>

        <div v-if="canEdit" class="flex items-end gap-3 mb-4 max-w-xl">
            <BaseInput
                id="snapshot_name"
                v-model="newName"
                :label="$t('Name')"
                class="flex-1"
            />
            <BaseInput
                type="date"
                id="snapshot_date"
                v-model="newDate"
                :label="$t('Date')"
                class="w-44"
            />
            <BaseUIButton @click="createSnapshot" :label="$t('Create')" is-add-button :disabled="!newName || !newDate" />
        </div>

        <div class="space-y-3" v-if="snapshots.length > 0">
            <div
                v-for="snapshot in snapshots"
                :key="snapshot.id"
                class="rounded-lg border border-gray-200 p-3"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-medium text-sm text-gray-900">{{ snapshot.name }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ formatDate(snapshot.snapshot_date) }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $t('by') }} {{ snapshot.creator?.first_name }} {{ snapshot.creator?.last_name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleDetail(snapshot.id)" class="text-xs text-primary hover:underline">
                            {{ expandedId === snapshot.id ? $t('Hide') : $t('Show') }}
                        </button>
                        <button v-if="canEdit" @click="deleteSnapshot(snapshot.id)" class="text-xs text-red-500 hover:text-red-700 print:hidden">
                            {{ $t('Delete') }}
                        </button>
                    </div>
                </div>
                <div v-if="expandedId === snapshot.id" class="mt-3 text-xs text-gray-600 bg-gray-50 rounded p-3 overflow-auto max-h-64">
                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(snapshot.data, null, 2) }}</pre>
                </div>
            </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('No snapshots created yet.') }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    snapshots: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const newName = ref('');
const newDate = ref('');
const expandedId = ref(null);

const formatDate = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
};

const toggleDetail = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

const createSnapshot = async () => {
    if (!newName.value || !newDate.value) return;
    try {
        await axios.post(route('projects.bi.snapshots.store', props.projectId), {
            name: newName.value,
            snapshot_date: newDate.value,
        });
        newName.value = '';
        newDate.value = '';
        emit('updated');
    } catch (error) {
        console.error('Error creating snapshot', error);
    }
};

const deleteSnapshot = async (snapshotId) => {
    if (!confirm('Delete this snapshot? This cannot be undone.')) return;
    try {
        await axios.delete(route('projects.bi.snapshots.destroy', [props.projectId, snapshotId]));
        emit('updated');
    } catch (error) {
        console.error('Error deleting snapshot', error);
    }
};
</script>
