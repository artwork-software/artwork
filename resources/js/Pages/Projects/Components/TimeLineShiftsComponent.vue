<template>
    <div class="flex h-full gap-2">
        <Timeline :time-line="timeLine" :event="event"/>
        <div class="w-[175px]" v-for="shift in shifts">
            <SingleShift :shift="shift" :crafts="crafts" :event="event" :currentUserCrafts="currentUserCrafts"/>
        </div>
        <!-- Empty -->
        <div class="w-[175px] flex items-center justify-center border-2 border-dashed" @click="checkWhichModal">
            <PlusCircleIcon class="h-4 w-4 rounded-full bg-backgroundBlue" />
        </div>
    </div>
    <AddShiftModal v-if="showAddShiftModal"
                   :crafts="crafts"
                   :event="event"
                   :currentUserCrafts="currentUserCrafts"
                   @closed="closeAddShiftModal"
                   :buffer="buffer"/>
    <ChooseShiftSeries :event="event" v-if="showChooseShiftSeriesModal" @close-modal="showChooseShiftSeriesModal = false" @returnBuffer="updateBuffer" />
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
import ChooseShiftSeries from "@/Pages/Projects/Components/ChooseShiftSeries.vue";

export default defineComponent({
    name: "TimeLineShiftsComponent",

    props: ['timeLine', 'shifts', 'event', 'crafts', 'currentUserCrafts'],
    components: {
        SingleShift,
        DropElement,
        AddShiftModal,
        Timeline,
        PlusCircleIcon,
        XIcon,
        ChooseShiftSeries,
    },
    data(){
        return {
            showAddShiftModal: false,
            showChooseShiftSeriesModal: false,
            buffer: {
                onlyThisDay: false,
                start: null,
                end: null,
            }
        }
    },
    methods: {
        dayjs,
        checkWhichModal() {
            if (this.event.is_series) {
                this.showChooseShiftSeriesModal = true
            } else {
                this.showAddShiftModal = true
            }
        },
        updateBuffer(buffer){
            this.buffer = buffer
            this.showChooseShiftSeriesModal = false
            this.showAddShiftModal = true
        },
        closeAddShiftModal(){
            this.showAddShiftModal = false
            this.buffer = {
                onlyThisDay: false,
                start: null,
                end: null,
            }
        }
    },
})
</script>


<style scoped>

</style>
