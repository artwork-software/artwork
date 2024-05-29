<template>
    <app-layout :title="$t('Duty rosters & shift templates')">
        <div class="ml-9">
           <div class="px-5" v-if="showHeadLine">
               <ShiftTabs />

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
