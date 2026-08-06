<template>
    <app-layout :title="title ?? $t('Shift plan')">
        <div class="-ml-4">
            <slot></slot>
        </div>
    </app-layout>
</template>
<script>
import {IconChevronDown, IconChevronUp} from "@tabler/icons-vue";
import {defineComponent} from 'vue'
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

export default defineComponent({
    name: "ShiftHeader",
    components: {AppLayout, ShiftTabs, IconChevronDown, IconChevronUp},
    props: {
        title: {
            type: String,
            default: null,
        },
    },
    data() {
        return {
            showHeadLine: true,
        }
    },
})
</script>
