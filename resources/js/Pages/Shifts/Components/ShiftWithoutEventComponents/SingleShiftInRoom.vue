<template>
    <ShiftDropElement
        :multiEditMode="multiEditMode"
        :craft-id="shift.craft.id"
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
    />
</template>

<script setup>

import ShiftDropElement from "@/Layouts/Components/ShiftPlanComponents/ShiftDropElement.vue";
import { IconLock } from "@tabler/icons-vue";
import {usePage} from "@inertiajs/vue3";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
const percentage = usePage().props.high_contrast_percent;
const {
    backgroundColorWithOpacity,
    detectParentBackgroundColor,
    getTextColorBasedOnBackground,
    parentBackgroundColor
} = useColorHelper();

// Define emits
const emit = defineEmits(['dropFeedback', 'eventDesiresReload', 'handleShiftAndEventForMultiEdit', 'clickOnEdit']);

const props = defineProps({
    shift: Object,
    showRoom: Boolean,
    room: Object,
    highlightMode: Boolean,
    highlightedId: [String, Number],
    highlightedType: String,
    multiEditMode: Boolean,
    userForMultiEdit: Object,
    shiftQualifications: Array,
    dayString: Object,
    eventType: String,
    firstProjectShiftTabId: [String, Number],
})

// Methods converted to functions
const getDropFeedback = (event) => {
    emit('dropFeedback', event);
}

const clickOnEdit = (shift) => {
    emit('clickOnEdit', shift);
}

const areAllShiftsCommitted = (event) => {
    return event.shifts.every(shift => shift.is_committed);
}

const checkIfShiftInDayString = (shift) => {
    const user = usePage().props.auth.user;
    if (user?.show_crafts?.length === 0 || user?.show_crafts === null) {
        return shift.formatted_dates.start === props.dayString['full_day'];
    } else {
        return shift.formatted_dates.start === props.dayString['full_day'] && user?.show_crafts?.includes(shift.craft.id);
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