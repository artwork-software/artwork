<template>
    <div class="w-[175px] ">
        <div class="h-9 bg-gray-800/60 flex items-center px-4">
            <div class="uppercase text-white text-xs">
                TIMELINE
            </div>
        </div>


        <div class="mt-1">
            <div v-if="timeLine?.length === 0 || timeLine === null" class="text-xs bg-gray-900 p-2 text-white my-1 cursor-pointer" @click="showAddTimeLineModal = true">
                <p class="text-xs">
                    Hier klicken um eine Timeline hinzuzufügen
                </p>
            </div>
            <div v-for="(time, index) in timeLine">
                <div class="text-xs bg-gray-900 p-2 text-white my-1"  v-if="time.start !== null && time.end !== null" @click="showAddTimeLineModal = true">
                    {{ time.start }} - {{ time.end }}
                    <p class="text-xs">{{ time.description }}</p>
                </div>
            </div>
        </div>
    </div>

    <AddEditTimeLineModal v-if="showAddTimeLineModal" :preset-id="presetId" :timeLine="timeLine" @closed="showAddTimeLineModal = false"/>
</template>
<script>
import {defineComponent} from 'vue'
import AddEditTimeLineModal from "@/Pages/Shifts/Components/AddEditTimeLineModal.vue";

export default defineComponent({
    name: "PresetTimeLine",
    components: {AddEditTimeLineModal},
    props: ['timeLine', 'presetId'],
    data(){
        return {
            showAddTimeLineModal: false
        }
    }
})
</script>


<style scoped>

</style>
