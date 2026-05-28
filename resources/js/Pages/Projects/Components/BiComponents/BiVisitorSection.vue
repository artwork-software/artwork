<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">{{ $t('Visitors') }}</h3>
            <div v-if="canEdit">
                <SwitchDualLabel
                    :model-value="currentMode === 'per_event'"
                    :left-label="$t('Total')"
                    :right-label="$t('Per event')"
                    size="sm"
                    @change="onToggleChange"
                />
            </div>
        </div>

        <div v-if="currentMode === 'total'" class="max-w-xs">
            <BaseInput
                type="number"
                id="visitors_total"
                v-model.number="visitorsTotal"
                :label="$t('Total visitors')"
                :disabled="!canEdit"
                :min="0"
                @change="saveTotal"
            />
        </div>

        <BiPerEventDataTable
            v-else
            :event-data="eventData"
            :project-events="projectEvents"
            :can-edit="canEdit"
            :project-id="projectId"
            field="visitors"
            :label="$t('Visitors')"
            @updated="$emit('updated')"
        />

        <BiModeSwitchModal
            v-if="showModeModal"
            @confirm="confirmModeSwitch"
            @close="showModeModal = false"
        />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BiPerEventDataTable from "@/Pages/Projects/Components/BiComponents/BiPerEventDataTable.vue";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";
import BiModeSwitchModal from "@/Pages/Projects/Components/BiComponents/BiModeSwitchModal.vue";

const props = defineProps({
    biData: { type: Object, default: null },
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const currentMode = ref('total');
const visitorsTotal = ref(null);
const showModeModal = ref(false);
const pendingMode = ref(null);

watch(() => props.biData, (val) => {
    if (val) {
        currentMode.value = val.visitor_mode ?? 'total';
        visitorsTotal.value = val.visitors_total;
    }
}, { immediate: true });

const onToggleChange = (isPerEvent) => {
    pendingMode.value = isPerEvent ? 'per_event' : 'total';
    showModeModal.value = true;
};

const confirmModeSwitch = async () => {
    const newMode = pendingMode.value;
    showModeModal.value = false;
    currentMode.value = newMode;
    try {
        await axios.put(route('projects.bi.switch-visitor-mode', props.projectId), { mode: newMode });
        emit('updated');
    } catch (error) {
        console.error('Error switching visitor mode', error);
    }
};

const saveTotal = async () => {
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), { visitors_total: visitorsTotal.value });
        emit('updated');
    } catch (error) {
        console.error('Error saving visitors total', error);
    }
};
</script>
