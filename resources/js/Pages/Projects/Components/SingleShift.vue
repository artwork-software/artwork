<template>
    <div class="flex h-full gap-2 items-stretch">
        <Timeline :time-line="timeLine" :event="event"/>
        <div class="w-[175px]">
            <div class="h-9 bg-amber-400 flex items-center px-4 text-white text-xs">
                LX (0 / 4)
            </div>
            <div class="mt-1 h-[calc(100%-2.7rem)] bg-gray-200 p-4">

            </div>
        </div>
        <!-- Empty -->
        <div class="w-[175px] flex items-center justify-center border-2 border-dashed">
            <PlusCircleIcon class="h-4 w-4 rounded-full bg-backgroundBlue" />
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'
import {PlusCircleIcon} from "@heroicons/vue/outline";
import Timeline from "@/Pages/Projects/Components/Timeline.vue";

export default defineComponent({
    name: "SingleShift",
    props: ['timeLine', 'shifts', 'event'],
    components: {
        Timeline,
        PlusCircleIcon
    }
})
</script>


<style scoped>

</style>
