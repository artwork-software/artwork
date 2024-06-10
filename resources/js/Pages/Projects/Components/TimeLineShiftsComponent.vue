<template>
    <div :id="'event-container-inner-' + event.id" class="flex flex-row items-start gap-2">
        <Timeline :time-line="timeLine"
                  :event="event"
                  @wantsFreshPlacements="this.reinitializeEventContainerPlacements()"
        />
        <template v-for="shift in event.shifts">
            <SingleShift @dropFeedback="dropFeedback"
                         @wantsFreshPlacements="this.reinitializeEventContainerPlacements()"
                         :shift="shift"
                         :crafts="crafts"
                         :event="event"
                         :currentUserCrafts="currentUserCrafts"
                         :shift-qualifications="shiftQualifications"
                         :shift-time-presets="shiftTimePresets"/>
        </template>

        <!-- Empty -->
        <div class="w-[175px] h-[144px] rounded-lg flex items-center justify-center border-2 border-dashed" @click="checkWhichModal">
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
                   :shift-time-presets="shiftTimePresets"
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
import IconLib from "@/Mixins/IconLib.vue";
import ShiftPlanPlacementHandler from "@/Helper/ShiftPlanPlacementHandler.vue";

export default defineComponent({
    name: "TimeLineShiftsComponent",
    props: [
        'timeLine',
        'shifts',
        'event',
        'crafts',
        'currentUserCrafts',
        'shiftQualifications',
        'shiftTimePresets'
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
                cameFormBuffer: false
            },
            elementsHeightInPixelsPerMinute: 0.75, // 200 Pixel / (4 * 60 Minuten),
            elementsHeaderHeight: 36
        }
    },
    mixins: [IconLib],
    emits: ['dropFeedback'],
    mounted() {
        this.getPlacementHandler().initialize();
    },
    methods: {
        getPlacementHandler() {
            return new ShiftPlanPlacementHandler(
                this.event.id,
                this.shifts.concat(this.timeLine),
                'event-container-inner-',
                'timeline-container-',
                'shift-container-',
                this.elementsHeightInPixelsPerMinute,
                this.elementsHeaderHeight
            );
        },
        reinitializeEventContainerPlacements() {
            this.getPlacementHandler().reinitialize();
        },
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
            this.buffer = buffer;
            this.showChooseShiftSeriesModal = false;
            this.showAddShiftModal = true;
        },
        closeAddShiftModal(){
            this.showAddShiftModal = false;
            this.buffer = {
                onlyThisDay: false,
                start: null,
                end: null,
            };

            this.reinitializeEventContainerPlacements();
        }
    },
})
</script>
