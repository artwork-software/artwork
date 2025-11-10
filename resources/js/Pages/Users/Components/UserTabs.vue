<template>
    <BaseTabs :tabs="tabs" />
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'
import { is, can } from 'laravel-permission-to-vuejs'

const tabs = computed(() => ([
    {
        name: 'Users',
        href: route('users'),
        current: route().current('users'),
        permission: true,
        icon: 'IconUsers',
    },
    {
        name: 'Freelancers & Service Providers',
        href: route('users.addresses'),
        current: route().current('users.addresses'),
        permission: true,
        icon: 'IconCompass',
    },
    {
        name: 'Manufacturers',
        href: route('manufacturers.index'),
        current: route().current('manufacturers.index'),
        permission: true,
        icon: 'IconBuildingWarehouse',
    },
    {
        name: 'Accommodations',
        href: route('accommodation.index'),
        current: route().current('accommodation.index'),
        permission: true,
        icon: 'IconHome',
    },
    {
        name: 'Artists',
        href: route('artist.index'),
        current: route().current('artist.index'),
        permission: true,
        icon: 'IconUsers',
    },
    {
        name: 'Teams',
        href: route('departments'),
        current: route().current('departments'),
        permission: can('teammanagement') || is('artwork admin'),
        icon: 'IconUsersGroup',
    },
    {
        name: 'Permission presets',
        href: route('permission-presets.index'),
        current: route().current('permission-presets.index'),
        permission: is('artwork admin'),
        icon: 'IconLicense',
    },
]))
</script>
