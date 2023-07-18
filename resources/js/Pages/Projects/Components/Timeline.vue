<template>
    <div class="w-[175px] ">
        <div class="h-9 bg-gray-800/60 flex items-center px-4"  @click="showAddTimeLineModal = true">
            <div class="uppercase text-white text-xs">
                TIMELINE
            </div>
        </div>


        <div class="mt-1">
            <div v-if="timeLine.length === 0" class="text-xs bg-gray-900 p-2 text-white my-1 cursor-pointer" @click="showAddTimeLineModal = true">
                <p class="text-xs">
                    Hier klicken um eine Timeline hinzuzufügen
                </p>
            </div>
            <div v-for="(time, index) in timeLine">
                <div @click="showAddTimeLineModal = true" class="text-xs bg-gray-900 p-2 text-white my-1"  v-if="time.start !== null && time.end !== null">
                    {{ time.start }} - {{ time.end }}
                    <p class="text-xs">{{ time.description }}</p>
                </div>
            </div>
        </div>
    </div>

    <AddTimeLineModal v-if="showAddTimeLineModal" :event="event" :timeLine="timeLine" @closed="showAddTimeLineModal = false"/>
</template>
<script>
import {defineComponent} from 'vue'
import AddTimeLineModal from "@/Pages/Projects/Components/AddTimeLineModal.vue";
import dayjs from "dayjs";

export default defineComponent({
    name: "Timeline",
    computed: {
        dayjs() {
            return dayjs
        }
    },
    components: {AddTimeLineModal},
    props: ['timeLine', 'event'],
    data(){
        return {
            showAddTimeLineModal: false
        }
    },
    methods: {
        checkIfEventAndTimelineTheSame(){
            return dayjs(this.timeLine.start).format('HH:MM:SS') === dayjs(this.event.start_time).format('HH:MM:SS');
        }
    }
})
</script>


<style scoped>

</style>
