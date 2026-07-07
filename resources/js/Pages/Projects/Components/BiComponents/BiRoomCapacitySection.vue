<template>
    <div>
        <div class="overflow-x-auto" v-if="projectRooms.length > 0">
            <table class="min-w-full divide-y divide-gray-300 text-sm">
                <thead>
                    <tr>
                        <th class="py-2 pr-3 text-left font-semibold text-gray-900">{{ $t('Room') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Default capacity') }}</th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">
                            <div class="flex items-center gap-1">
                                {{ $t('Project-specific capacity') }}
                                <ToolTipComponent
                                    direction="top"
                                    :tooltip-text="$t('Enter project-specific room capacities here if the room has a different capacity for events in this project.')"
                                    icon="IconInfoCircle"
                                    icon-size="h-4 w-4"
                                />
                            </div>
                        </th>
                        <th class="py-2 px-3 text-left font-semibold text-gray-900">{{ $t('Effective capacity') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="room in projectRooms" :key="room.id">
                        <td class="py-2 pr-3 text-gray-700">
                            <div class="flex items-center gap-2">
                                <span>{{ room.name }}</span>
                                <Link
                                    v-if="canEditRooms"
                                    :href="route('rooms.show', { room: room.id })"
                                    class="text-gray-400 hover:text-artwork-buttons-create transition"
                                    v-tooltip.top="{ value: $t('Open room'), appendTo: 'body', class: 'aw-tooltip' }"
                                >
                                    <IconExternalLink class="size-4" />
                                </Link>
                            </div>
                        </td>
                        <td class="py-2 px-3 text-gray-500">{{ room.default_capacity ?? '-' }}</td>
                        <td class="py-2 px-3">
                            <input
                                v-if="canEdit"
                                type="number"
                                class="w-28 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm shadow-sm focus:border-artwork-buttons-create focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create"
                                :min="0"
                                :value="getOverride(room.id)"
                                placeholder=""
                                @change="saveOverride(room.id, $event.target.value)"
                            />
                            <span v-else class="text-gray-700">{{ getOverride(room.id) ?? '–' }}</span>
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
import { Link, usePage } from '@inertiajs/vue3';
import { IconExternalLink } from '@tabler/icons-vue';
import { usePermission } from '@/Composeables/Permission.js';
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue';

const props = defineProps({
    roomCapacities: { type: Array, default: () => [] },
    projectRooms: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const { can, hasAdminRole } = usePermission(usePage().props);
const canEditRooms = can('create, delete and update rooms') || hasAdminRole();

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
