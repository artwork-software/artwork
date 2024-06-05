<template>
    <div>
        <div>
            <div class="text-secondaryHover xsWhiteBold px-1 py-1 flex justify-between items-center rounded-t-lg"
                 :style="{backgroundColor: event.event_type?.hex_code ?? event.event_type_color}">
                <div class="w-40 truncate">
                    {{ eventType.abbreviation }}: {{ project?.name }}
                </div>
                <div v-if="areAllShiftsCommitted(event)">
                    <IconLock stroke-width="1.5" class="h-5 w-5 text-white"/>
                </div>
            </div>
        </div>
        <div class="bg-backgroundGray rounded-b-lg" :class="[userForMultiEdit ? 'bg-blue-300/20' : 'bg-backgroundGray', dayString.is_weekend ? 'bg-white' : 'bg-backgroundGray']">
            <div v-for="shift in event.shifts" class="flex justify-between px-1">
                <!-- Drop Element --->
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
                                  :event="event"
                                  :shift-qualifications="shiftQualifications"
                                  @drop-feedback="getDropFeedback"
                                  v-if="checkIfShiftInDayString(shift)"
                />
            </div>
        </div>
    </div>
</template>
<script>

import {defineComponent} from 'vue'
import Permissions from "@/Mixins/Permissions.vue";
import {CheckIcon} from "@heroicons/vue/outline";
import ShiftDropElement from "@/Layouts/Components/ShiftPlanComponents/ShiftDropElement.vue";
import DropElement from "@/Pages/Projects/Components/DropElement.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default defineComponent({
    mixins: [Permissions, IconLib],
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
        'shiftQualifications',
        'dayString'
    ],
    methods: {
        getDropFeedback(event) {
            this.$emit('dropFeedback', event)
        },
        areAllShiftsCommitted(event) {
            return event.shifts.every(shift => shift.is_committed);
        },
        checkIfShiftInDayString(shift) {
            if(this.$page.props.user?.show_crafts?.length === 0 || this.$page.props.user?.show_crafts === null) {
                return shift.days_of_shift?.includes(this.dayString['full_day']);
            } else {
                return shift.days_of_shift?.includes(this.dayString['full_day']) && this.$page.props.user?.show_crafts?.includes(shift.craft.id);
            }

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
