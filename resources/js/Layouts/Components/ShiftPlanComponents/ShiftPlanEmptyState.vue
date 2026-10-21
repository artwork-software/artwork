<template>
    <!-- Leerzustand des Dienstplan-Rasters (Wochen- und Tagesansicht), genau einmal pro Raster.
         Vier Fälle in fester Reihenfolge (shiftPlanEmptyState.js): (1) keine Räume für die Disposition,
         (2) Stammdaten fehlen (Gewerke/Funktionen), (3) keine Schichten im Zeitraum, (4) Schichten
         vorhanden, aber Filter/Anzeigeeinstellungen blenden alle aus. Rendert nichts, wenn keiner zutrifft.
         Overlay z-[45]: über den Zeilentrennern des Wochenrasters (z-40), unter der Raumspalte (z-50). -->
    <div
        v-if="mode"
        :class="overlay ? 'absolute inset-x-0 top-14 z-[45] flex justify-center px-4 pointer-events-none' : 'flex justify-center px-4 py-6'"
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

                    <!-- Fall 2: welche Stammdaten fehlen -->
                    <ul v-if="mode === 'no-master-data'" class="mt-2 space-y-1 text-xs">
                        <li class="flex items-center gap-1.5" :class="craftsCount > 0 ? 'text-text-subtle' : 'text-danger'">
                            <PropertyIcon :name="craftsCount > 0 ? 'IconCheck' : 'IconX'" class="size-3.5" :stroke-width="2" />
                            {{ $t('Crafts') }}: {{ craftsCount }}
                        </li>
                        <li class="flex items-center gap-1.5" :class="functionsCount > 0 ? 'text-text-subtle' : 'text-danger'">
                            <PropertyIcon :name="functionsCount > 0 ? 'IconCheck' : 'IconX'" class="size-3.5" :stroke-width="2" />
                            {{ $t('Functions') }}: {{ functionsCount }}
                        </li>
                    </ul>

                    <!-- Fall 1: mögliche Ursachen neben der Raumfreigabe -->
                    <p v-if="mode === 'no-rooms' && (filtersActive || hideUnoccupiedRooms)" class="mt-2 text-xs text-text-subtle">
                        <template v-if="filtersActive">{{ $t('A filter is active — rooms hidden by the filter are not shown.') }} </template>
                        <template v-if="hideUnoccupiedRooms">{{ $t('"Hide unoccupied rooms" is active in the display settings.') }}</template>
                    </p>

                    <!-- Fall 3: ein Filter kann auch serverseitig alle Schichten ausschließen (z. B. Gewerke) -->
                    <p v-if="mode === 'no-shifts' && filtersActive" class="mt-2 text-xs text-text-subtle">
                        {{ $t('A filter is active — shifts outside the filter are not shown.') }}
                    </p>

                    <!-- Fall 4: welche Filter/Einstellungen die vorhandenen Schichten ausblenden -->
                    <ul v-if="mode === 'filtered' && filteredReasons.length" class="mt-2 space-y-1 text-xs text-text-subtle">
                        <li v-for="reason in filteredReasons" :key="reason" class="flex items-start gap-1.5">
                            <PropertyIcon name="IconFilter" class="mt-px size-3.5 shrink-0" :stroke-width="2" />
                            <span>{{ $t(reason) }}</span>
                        </li>
                    </ul>

                    <div v-if="mode !== 'filtered'" class="mt-3 flex flex-wrap items-center gap-2">
                        <template v-if="mode === 'no-rooms'">
                            <Link
                                v-if="canManageRooms"
                                :href="route('areas.management')"
                                class="inline-flex items-center gap-1 rounded-md bg-accent-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-accent-600/90"
                            >
                                <PropertyIcon name="IconDoor" class="size-3.5" />
                                {{ $t('Room management') }}
                            </Link>
                            <span v-else class="text-xs text-text-subtle">{{ $t('Ask an administrator to release rooms for the duty roster.') }}</span>
                        </template>

                        <template v-else-if="mode === 'no-master-data'">
                            <Link
                                v-if="canOpenShiftSettings"
                                :href="route('shift.settings')"
                                class="inline-flex items-center gap-1 rounded-md bg-accent-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-accent-600/90"
                            >
                                <PropertyIcon name="IconSettings" class="size-3.5" />
                                {{ $t('Shift settings') }}
                            </Link>
                            <span v-else class="text-xs text-text-subtle">{{ $t('Ask an administrator to create crafts and functions in the shift settings.') }}</span>
                        </template>

                        <template v-else>
                            <template v-if="canPlan">
                                <BaseUIButton
                                    type="button"
                                    is-small
                                    :label="$t('Add Shift')"
                                    icon="IconCirclePlus"
                                    @click="$emit('add-shift')"
                                />
                                <BaseUIButton
                                    type="button"
                                    is-small
                                    variant="secondary"
                                    :label="$t('Add Shift based on templates')"
                                    icon="IconCopyPlus"
                                    @click="$emit('add-from-template')"
                                />
                            </template>
                            <span v-else class="text-xs text-text-subtle">{{ $t('Planners create shifts here; you will see them as soon as they are planned.') }}</span>
                        </template>
                    </div>
                </div>
                <button
                    v-if="mode === 'no-shifts' || mode === 'filtered'"
                    type="button"
                    class="shrink-0 rounded-md p-1 text-text-subtle hover:bg-surface-sunken hover:text-text"
                    :aria-label="$t('Close')"
                    @click="$emit('dismiss')"
                >
                    <PropertyIcon name="IconX" class="size-4" :stroke-width="1.5" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed} from 'vue'
