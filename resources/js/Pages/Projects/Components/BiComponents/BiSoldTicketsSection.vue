<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">{{ $t('Sold tickets') }}</h3>
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
                id="sold_tickets_total"
                v-model.number="soldTicketsTotal"
                :label="$t('Total sold tickets')"
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
            field="sold_tickets"
            :label="$t('Sold tickets')"
            @updated="$emit('updated')"
        />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BiPerEventDataTable from "@/Pages/Projects/Components/BiComponents/BiPerEventDataTable.vue";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";

const props = defineProps({
    biData: { type: Object, default: null },
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const currentMode = ref('total');
const soldTicketsTotal = ref(null);

watch(() => props.biData, (val) => {
    if (val) {
        currentMode.value = val.sold_tickets_mode ?? 'total';
        soldTicketsTotal.value = val.sold_tickets_total;
    }
}, { immediate: true });

const onToggleChange = async (isPerEvent) => {
    const newMode = isPerEvent ? 'per_event' : 'total';
    const message = isPerEvent
        ? 'Switching to per-event mode will clear the total value. Continue?'
        : 'Switching to total mode will clear per-event values. Continue?';

    if (!confirm(message)) {
        return;
    }

    currentMode.value = newMode;
    try {
        await axios.put(route('projects.bi.switch-sold-tickets-mode', props.projectId), { mode: newMode });
        emit('updated');
    } catch (error) {
        console.error('Error switching sold tickets mode', error);
    }
};

const saveTotal = async () => {
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), { sold_tickets_total: soldTicketsTotal.value });
        emit('updated');
    } catch (error) {
        console.error('Error saving sold tickets total', error);
    }
};
</script>
