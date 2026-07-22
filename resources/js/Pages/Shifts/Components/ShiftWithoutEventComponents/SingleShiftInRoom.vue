<template>
    <ShiftDropElement
        :multiEditMode="multiEditMode"
        :craft-id="craft.id"
        :userForMultiEdit="userForMultiEdit"
        :highlight-mode="highlightMode"
        :highlighted-id="highlightedId"
        :highlighted-type="highlightedType"
        :shift="shift"
        :show-room="showRoom"
        :room="room"
        :shift-qualifications="shiftQualifications"
        @drop-feedback="getDropFeedback"
        @desires-reload="dropElementDesiresReload"
        @handle-shift-and-event-for-multi-edit="handleShiftAndEventForMultiEdit"
        @click-on-edit="clickOnEdit"
        :highlightedShiftId="highlightedShiftId"
        @highlight-shift-users="highlightShiftUsers"
        @hover-shift-users="hoverShiftUsers"
    />
</template>


<script setup>

import { computed } from "vue";
import ShiftDropElement from "@/Layouts/Components/ShiftPlanComponents/ShiftDropElement.vue";
import {usePage} from "@inertiajs/vue3";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import {useShiftPlanLookups} from "@/Composeables/useShiftPlanLookups.js";
import {computeShiftFormattedDates} from "@/Composeables/calendarDateUtils.js";

const { resolveCraft } = useShiftPlanLookups();
const {
    backgroundColorWithOpacity,
    getTextColorBasedOnBackground,
} = useColorHelper();

// Define emits
const emit = defineEmits([
    'dropFeedback',
    'eventDesiresReload',
    'handleShiftAndEventForMultiEdit',
    'clickOnEdit',
    'highlightShiftUsers',
    'hoverShiftUsers'
]);


const highlightShiftUsers = (shift) => emit('highlightShiftUsers', shift)
const hoverShiftUsers = (shift) => emit('hoverShiftUsers', shift)

const props = defineProps({
    shift: Object,
    showRoom: Boolean,
    room: Object,
    highlightMode: Boolean,
    highlightedId: [String, Number],
    highlightedType: Number,
    multiEditMode: Boolean,
    userForMultiEdit: Object,
    shiftQualifications: Array,
    dayString: Object,
    eventType: String,
    highlightedShiftId: [String, Number],
    firstProjectShiftTabId: [String, Number],
})

const craft = computed(() => props.shift.craft ?? resolveCraft(props.shift.craftId) ?? {});
const formattedDates = computed(() => props.shift.formatted_dates ?? computeShiftFormattedDates(props.shift.startDate, props.shift.endDate, props.shift.start, props.shift.end));

// Methods converted to functions
const getDropFeedback = (event) => {
    emit('dropFeedback', event);
}

const clickOnEdit = (shift, mode = 'normal') => {
    emit('clickOnEdit', shift, mode);
}

const areAllShiftsCommitted = (event) => {
    // ShiftDTO liefert camelCase (isCommitted), ältere Payloads snake_case
    return event.shifts.every(shift => shift.isCommitted ?? shift.is_committed);
}

const checkIfShiftInDayString = (shift) => {
    const user = usePage().props.auth.user;
    const fd = shift.formatted_dates ?? computeShiftFormattedDates(shift.startDate, shift.endDate, shift.start, shift.end);
    const shiftCraftId = shift.craft?.id ?? shift.craftId;
    if (user?.show_crafts?.length === 0 || user?.show_crafts === null) {
        return fd.start === props.dayString['full_day'];
    } else {
        return fd.start === props.dayString['full_day'] && user?.show_crafts?.includes(shiftCraftId);
    }
}

const dropElementDesiresReload = (userId, userType, seriesShiftData) => {
    emit('eventDesiresReload', userId, userType, props.event, seriesShiftData);
}

const handleShiftAndEventForMultiEdit = (checked, shift, event) => {
    emit('handleShiftAndEventForMultiEdit', checked, shift, event);
}
</script>

<style scoped>

</style>
