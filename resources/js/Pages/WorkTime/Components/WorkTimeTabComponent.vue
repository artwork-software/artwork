<template>
    <TabComponent :tabs="tabs" use-translation />
</template>

<script setup>

import TabComponent from "@/Components/Tabs/TabComponent.vue";
import {ref} from "vue";

const props = defineProps({})


const tabs = ref([
    {
        name: 'My work schedule adjustment requests',
        icon: 'IconGitPullRequestDraft',
        href: route('work-time-request.index'),
        current: route().current('work-time-request.index'),
        show: true
    },
    {
        name: 'Work time adjustment requests received',
        icon: 'IconGitPullRequest',
        href: route('work-time-request.received'),
        current: route().current('work-time-request.received'),
        show: true
    },
]);
</script>

<style scoped>

</style>