import {Link} from '@inertiajs/vue3'
import {can, is} from 'laravel-permission-to-vuejs'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import {resolveShiftPlanEmptyState, shiftPlanFilteredReasons} from './shiftPlanEmptyState.js'

const props = defineProps({
    roomsCount: {type: Number, default: 0},
    craftsCount: {type: Number, default: 0},
    functionsCount: {type: Number, default: 0},
    hasShifts: {type: Boolean, default: false},
    /** false = Schichten vorhanden, aber Filter/Anzeigeeinstellungen blenden alle aus */
    hasVisibleShifts: {type: Boolean, default: true},
    /** true = schwebende Karte über dem Wochenraster, false = im Textfluss (Tagesansicht) */
    overlay: {type: Boolean, default: false},
    filtersActive: {type: Boolean, default: false},
    hideUnoccupiedRooms: {type: Boolean, default: false},
    onlyNotFullyStaffed: {type: Boolean, default: false},
    hideUnoccupiedDays: {type: Boolean, default: false},
})

defineEmits(['add-shift', 'add-from-template', 'dismiss'])

const isAdmin = () => is('artwork admin')
const canPlan = computed(() => isAdmin() || can('can plan shifts'))
const canManageRooms = computed(() => isAdmin() || can('create, delete and update rooms'))
const canOpenShiftSettings = computed(() => isAdmin() || can('shift.settings_view_edit'))

const mode = computed(() => resolveShiftPlanEmptyState({
    roomsCount: props.roomsCount,
    craftsCount: props.craftsCount,
    functionsCount: props.functionsCount,
    hasShifts: props.hasShifts,
    hasVisibleShifts: props.hasVisibleShifts,
}))

const filteredReasons = computed(() => shiftPlanFilteredReasons({
    filtersActive: props.filtersActive,
    onlyNotFullyStaffed: props.onlyNotFullyStaffed,
    hideUnoccupiedDays: props.hideUnoccupiedDays,
}))

const icon = computed(() => ({
    'no-rooms': 'IconDoorOff',
    'no-master-data': 'IconSettings',
    'no-shifts': 'IconCalendarOff',
    'filtered': 'IconFilterOff',
}[mode.value] ?? 'IconInfoCircle'))

const title = computed(() => ({
    'no-rooms': 'No rooms released for the duty roster',
    'no-master-data': 'Master data missing',
    'no-shifts': 'No shifts in this period',
    'filtered': 'All shifts are hidden',
}[mode.value] ?? ''))

const text = computed(() => ({
    'no-rooms': 'Only rooms marked as "relevant for disposition" appear in the duty roster. Release rooms in the room management.',
    'no-master-data': 'Shifts need at least one craft and one function (the role in the shift). Create them in the shift settings first.',
    'no-shifts': 'There are no shifts in the selected period yet. Create a shift for the first room and day or apply shift templates.',
    'filtered': 'There are shifts in the selected period, but the current filters and display settings hide all of them. Adjust them to see the shifts again.',
}[mode.value] ?? ''))
</script>
