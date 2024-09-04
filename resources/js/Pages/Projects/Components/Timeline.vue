<template>
    <div class="w-72">
        <div class="h-9 bg-gray-800/60 flex items-center px-4 rounded-lg mb-0.5">
            <div class="uppercase text-white text-xs">
                {{ $t('Timeline') }}
            </div>
        </div>

        <div>
            <div v-if="(timeLine?.length === 0 || timeLine === null) && (this.$can('can plan shifts') || this.hasAdminRole())" class="text-xs bg-gray-900 p-2 text-white my-1 cursor-pointer hidden" @click="showAddTimeLineModal = true">
                <p class="text-xs">
                    {{ $t('Click here to add a timeline') }}
                </p>
            </div>

            <template v-for="(time) in timeLine">
                <NewSingleTimeline :time="time" :event="event" @wantsFreshPlacements="this.$emit('wantsFreshPlacements')"/>
            </template>

            <div>
                <div class="flex items-center justify-center mt-1 py-2 rounded-lg cursor-pointer border-2 border-dashed group" @click="addEmptyTimeline">
                    <IconCirclePlus class="h-6 w-6 text-artwork-buttons-context group-hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out" stroke-width="2" />
                </div>
            </div>
        </div>
    </div>

    <AddTimeLineModal v-if="showAddTimeLineModal"
                      :event="event"
                      :timeLine="timeLine"
                      @closed="this.closeModal()"/>
</template>
<script>
import {defineComponent} from 'vue'
import AddTimeLineModal from "@/Pages/Projects/Components/AddTimeLineModal.vue";
import dayjs from "dayjs";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {router} from "@inertiajs/vue3";
import NewSingleTimeline from "@/Pages/Projects/Components/TimelineComponents/NewSingleTimeline.vue";

export default defineComponent({
    name: "Timeline",
    computed: {
        dayjs() {
            return dayjs;
        }
    },
    components: {
        NewSingleTimeline,
        AddTimeLineModal
    },
    props: [
        'timeLine',
        'event'
    ],
    emits: [
        'wantsFreshPlacements'
    ],
    mixins: [
        Permissions,
        IconLib
    ],
    data(){
        return {
            showAddTimeLineModal: false
        }
    },
    methods: {
        closeModal() {
            this.$emit('wantsFreshPlacements');
            this.showAddTimeLineModal = false;
        },
        openTimelineModal() {
            if(this.$can('can plan shifts') || this.hasAdminRole()) {
                this.showAddTimeLineModal = true;
            }
        },
        addEmptyTimeline(){
            router.post(route('add.timeline.row', {event: this.event.id}), {
            }, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    this.$emit('wantsFreshPlacements');
                }
            })
        }
    }
});
</script>
