<template>
    <div class="flex h-full gap-2">
        <Timeline :time-line="timeLine" :event="event"/>
        <div class="w-[175px]" v-for="shift in shifts">
            <SingleShift :shift="shift"/>
        </div>
        <!-- Empty -->
        <div class="w-[175px] flex items-center justify-center border-2 border-dashed" @click="showAddShiftModal = true">
            <PlusCircleIcon class="h-4 w-4 rounded-full bg-backgroundBlue" />
        </div>
    </div>
    <AddShiftModal :crafts="crafts" :event="event" v-if="showAddShiftModal" @closed="showAddShiftModal = false"/>
</template>
<script>
import {defineComponent} from 'vue'
import {PlusCircleIcon} from "@heroicons/vue/outline";
import Timeline from "@/Pages/Projects/Components/Timeline.vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import dayjs from "dayjs";
import DropElement from "@/Pages/Projects/Components/DropElement.vue";
import {XIcon} from "@heroicons/vue/solid";
import SingleShift from "@/Pages/Projects/Components/SingleShift.vue";

export default defineComponent({
    name: "TimeLineShiftsComponent",

    props: ['timeLine', 'shifts', 'event', 'crafts'],
    components: {
        SingleShift,
        DropElement,
        AddShiftModal,
        Timeline,
        PlusCircleIcon,
        XIcon
    },
    data(){
        return {
            showAddShiftModal: false
        }
    },
    methods: {
        dayjs,
    },
})
</script>


<style scoped>

</style>
