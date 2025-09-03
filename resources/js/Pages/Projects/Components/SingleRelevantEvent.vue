<template>
    <div class="mb-3 flex flex-col gap-2" :id="`event-${evt.event.id}`">
        <!-- Header / Summary -->
        <div
            class="w-full min-h-12 rounded-xl border shadow-sm overflow-hidden"
            :style="{
            borderColor: baseHex + '40',
        background: baseHex + '30',
        color: headerFg
      }"
        >
            <div class="flex items-center justify-between px-4 py-2">
                <!-- Chips links -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Datum/Zeit -->
                    <span class="chip">
            <template v-if="!evt.event.allDay">
              {{ evt.event.formatted_dates.start }} – {{ evt.event.formatted_dates.end }}
            </template>
            <template v-else>
              {{ evt.event.event_date_without_time.start }} · {{ $t('All day') }}
            </template>
          </span>

                    <span class="chip" v-if="!evt.event.allDay">
            {{ evt.event.start_time_without_day }} – {{ evt.event.end_time_without_day }}
          </span>

                    <!-- Typ-Abkürzung -->
                    <span class="chip chip-strong">
            {{ evt.event_type.abbreviation }}
          </span>

                    <!-- Raum -->
                    <span class="chip">
            {{ evt.room?.name }}
          </span>

                    <!-- Serien-Icon -->
                    <span v-if="evt.event.is_series" class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium bg-white/15 ring-1 ring-inset ring-white/20">
            <IconRepeat class="h-3.5 w-3.5" />
            <span class="ml-1">Serie</span>
          </span>
                </div>

                <!-- Actions rechts -->
                <div class="flex items-center gap-2">
                    <!-- Expand/Collapse -->
                    <button
                        type="button"
                        class="rounded-lg p-1.5 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/30 transition"
                        @click="toggleShift"
                        :aria-expanded="showShift"
                        :aria-controls="`event-body-${evt.event.id}`"
                    >
                        <ChevronDownIcon v-if="!showShift" class="h-4 w-4" />
                        <ChevronUpIcon v-else class="h-4 w-4" />
                    </button>

                    <!-- Kontextmenü -->
                    <div v-if="canManageShifts" class="mt-0.5">
                        <BaseMenu
                            white-menu-background
                            has-no-offset
                            dots-size="h-4 w-4"
                            menu-width="w-fit"
                        >
                            <BaseMenuItem white-menu-background title="Delete shift planning" icon="IconTrash" @click="openDeleteConfirmModal" />
                            <BaseMenuItem white-menu-background title="Save shift planning as a template" icon="IconFilePlus" @click="saveShiftAsPreset" />
                            <BaseMenuItem white-menu-background title="Import shift planning from template" icon="IconFileImport" @click="showImportShiftTemplateModal = true" />
                        </BaseMenu>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <ConfirmDeleteModal
            v-if="showConfirmDeleteModal"
            :title="$t('Delete shift planning')"
            :description="$t('Would you like to delete the shift planning?')"
            @closed="closeConfirmDeleteModal"
            @delete="deleteShift"
        />
        <AddShiftPresetModal
            v-if="showAddShiftPresetModal"
            @closed="showAddShiftPresetModal = false"
            :event_types="eventTypes"
            :event_type_id="evt.event_type.id"
            :event-id="evt.event.id"
        />
        <ImportShiftTemplate
            v-if="showImportShiftTemplateModal"
            @closed="closeImportShiftTemplateModal"
            :event_type="evt.event_type"
            :eventId="evt.event.id"
        />

        <!-- Timeline -->
        <div v-if="showShift" :id="`event-body-${evt.event.id}`">
            <TimeLineShiftsComponent
                ref="timelineShiftsComponent"
                :time-line="evt.timeline"
                :shifts="evt.shifts"
                :crafts="crafts"
                :currentUserCrafts="currentUserCrafts"
                :event="evt.event"
                :shift-qualifications="shiftQualifications"
                :shift-time-presets="shiftTimePresets"
                :can-edit-component="canEditComponent"
                @dropFeedback="emitDropFeedback"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, ref, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { can, is } from 'laravel-permission-to-vuejs'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import AddShiftPresetModal from '@/Pages/Projects/Components/AddShiftPresetModal.vue'
