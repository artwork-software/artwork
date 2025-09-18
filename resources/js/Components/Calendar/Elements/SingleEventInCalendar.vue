<template>
    <div :class="isHeightFull ? 'h-full' : ''">
        <component
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
            @editEvent="e => emit('editEvent', e)"
            @editSubEvent="e => openAddSubEventModal"
            @openAddSubEventModal="openAddSubEventModal"
            @openConfirmModal="(e, type) => emit('open-confirm-modal', e, type)"
            @showDeclineEventModal="e => emit('show-decline-event-modal', e)"
            @changedMultiEditCheckbox="(...args) => emit('changed-multi-edit-checkbox', ...args)"
        />
    </div>
</template>

<script setup>
import { defineAsyncComponent, defineComponent } from 'vue'

// Async laden (Original-Datei vorhanden)
const FullEventInCalendar = defineAsyncComponent(() => import("@/Components/Calendar/Elements/Events/FullEventInCalendar.vue"))

// Minimale Inline-Variante, falls event.isMinimal true ist
const MinimalEventInCalendar = defineComponent({
    name: 'MinimalEventInCalendar',
    props: {
        event: { type: Object, required: true },
        width: { type: String, default: '248px' }
    },
    template: `
    <div class="rounded-lg border border-gray-200 bg-white px-2 py-1 overflow-hidden"
         :style="{ minWidth: width, maxWidth: width, width: width }">
      <div class="text-xs font-medium truncate">{{ event.title ?? event.eventName ?? 'Event' }}</div>
    </div>`
})

const emit = defineEmits([
    'editEvent',
    'editSubEvent',
    'openAddSubEventModal',
    'openConfirmModal',
    'showDeclineEventModal',
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
