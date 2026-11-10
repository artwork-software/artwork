<template>
    <!-- Hinweiskarte, wenn der Kalender keinen einzigen Raum anzeigt (siehe calendarEmptyState.js).
         Schwebt unter der Topbar, damit sie beim Scrollen durch die leeren Tage sichtbar bleibt. -->
    <div
        v-if="mode"
        class="pointer-events-none fixed inset-x-0 z-30 flex justify-center px-4 lg:left-16"
        :style="{ top: topOffset + 16 + 'px' }"
    >
        <div
            class="pointer-events-auto w-full max-w-xl rounded-2xl border border-border bg-surface p-5 shadow-lg"
            role="status"
        >
            <div class="flex items-start gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-surface-sunken">
                    <PropertyIcon :name="icon" class="size-5 text-text-subtle" :stroke-width="1.5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-text">{{ $t(title) }}</h3>
                    <p class="mt-1 text-xs text-text-subtle">{{ $t(text) }}</p>
                    <p v-if="mode === 'no-events' && filtersActive" class="mt-2 text-xs text-text-subtle">
                        {{ $t('A filter is also active — only events matching the filter count as occupancy.') }}
                    </p>
                    <div v-if="mode === 'no-rooms'" class="mt-3">
                        <Link
                            v-if="canManageRooms"
                            :href="route('areas.management')"
                            class="inline-flex items-center gap-1 rounded-md bg-accent-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-accent-600/90"
                        >
                            <PropertyIcon name="IconDoor" class="size-3.5" />
                            {{ $t('Room management') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'
import {Link} from '@inertiajs/vue3'
import {can, is} from 'laravel-permission-to-vuejs'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import {resolveCalendarEmptyState} from '@/Components/Calendar/calendarEmptyState.js'

const props = defineProps({
    roomsCount: {type: Number, default: 0},
    hideUnoccupiedRooms: {type: Boolean, default: false},
    filtersActive: {type: Boolean, default: false},
    /** Höhe der fixen Topbar in px */
    topOffset: {type: Number, default: 0},
})

const canManageRooms = computed(() => is('artwork admin') || can('create, delete and update rooms'))

const mode = computed(() => resolveCalendarEmptyState({
    roomsCount: props.roomsCount,
    hideUnoccupiedRooms: props.hideUnoccupiedRooms,
    filtersActive: props.filtersActive,
}))

const icon = computed(() => ({
    'no-events': 'IconCalendarOff',
    'filtered': 'IconFilterOff',
    'no-rooms': 'IconDoorOff',
}[mode.value] ?? 'IconInfoCircle'))

const title = computed(() => ({
    'no-events': 'No events in the selected period',
    'filtered': 'No rooms match the current filter',
    'no-rooms': 'No rooms available',
}[mode.value] ?? ''))

const text = computed(() => ({
    'no-events': 'There are no events in the selected period yet. Because "Hide unoccupied rooms" is active in the display settings, rooms without events are not shown.',
    'filtered': 'The active filter hides all rooms. Adjust or reset the filter to show rooms again.',
    'no-rooms': 'There are no rooms you can see in the calendar yet. Rooms are created in the room management.',
}[mode.value] ?? ''))
</script>
