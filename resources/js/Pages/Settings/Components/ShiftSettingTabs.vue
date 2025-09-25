<script setup>

import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation();

const tabs = [
    {
        name: $t('Shift Settings'),
        href: route('shift.settings'),
        current: route().current('shift.settings'),
        show: true,
        icon: 'IconCalendarUser',
        permission: true
    },
    {
        name: $t('Day Services'),
        href: route('day-service.index'),
        current: route().current('day-service.index'),
        show: true,
        icon: 'IconHours24',
        permission: true
    },
    {
        name: $t('Work Time Pattern'),
        href: route('shift.work-time-pattern'),
        current: route().current('shift.work-time-pattern'),
        show: true,
        icon: 'IconClockCog',
        permission: true
    },
    {
        name: $t('User Contracts'),
        href: route('user-contract-settings.index'),
        current: route().current('user-contract-settings.index'),
        show: true,
        icon: 'IconContract',
        permission: true
    },
    {
        name: $t('shift templates'),
        href: route('single-shift-presets.index'),
        current: route().current('single-shift-presets.index'),
        show: true,
        icon: 'IconContract',
        permission: true
    }
];
</script>

<template>
    <div class="my-4">
        <BaseTabs :tabs="tabs" />
    </div>
</template>

<style scoped>

</style>
