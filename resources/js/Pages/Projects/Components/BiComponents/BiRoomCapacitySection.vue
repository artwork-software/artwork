<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $t('Room capacities & utilisation') }}</h3>
        <div class="overflow-x-auto" v-if="projectRooms.length > 0">
            <table class="min-w-full divide-y divide-gray-300 text-sm">
                <thead>
                    <tr>
                        <th class="py-2 pr-3 text-left font-semibold text-gray-900">{{ $t('Room') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Default capacity') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Override') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Effective capacity') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="room in projectRooms" :key="room.id">
                        <td class="py-2 pr-3 text-gray-700">{{ room.name }}</td>
                        <td class="py-2 px-3 text-gray-500">{{ room.default_capacity ?? '-' }}</td>
                        <td class="py-2 px-3">
                            <input
                                type="number"
                                class="w-28 rounded border-gray-300 text-sm"
                                :min="0"
                                :value="getOverride(room.id)"
                                :disabled="!canEdit"
                                :placeholder="$t('Room default')"
                                @change="saveOverride(room.id, $event.target.value)"
                            />
                        </td>
                        <td class="py-2 px-3 font-medium text-gray-900">
                            {{ getEffectiveCapacity(room) ?? '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('No rooms with events in this project.') }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    roomCapacities: { type: Array, default: () => [] },
    projectRooms: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const getOverride = (roomId) => {
    const entry = props.roomCapacities.find(c => c.room_id === roomId);
    return entry?.capacity_override ?? null;
};

const getEffectiveCapacity = (room) => {
    const override = getOverride(room.id);
    return override ?? room.default_capacity;
};

const saveOverride = async (roomId, value) => {
    try {
        await axios.put(route('projects.bi.update-room-capacity', [props.projectId, roomId]), {
            capacity_override: value === '' ? null : Number(value),
        });
        emit('updated');
    } catch (error) {
        console.error('Error saving room capacity override', error);
    }
};
</script>
