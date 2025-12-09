<template>
    <TabComponent :tabs="tabs" use-translation />
</template>

<script setup>

import TabComponent from "@/Components/Tabs/TabComponent.vue";
import {ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";

const props = defineProps({})

const { hasAdminRole, can } = usePermission(usePage().props);

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
        show: hasAdminRole() || can('can plan shifts')
    },
]);
</script>

<style scoped>

</style>
