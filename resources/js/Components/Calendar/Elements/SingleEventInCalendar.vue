<template>
    <div class="h-full">
        <MinimalEventInCalendar
            v-if="event.isMinimal"
        />
        <FullEventInCalendar
            v-else
            :event="event"
            :multi-edit="multiEdit"
            :font-size="fontSize"
            :line-height="lineHeight"
            :rooms="rooms"
            :has-admin-role="hasAdminRole"
            :width="width"
            :first_project_tab_id="first_project_tab_id"
            :firstProjectShiftTabId="firstProjectShiftTabId"
            @editEvent="e => emit('editEvent', e)"
            @editSubEvent="e => emit('edit-sub-event', e)"
            @openAddSubEventModal="e => emit('open-add-sub-event-modal', e)"
            @openConfirmModal="(e, type) => emit('open-confirm-modal', e, type)"
            @showDeclineEventModal="e => emit('show-decline-event-modal', e)"
            @changedMultiEditCheckbox="(...args) => emit('changed-multi-edit-checkbox', ...args)"
            :verifierForEventTypIds="verifierForEventTypIds"
            :is-planning="isPlanning"
        />
    </div>
</template>
<script setup>

import FullEventInCalendar from "@/Components/Calendar/Elements/Events/FullEventInCalendar.vue";
import MinimalEventInCalendar from "@/Components/Calendar/Elements/Events/MinimalEventInCalendar.vue";

const emit = defineEmits([
    'editEvent',
    'editSubEvent',
    'openAddSubEventModal',
    'openConfirmModal',
    'showDeclineEventModal',
    'changedMultiEditCheckbox'
]);

const props = defineProps({
    event: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    },
    fontSize: {
        type: String,
        required: false,
        default: '0.875rem'
    },
    lineHeight: {
        type: String,
        required: false,
        default: '1.25rem'
    },
    first_project_tab_id: {
        type: Number,
        required: false,
        default: 1
    },
    project: {
        type: Object,
        required: false,
        default: null
    },
    hasAdminRole: {
        type: Boolean,
        required: false,
        default: false
    },
    rooms: {
        type: Object,
        required: true
    },
    width: {
        type: Number,
        required: true
    },
    firstProjectShiftTabId: {
        type: [Number, String],
        required: false,
        default: null
    },
    isHeightFull: {
        type: Boolean,
        required: false,
        default: false
    },
    isInDailyView: {
        type: Boolean,
        required: false,
        default: false
    },
    verifierForEventTypIds: {
        type: Array,
        required: false,
        default: []
    },
    isPlanning: {
        type: Boolean,
        required: false,
        default: false
    }
});

</script>
