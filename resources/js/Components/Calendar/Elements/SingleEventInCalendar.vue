<template>
    <div :class="isHeightFull ? 'h-full' : ''">
        <!-- Kompaktkachel unterhalb 80 % Zoom: eine Zeile (Zeit + Titel), Klick öffnet das Termin-Modal -->
        <CompactEventInCalendar
            v-if="!event.isMinimal && isCompact && !isInDailyView"
            :event="event"
            :width="typeof width === 'number' ? width : parseInt(width, 10) || 196"
            :multi-edit="multiEdit"
            @editEvent="e => emit('editEvent', e)"
        />
        <component
            v-else
            :is="event.isMinimal ? MinimalEventInCalendar : FullEventInCalendar"
            :event="event"
            :multi-edit="multiEdit"
            :font-size="fontSize"
            :line-height="lineHeight"
            :rooms="rooms"
            :has-admin-role="hasAdminRole"
            :width="width"
            :first_project_tab_id="first_project_tab_id"
            :firstProjectShiftTabId="firstProjectShiftTabId"
            :verifierForEventTypIds="verifierForEventTypIds"
            :is-planning="isPlanning"
            :is-in-daily-view="isInDailyView"
            :is-height-full="isHeightFull"
            @editEvent="e => emit('editEvent', e)"
            @editSubEvent="e => openAddSubEventModal"
            @openAddSubEventModal="openAddSubEventModal"
            @openConfirmModal="(e, type) => emit('open-confirm-modal', e, type)"
            @showDeclineEventModal="e => emit('show-decline-event-modal', e)"
            @acceptRoomRequest="e => emit('accept-room-request', e)"
            @changedMultiEditCheckbox="(...args) => emit('changed-multi-edit-checkbox', ...args)"
        />
    </div>
</template>

<script setup>
import { computed, defineComponent } from 'vue'

// Synchron importieren – der äußere defineAsyncComponent in BaseCalendar
// sorgt bereits für Code-Splitting. Ein zweiter Async-Layer erzeugt eine
// Waterfall (2–3 Frames Verzögerung pro Event → Events "poppen" einzeln auf).
import FullEventInCalendar from "@/Components/Calendar/Elements/Events/FullEventInCalendar.vue"
import CompactEventInCalendar from "@/Components/Calendar/Elements/Events/CompactEventInCalendar.vue"
import { useCalendarZoom } from "@/Composeables/useCalendarZoom.js"

// Kompaktmodus reagiert live auf Zoom-Wechsel (kein Page-Reload mehr)
const { isCompact } = useCalendarZoom()

// Minimale Inline-Variante, falls event.isMinimal true ist
const MinimalEventInCalendar = defineComponent({
    name: 'MinimalEventInCalendar',
    props: {
        event: { type: Object, required: true },
        width: { type: String, default: '248px' }
    },
    setup() {
        // Über 100 % Kalender-Zoom wächst die Karte per CSS zoom mit (wie FullEventInCalendar).
        // Reaktiv aus dem Zoom-Store — Zoom-Wechsel laufen ohne Reload (kein stale Mount-Wert)
        const { zoomFactor } = useCalendarZoom()
        return { contentZoom: computed(() => (zoomFactor.value > 1 ? zoomFactor.value : 1)) }
    },
    template: `
    <div class="rounded-lg border border-gray-200 bg-white px-2 py-1 overflow-hidden"
         :style="{ minWidth: width, maxWidth: width, width: width, zoom: contentZoom }">
      <div class="text-xs font-medium truncate">{{ event.title ?? event.eventName ?? 'Event' }}</div>
    </div>`
})

const emit = defineEmits([
    'editEvent',
    'editSubEvent',
    'openAddSubEventModal',
    'openConfirmModal',
    'showDeclineEventModal',
    'acceptRoomRequest',
    'changedMultiEditCheckbox'
]);

defineProps({
    event: { type: Object, required: true },
    multiEdit: { type: Boolean, default: false },
    fontSize: { type: String, default: '0.875rem' },
    lineHeight: { type: String, default: '1.25rem' },
    first_project_tab_id: { type: Number, default: null },
    firstProjectShiftTabId: { type: Number, default: 1 },
    rooms: { type: [Object, Array], default: () => [] },
    hasAdminRole: { type: Boolean, default: false },
    width: { type: [String, Number], default: '248px' },
    isInDailyView: { type: Boolean, default: false },
    verifierForEventTypIds: { type: Array, default: () => [] },
    isPlanning: { type: Boolean, default: false },
    isHeightFull: {
        type: Boolean,
        required: false,
        default: false
    },
})

const openAddSubEventModal = (mainEvent, mode, subEvent) => {
    emit('open-add-sub-event-modal', mainEvent, mode, subEvent);
}
</script>
