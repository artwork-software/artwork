<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-300 text-sm">
            <thead>
                <tr>
                    <th class="py-2 pr-3 text-left font-semibold text-gray-900">{{ $t('Event') }}</th>
                    <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Date') }}</th>
                    <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Room') }}</th>
                    <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ label }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr v-for="event in projectEvents" :key="event.id">
                    <td class="py-2 pr-3 text-gray-700">{{ event.name }}</td>
                    <td class="py-2 px-3 text-gray-500">{{ event.start_time }}</td>
                    <td class="py-2 px-3 text-gray-500">{{ event.room_name }}</td>
                    <td class="py-2 px-3">
                        <input
                            type="number"
                            class="w-28 rounded border-gray-300 text-sm"
                            :min="0"
                            :step="field === 'revenue' ? 0.01 : 1"
                            :value="getEventValue(event.id)"
                            :disabled="!canEdit"
                            @change="saveEventValue(event.id, $event.target.value)"
                        />
                    </td>
                </tr>
                <tr v-if="projectEvents.length === 0">
                    <td colspan="4" class="py-4 text-center text-gray-400">{{ $t('No events found.') }}</td>
                </tr>
            </tbody>
            <tfoot v-if="projectEvents.length > 0">
                <tr class="border-t-2 border-gray-300">
                    <td colspan="3" class="py-2 pr-3 font-semibold text-gray-900">{{ $t('Sum') }}</td>
                    <td class="py-2 px-3 font-semibold text-gray-900">{{ totalSum }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
    field: { type: String, required: true },
    label: { type: String, required: true },
});

const emit = defineEmits(['updated']);

const getEventValue = (eventId) => {
    const entry = props.eventData.find(e => e.event_id === eventId);
    return entry ? entry[props.field] : null;
};

const totalSum = computed(() => {
    return props.eventData.reduce((sum, entry) => {
        const val = parseFloat(entry[props.field]) || 0;
        return sum + val;
    }, 0);
});

const saveEventValue = async (eventId, value) => {
    try {
        const data = {};
        data[props.field] = value === '' ? null : Number(value);
        await axios.put(route('projects.bi.upsert-event-data', [props.projectId, eventId]), data);
        emit('updated');
    } catch (error) {
        console.error('Error saving event data', error);
    }
};
</script>
