<template>
    <div>
        <div>
            <div class="text-secondaryHover xsWhiteBold px-1 py-1 flex justify-between items-center rounded-t-lg"
                 :style="{backgroundColor: backgroundColorWithOpacity(event.eventTypeColor ?? eventType?.hex_code, percentage), color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.eventTypeColor ?? eventType?.hex_code, percentage))}">
                <a :href="route('projects.tab', {project: event.projectId, projectTab: firstProjectShiftTabId})" class="w-40 truncate cursor-pointer hover:text-gray-300 transition-all duration-150 ease-in-out">
                    {{ event.eventTypeAbbreviation ?? eventType?.abbreviation }}: {{ event.projectName }}
                </a>
                <div v-if="areAllShiftsCommitted(event)">
                    <IconLock stroke-width="1.5" class="h-5 w-5 text-white"/>
                </div>
            </div>
        </div>
        <div class="bg-backgroundGray rounded-b-lg" :class="[userForMultiEdit ? 'bg-blue-300/20' : 'bg-backgroundGray', dayString.is_weekend ? 'bg-white' : 'bg-backgroundGray']">
            <div v-for="shift in event.shifts" class="flex justify-between">
                <!-- Drop Element -->
                <ShiftDropElement v-if="checkIfShiftInDayString(shift)"
                                  :multiEditMode="multiEditMode"
                                  :craft-id="shift.craft.id"
                                  :userForMultiEdit="userForMultiEdit"
                                  :highlight-mode="highlightMode"
                                  :highlighted-id="highlightedId"
                                  :highlighted-type="highlightedType"
                                  :shift="shift"
                                  :show-room="showRoom"
                                  :room="room"
                                  :event="event"
                                  :shift-qualifications="shiftQualifications"
                                  @drop-feedback="getDropFeedback"
                                  @desires-reload="dropElementDesiresReload"
                                  @handle-shift-and-event-for-multi-edit="handleShiftAndEventForMultiEdit"
                />
            </div>
        </div>
    </div>
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
const emit = defineEmits(['dropFeedback', 'eventDesiresReload', 'handleShiftAndEventForMultiEdit']);

// Define props
const props = defineProps({
    event: Object,
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
});


// Methods converted to functions
const getDropFeedback = (event) => {
    emit('dropFeedback', event);
}

const areAllShiftsCommitted = (event) => {
    return event.shifts.every(shift => shift.is_committed);
}

const checkIfShiftInDayString = (shift) => {
    const user = usePage().props.user;
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
.eventType0 {
    background-color: #7F7E88;
}

.eventType1 {
    background-color: #631D53;
}

.eventType2 {
    background-color: #D84387;
}

.eventType3 {
    background-color: #E97A45;
}

.eventType4 {
    background-color: #CB8913;
}

.eventType5 {
    background-color: #648928;
}

.eventType6 {
    background-color: #35A965;
}

.eventType7 {
    background-color: #35ACB2;
}

.eventType8 {
    background-color: #2290C1;
}

.eventType9 {
    background-color: #50908E;
}

.eventType10 {
    background-color: #23485B;
}
</style>
