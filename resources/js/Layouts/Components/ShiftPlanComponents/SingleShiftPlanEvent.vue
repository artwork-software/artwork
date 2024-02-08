<template>
    <div>
        <div>
            <div class="text-secondaryHover xsWhiteBold px-1 py-1 flex justify-between items-center"
                 :class="eventType.svg_name">
                <div class="w-40 truncate">
                    {{ eventType.abbreviation }}: {{ project?.name }}
                </div>
                <div v-if="areAllShiftsCommitted(event)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11.975" height="13.686" class="ml-1"
                         viewBox="0 0 11.975 13.686">
                        <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                              d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                              fill="#fcfcfb"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-backgroundGray" :class="userForMultiEdit ? 'bg-blue-300/20' : 'bg-backgroundGray'">
            <div v-for="shift in event.shifts" class="flex justify-between px-1">
                <!-- Drop Element --->
                <ShiftDropElement :multiEditMode="multiEditMode"
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
                />
            </div>
        </div>
    </div>
</template>
<script>

import {defineComponent} from 'vue'
import Permissions from "@/mixins/Permissions.vue";
import {CheckIcon} from "@heroicons/vue/outline";
import ShiftDropElement from "@/Layouts/Components/ShiftPlanComponents/ShiftDropElement.vue";
import DropElement from "@/Pages/Projects/Components/DropElement.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        DropElement,
        ShiftDropElement,
        CheckIcon,
    },
    props: [
        'event',
        'project',
        'eventType',
        'showRoom',
        'room',
        'highlightMode',
        'highlightedId',
        'highlightedType',
        'multiEditMode',
        'userForMultiEdit',
        'shiftQualifications'
    ],
    methods: {
        getDropFeedback(event) {
            this.$emit('dropFeedback', event)
        },
        areAllShiftsCommitted(event) {
            return event.shifts.every(shift => shift.is_committed);
        }
    },
    emits: ['dropFeedback'],
})
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