import ImportShiftTemplate from '@/Pages/Projects/Components/ImportShiftTemplate.vue'
import TimeLineShiftsComponent from '@/Pages/Projects/Components/TimeLineShiftsComponent.vue'
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/outline'
import {IconRepeat} from '@tabler/icons-vue'// falls du ein eigenes Icon nutzt – ansonsten ersetzen/entfernen
import { useColorHelper } from '@/Composeables/UseColorHelper.js' // enthält backgroundColorWithOpacity & getTextColorBasedOnBackground

defineOptions({ name: 'SingleRelevantEvent' })

const props = defineProps({
    event: { type: Object, required: true },
    crafts: { type: Array, required: true },
    eventTypes: { type: Array, required: true },
    currentUserCrafts: { type: Array, required: true },
    shiftQualifications: { type: Array, required: true },
    shiftTimePresets: { type: Array, required: true },
    canEditComponent: { type: Boolean, required: false, default: true },
})
const emit = defineEmits(['dropFeedback'])

const page = usePage()
const evt = computed(() => props.event)
const urlEventId = computed(() => parseInt(page?.props?.urlParameters?.eventId ?? 0))

// UI state
const showConfirmDeleteModal = ref(false)
const showAddShiftPresetModal = ref(false)
const showImportShiftTemplateModal = ref(false)
const showShift = ref(urlEventId.value ? urlEventId.value === parseInt(evt.value.event.id) : true)
const timelineShiftsComponent = ref(null)

// Permissions
const canManageShifts = computed(() => can('can plan shifts') || is('artwork admin'))

// Farben
const { backgroundColorWithOpacity, getTextColorBasedOnBackground } = useColorHelper?.() ?? {
    backgroundColorWithOpacity: (hex, op) => `rgba(100,116,139,${op/100})`, // Fallback slate-500
    getTextColorBasedOnBackground: () => '#111827',
}
const baseHex = computed(() => evt.value?.event_type?.hex_code || '#64748b') // Fallback slate-500
const headerBg = computed(() => {
    // dezenter Verlauf mit Brand-Farbe
    const c10 = backgroundColorWithOpacity(baseHex.value, 12)
    const c18 = backgroundColorWithOpacity(baseHex.value, 18)
    const c30 = backgroundColorWithOpacity(baseHex.value, 30)
    return `linear-gradient(90deg, ${c18} 0%, ${c30} 50%, ${c10} 100%)`
})
const headerFg = computed(() => getTextColorBasedOnBackground(baseHex.value))
const headerFgIsDark = computed(() => {
    const c = headerFg.value?.replace('#','')
    const r = parseInt(c.substring(0,2),16), g = parseInt(c.substring(2,4),16), b = parseInt(c.substring(4,6),16)
    // simple luminanzschätzung
    const Y = 0.2126*r + 0.7152*g + 0.0722*b
    return Y < 160
})

// Actions
function toggleShift() {
    showShift.value = !showShift.value
}
function openDeleteConfirmModal() {
    showConfirmDeleteModal.value = true
}
function closeConfirmDeleteModal() {
    showConfirmDeleteModal.value = false
}
function deleteShift() {
    router.delete(`/events/${evt.value.event.id}/shifts`, { preserveScroll: true })
    showConfirmDeleteModal.value = false
}
function saveShiftAsPreset() {
    showAddShiftPresetModal.value = true
}
function closeImportShiftTemplateModal() {
    showImportShiftTemplateModal.value = false
    nextTick(() => {
        timelineShiftsComponent.value?.reinitializeEventContainerPlacements?.()
    })
}
function emitDropFeedback(e) {
    emit('dropFeedback', e)
}
</script>

<style scoped>


</style>
