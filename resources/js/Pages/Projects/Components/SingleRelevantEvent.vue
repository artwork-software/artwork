<template>
    <div>
        <!-- Event Header -->
        <div class="w-full h-12 flex items-center justify-between px-4 text-white uppercase text-sm" :class="event.event_type.svg_name">
            <div>
                {{ event.event?.start_time }} | {{ event.event_type.abbreviation }} | {{ event.room.name }}

            </div>
            <div>
                // menu

            </div>
        </div>

        <div class="flex justify-start mt-3 overflow-x-scroll gap-3 h-full">
            <TimeLineShiftsComponent :time-line="event.timeline" :shifts="event.shifts" :crafts="crafts" :event="event.event"/>
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'
import Timeline from "@/Pages/Projects/Components/Timeline.vue";
import SingleShift from "@/Pages/Projects/Components/TimeLineShiftsComponent.vue";
import {PlusCircleIcon} from "@heroicons/vue/outline";
import TimeLineShiftsComponent from "@/Pages/Projects/Components/TimeLineShiftsComponent.vue";

export default defineComponent({
    name: "SingleRelevantEvent",
    props: ['event', 'crafts'],
    components: {TimeLineShiftsComponent, PlusCircleIcon, SingleShift, Timeline}
})
</script>


<style scoped>

.eventType0 {
    background-color: #A7A6B115;
    stroke: #7F7E88;
    color: #7F7E88
}

.eventType1 {
    background-color: #641a5415;
    stroke: #631D53;
    color: #631D53
}

.eventType2 {
    background-color: #da3f8715;
    stroke: #D84387;
    color: #D84387
}

.eventType3 {
    background-color: #eb7a3d15;
    stroke: #E97A45;
    color: #E97A45
}

.eventType4 {
    background-color: #f1b64015;
    stroke: #CB8913;
    color: #CB8913
}

.eventType5 {
    background-color: #86c55415;
    stroke: #648928;
    color: #648928
}

.eventType6 {
    background-color: #2eaa6315;
    stroke: #35A965;
    color: #35A965
}

.eventType7 {
    background-color: #3dc3cb15;
    stroke: #35ACB2;
    color: #35ACB2
}

.eventType8 {
    background-color: #168fc315;
    stroke: #2290C1;
    color: #2290C1
}

.eventType9 {
    background-color: #4d908e15;
    stroke: #50908E;
    color: #50908E
}

.eventType10 {
    background-color: #21485C15;
    stroke: #23485B;
    color: #23485B
}
</style>
