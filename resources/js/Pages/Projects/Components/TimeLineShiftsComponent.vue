<template>
    <div class="flex h-full gap-2">
        <Timeline :time-line="timeLine" :event="event"/>
        <div class="w-[175px]" v-for="shift in shifts">
            <SingleShift @dropFeedback="dropFeedback"
                         :shift="shift"
                         :crafts="crafts"
                         :event="event"
                         :currentUserCrafts="currentUserCrafts"
                         :shift-qualifications="shiftQualifications"
            />
        </div>
        <!-- Empty -->
        <div class="w-[175px] rounded-lg flex items-center justify-center border-2 border-dashed" @click="checkWhichModal">
            <IconCirclePlus class="h-6 w-6 rounded-full bg-artwork-buttons-create text-white p-0.5 hover:bg-artwork-buttons-hover cursor-pointer transition-all" />
        </div>
    </div>
    <AddShiftModal v-if="showAddShiftModal"
                   :crafts="crafts"
                   :event="event"
                   :currentUserCrafts="currentUserCrafts"
                   :buffer="buffer"
                   :shift-qualifications="shiftQualifications"
                   @closed="closeAddShiftModal"
    />
    <ChooseShiftSeries :event="event"
                       v-if="showChooseShiftSeriesModal"
                       @close-modal="showChooseShiftSeriesModal = false"
                       @returnBuffer="updateBuffer"
    />
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
import IconLib from "@/mixins/IconLib.vue";

export default defineComponent({
    name: "TimeLineShiftsComponent",
    props: [
        'timeLine',
        'shifts',
        'event',
        'crafts',
        'currentUserCrafts',
        'shiftQualifications'
    ],
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
    mixins: [IconLib],
    emits: ['dropFeedback'],
    methods: {
        dropFeedback(event){
            this.$emit('dropFeedback', event)
        },
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
