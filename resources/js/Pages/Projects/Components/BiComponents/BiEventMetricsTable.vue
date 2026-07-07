<template>
    <div>
        <!-- Toolbar: collapse + filter -->
        <div class="flex items-center justify-between mb-2">
            <button type="button" @click="expanded = !expanded" class="flex items-center gap-2 text-sm font-medium text-gray-700 print:hidden">
                <IconChevronDown class="size-4 transition-transform" :class="{ '-rotate-90': !expanded }" />
                {{ $t('Entries per event') }} <span class="text-gray-400">({{ displayedEvents.length }})</span>
            </button>
            <button
                type="button"
                @click="showFilters = !showFilters"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 px-2.5 py-1 text-xs text-gray-600 hover:bg-gray-50 transition print:hidden"
                :class="{ 'bg-gray-100 border-gray-300': showFilters || hasActiveFilter }"
            >
                <IconFilter class="size-4" />
                {{ $t('Filter') }}
            </button>
        </div>

        <div v-show="expanded" class="print:!block">
            <!-- Filter bar -->
            <div v-if="showFilters" class="mb-3 flex flex-wrap items-end gap-3 rounded-md bg-gray-50 p-3 print:hidden">
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
                        <tr
                            v-for="event in displayedEvents"
                            :key="event.id"
                            class="hover:bg-gray-50"
                            :class="{ 'bg-indigo-50/60': event.id === latestPastEventId }"
                        >
                            <td class="py-2 pl-0 pr-3 text-gray-700">
                                <span class="inline-flex items-center gap-2">
                                    {{ event.name }}
                                    <span
                                        v-if="event.id === latestPastEventId"
                                        class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-medium text-indigo-700 whitespace-nowrap print:hidden"
                                    >
                                        {{ $t('Most recent event') }}
                                    </span>
                                </span>
                            </td>
                            <td class="py-2 px-3 text-gray-500 whitespace-nowrap">{{ event.start_time }}</td>
                            <td class="py-2 px-3 text-gray-500">{{ event.room_name }}</td>
                            <td v-for="field in fields" :key="field.key" class="py-2 px-3">
                                <input
                                    v-if="canEdit"
                                    type="number"
                                    class="w-24 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm shadow-sm focus:border-artwork-buttons-create focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create"
                                    :min="0"
                                    :step="field.key === 'revenue' ? 0.01 : 1"
                                    :value="getEventValue(event.id, field.key)"
                                    @change="saveEventValue(event.id, field.key, $event.target.value)"
                                />
                                <span v-else class="text-gray-700">{{ getEventValue(event.id, field.key) ?? '–' }}</span>
                            </td>
                            <td v-if="showOccupancy" class="py-2 px-3">
                                <div v-if="occupancyFor(event) !== null" class="flex items-center gap-2 min-w-28">
                                    <div class="h-1.5 w-16 rounded-full bg-gray-100 overflow-hidden shrink-0">
                                        <div
                                            class="h-full rounded-full"
                                            :class="occupancyBarClass(occupancyFor(event))"
                                            :style="{ width: Math.min(occupancyFor(event), 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-xs text-gray-600 whitespace-nowrap">{{ occupancyFor(event).toFixed(0) }} %</span>
                                </div>
                                <span v-else class="text-gray-300">–</span>
                            </td>
                        </tr>
                        <tr v-if="displayedEvents.length === 0">
                            <td :colspan="columns.length" class="py-4 text-center text-gray-400">{{ $t('No events found.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="displayedEvents.length > 0">
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="3" class="py-2 pl-0 pr-3 font-semibold text-gray-900">{{ $t('Sum') }}</td>
                            <td v-for="field in fields" :key="field.key" class="py-2 px-3 font-semibold text-gray-900">
                                {{ formatSum(field, sums[field.key]) }}
                            </td>
                            <td v-if="showOccupancy" class="py-2 px-3"></td>
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
    // [{ key: 'visitors', label: 'Visitors' }, ...] — nur Kennzahlen im Pro-Termin-Modus
    fields: { type: Array, required: true },
    showOccupancy: { type: Boolean, default: false },
    // room_id → effektive Kapazität (Override oder Raum-Default)
    effectiveCapacities: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['updated']);

const expanded = ref(true);
const showFilters = ref(false);
const sortKey = ref('date');
const sortAsc = ref(true);
const filterRoomId = ref(null);
const search = ref('');

const currencyFmt = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
const numberFmt = new Intl.NumberFormat('de-DE');

const columns = computed(() => [
    { key: 'name', label: 'Event', translate: true },
    { key: 'date', label: 'Date', translate: true },
    { key: 'room', label: 'Room', translate: true },
    ...props.fields.map(f => ({ key: f.key, label: f.label, translate: true })),
    ...(props.showOccupancy ? [{ key: 'occupancy', label: 'Occupancy rate', translate: true }] : []),
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

const entriesByEventId = computed(() => new Map(props.eventData.map(e => [e.event_id, e])));

const getEventValue = (eventId, fieldKey) => {
    const entry = entriesByEventId.value.get(eventId);
    return entry ? entry[fieldKey] : null;
};

const parseDate = (value) => {
    if (!value) return 0;
    const [datePart, timePart = '00:00'] = String(value).split(' ');
    const [day, month, year] = datePart.split('.');
    const [hour, minute] = timePart.split(':');
    return new Date(+year, (+month || 1) - 1, +day || 1, +hour || 0, +minute || 0).getTime();
};

const occupancyFor = (event) => {
    const sold = getEventValue(event.id, 'sold_tickets');
    const capacity = props.effectiveCapacities[event.room_id];
    if (sold === null || sold === undefined || !capacity) return null;
    return (Number(sold) / Number(capacity)) * 100;
};

const occupancyBarClass = (value) => {
    if (value >= 90) return 'bg-emerald-500';
    if (value >= 50) return 'bg-indigo-500';
    return 'bg-amber-500';
};

const sortValue = (event, key) => {
    switch (key) {
        case 'date': return parseDate(event.start_time);
        case 'room': return (event.room_name || '').toLowerCase();
        case 'occupancy': return occupancyFor(event) ?? -1;
        case 'name': return (event.name || '').toLowerCase();
        default: return parseFloat(getEventValue(event.id, key)) || 0;
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

// Der zuletzt vergangene Termin — typischer Erfassungs-Einstieg nach einer Vorstellung
const latestPastEventId = computed(() => {
    const now = Date.now();
    let best = null;
    let bestTs = -Infinity;
    props.projectEvents.forEach((event) => {
        const ts = parseDate(event.start_time);
        if (ts && ts <= now && ts > bestTs) {
            bestTs = ts;
            best = event.id;
        }
    });
    return best;
});

const sums = computed(() => {
    const result = {};
    props.fields.forEach((field) => {
        result[field.key] = displayedEvents.value.reduce(
            (sum, event) => sum + (parseFloat(getEventValue(event.id, field.key)) || 0),
            0
        );
    });
    return result;
});

const formatSum = (field, value) => field.key === 'revenue' ? currencyFmt.format(value ?? 0) : numberFmt.format(value ?? 0);

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

const saveEventValue = async (eventId, fieldKey, value) => {
    try {
        const data = {};
        data[fieldKey] = value === '' ? null : Number(value);
        await axios.put(route('projects.bi.upsert-event-data', [props.projectId, eventId]), data);
        emit('updated');
    } catch (error) {
        console.error('Error saving event data', error);
    }
};
</script>
