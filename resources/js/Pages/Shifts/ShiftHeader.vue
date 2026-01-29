<template>
    <app-layout :title="$t('Shift plan')">
        <div class="-ml-4">
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
