<template>
    <TabComponent :tabs="tabs" />
</template>

<script setup>

import TabComponent from "@/Components/Tabs/TabComponent.vue";
import {ref} from "vue";

const props = defineProps({})

const tabs = ref([
    { name: 'Shift templates', href: route('shifts.presets'), current: route().current('shifts.presets'), show: true },
    { name: 'Timeline Presets', href: route('shifts.timeline-presets.index'), current: route().current('shifts.timeline-presets.index'), show: true }
])

</script>

<style scoped>

</style>