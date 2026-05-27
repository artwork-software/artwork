<template>
    <div>
        <!-- Toolbar: collapse + filter -->
        <div class="flex items-center justify-between mb-2">
            <button type="button" @click="expanded = !expanded" class="flex items-center gap-2 text-sm font-medium text-gray-700">
                <IconChevronDown class="size-4 transition-transform" :class="{ '-rotate-90': !expanded }" />
                {{ label }} <span class="text-gray-400">({{ displayedEvents.length }})</span>
            </button>
            <button
                type="button"
                @click="showFilters = !showFilters"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-2.5 py-1 text-xs text-gray-600 hover:bg-gray-50 transition"
                :class="{ 'bg-gray-100 border-gray-300': showFilters || hasActiveFilter }"
            >
                <IconFilter class="size-4" />
                {{ $t('Filter') }}
            </button>
        </div>

        <div v-show="expanded">
            <!-- Filter bar -->
            <div v-if="showFilters" class="mb-3 flex flex-wrap items-end gap-3 rounded-md bg-gray-50 p-3">
                <ArtworkBaseListbox
                    class="w-56"
                    :model-value="selectedRoom"
                    @update:model-value="room => filterRoomId = room?.id ?? null"
                    :items="rooms"
                    by="id"
                    option-label="name"
                    :label="$t('Room')"
                    :placeholder="$t('All rooms')"
                />
                <BaseInput id="bi_event_search" v-model="search" :label="$t('Search event')" class="w-56" />
                <button v-if="hasActiveFilter" type="button" @click="clearFilters" class="pb-2 text-xs text-gray-500 hover:text-gray-700">
                    {{ $t('Reset') }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                class="py-2 px-3 text-left font-semibold text-gray-900 cursor-pointer select-none whitespace-nowrap first:pl-0"
                                @click="sortByColumn(col.key)"
                            >
                                <span class="inline-flex items-center gap-1">
                                    {{ col.translate ? $t(col.label) : col.label }}
                                    <IconChevronUp v-if="sortKey === col.key && sortAsc" class="size-3.5" />
                                    <IconChevronDown v-else-if="sortKey === col.key" class="size-3.5" />
                                    <IconArrowsSort v-else class="size-3.5 text-gray-300" />
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="event in displayedEvents" :key="event.id" class="hover:bg-gray-50">
                            <td class="py-2 pl-0 pr-3 text-gray-700">{{ event.name }}</td>
                            <td class="py-2 px-3 text-gray-500 whitespace-nowrap">{{ event.start_time }}</td>
                            <td class="py-2 px-3 text-gray-500">{{ event.room_name }}</td>
                            <td class="py-2 px-3">
                                <input
                                    v-if="canEdit"
                                    type="number"
                                    class="w-28 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm shadow-sm focus:border-artwork-buttons-create focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create"
                                    :min="0"
                                    :step="field === 'revenue' ? 0.01 : 1"
                                    :value="getEventValue(event.id)"
                                    @change="saveEventValue(event.id, $event.target.value)"
                                />
                                <span v-else class="text-gray-700">{{ getEventValue(event.id) ?? '–' }}</span>
                            </td>
                        </tr>
                        <tr v-if="displayedEvents.length === 0">
                            <td colspan="4" class="py-4 text-center text-gray-400">{{ $t('No events found.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="displayedEvents.length > 0">
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="3" class="py-2 pl-0 pr-3 font-semibold text-gray-900">{{ $t('Sum') }}</td>
                            <td class="py-2 px-3 font-semibold text-gray-900">{{ totalSum }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { IconChevronDown, IconChevronUp, IconArrowsSort, IconFilter } from '@tabler/icons-vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';

const props = defineProps({
    eventData: { type: Array, default: () => [] },
    projectEvents: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
    field: { type: String, required: true },
    label: { type: String, required: true },
});

const emit = defineEmits(['updated']);

const expanded = ref(true);
const showFilters = ref(false);
const sortKey = ref('date');
const sortAsc = ref(true);
const filterRoomId = ref(null);
const search = ref('');

const columns = computed(() => [
    { key: 'name', label: 'Event', translate: true },
    { key: 'date', label: 'Date', translate: true },
    { key: 'room', label: 'Room', translate: true },
    { key: 'value', label: props.label, translate: false },
]);

const rooms = computed(() => {
    const map = new Map();
    props.projectEvents.forEach((event) => {
        if (event.room_id && !map.has(event.room_id)) {
            map.set(event.room_id, { id: event.room_id, name: event.room_name });
        }
    });
    return [...map.values()];
});

const selectedRoom = computed(() => rooms.value.find(r => r.id === filterRoomId.value) ?? null);
const hasActiveFilter = computed(() => filterRoomId.value !== null || search.value.trim() !== '');

const getEventValue = (eventId) => {
    const entry = props.eventData.find(e => e.event_id === eventId);
    return entry ? entry[props.field] : null;
};

const parseDate = (value) => {
    if (!value) return 0;
    const [datePart, timePart = '00:00'] = String(value).split(' ');
    const [day, month, year] = datePart.split('.');
    const [hour, minute] = timePart.split(':');
    return new Date(+year, (+month || 1) - 1, +day || 1, +hour || 0, +minute || 0).getTime();
};

const sortValue = (event, key) => {
    switch (key) {
        case 'date': return parseDate(event.start_time);
        case 'room': return (event.room_name || '').toLowerCase();
        case 'value': return parseFloat(getEventValue(event.id)) || 0;
        default: return (event.name || '').toLowerCase();
    }
};

const displayedEvents = computed(() => {
    let list = [...props.projectEvents];

    if (filterRoomId.value !== null) {
        list = list.filter(e => e.room_id === filterRoomId.value);
    }

    const term = search.value.trim().toLowerCase();
    if (term) {
        list = list.filter(e => (e.name || '').toLowerCase().includes(term));
    }

    list.sort((a, b) => {
        const av = sortValue(a, sortKey.value);
        const bv = sortValue(b, sortKey.value);
        if (av < bv) return sortAsc.value ? -1 : 1;
        if (av > bv) return sortAsc.value ? 1 : -1;
        return 0;
    });

    return list;
});

const totalSum = computed(() => displayedEvents.value.reduce((sum, event) => {
    return sum + (parseFloat(getEventValue(event.id)) || 0);
}, 0));

const sortByColumn = (key) => {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        sortAsc.value = true;
    }
};

const clearFilters = () => {
    filterRoomId.value = null;
    search.value = '';
};

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
