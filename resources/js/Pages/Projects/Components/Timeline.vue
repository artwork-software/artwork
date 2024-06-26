<template>
    <div class="w-[175px]">
        <div class="h-9 bg-gray-800/60 flex items-center px-4 rounded-lg mb-0.5"  @click="showAddTimeLineModal = true">
            <div class="uppercase text-white text-xs">
                {{ $t('Timeline') }}
            </div>
        </div>

        <div>
            <div v-if="timeLine?.length === 0 || timeLine === null" class="text-xs bg-gray-900 p-2 text-white my-1 cursor-pointer" @click="showAddTimeLineModal = true">
                <p class="text-xs">
                    {{ $t('Click here to add a timeline') }}
                </p>
            </div>
            <template v-for="(time) in timeLine">
                <div :id="'timeline-container-' + event.id + '-' + time.id"
                     @click="showAddTimeLineModal = true"
                     class="flex flex-col relative"
                     v-if="time.start !== null && time.end !== null">
                    <div class="text-xs bg-gray-900 p-2 text-white h-full rounded-lg">
                        <p v-if="time.start_date === time.end_date">
                            {{ time.formatted_dates.start_date }} {{ time.start }} - {{ time.end }}
                        </p>
                        <p v-else>
                            {{ time.formatted_dates.start_date }} {{ time.start }} - {{ time.formatted_dates.end_date }} {{ time.end }}
                        </p>
                        <p class="text-xs" v-html="time.description"></p>
                    </div>
                </div>
            </template>
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

export default defineComponent({
    name: "Timeline",
    computed: {
        dayjs() {
            return dayjs;
        }
    },
    components: {
        AddTimeLineModal
    },
    props: [
        'timeLine',
        'event'
    ],
    emits: [
        'wantsFreshPlacements'
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
        }
    }
});
</script>
