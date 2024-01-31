<template>
    <app-layout>
        <div class="ml-9">
           <div class="px-5" v-if="showHeadLine">
               <div class="flex items-center justify-between ">
                   <h2 class="headline1">Dienstpläne & Schichtvorlagen</h2>
                   <div @click="showHeadLine = !showHeadLine" class="flex items-center gap-x-1.5 cursor-pointer">
                       <p class="text-xs"> Menu ausblenden</p>
                       <chevron-up-icon class="w-6 h-6 text-gray-500" v-if="showHeadLine"/>
                   </div>
               </div>

               <ShiftTabs />
           </div>
            <div v-else class="px-5">
                <div class="flex items-center justify-end ">
                    <div @click="showHeadLine = !showHeadLine" class="flex items-center gap-x-1.5 cursor-pointer">
                        <p class="text-xs"> Menu einblenden</p>
                        <chevron-down-icon class="w-6 h-6 text-gray-500" v-if="!showHeadLine"/>
                    </div>
                </div>
            </div>
            <slot></slot>
        </div>



    </app-layout>
</template>
<script>
import {defineComponent} from 'vue'
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ChevronDownIcon, ChevronUpIcon } from "@heroicons/vue/solid";

export default defineComponent({
    name: "ShiftHeader",
    components: {AppLayout, ShiftTabs, ChevronDownIcon, ChevronUpIcon},
    data() {
        return {
            showHeadLine: true,
        }
    },
})
</script>


<style scoped>

</style>
