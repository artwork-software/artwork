<template>
    <TransitionRoot as="template" :show="sidebarOpen">
        <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
            <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-900/80" />
            </TransitionChild>

            <div class="fixed inset-0 flex">
                <TransitionChild as="template" enter="transition ease-in-out duration-300 transform" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0" leave-to="-translate-x-full">
                    <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                        <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
                            <div class="absolute top-0 left-full flex w-16 justify-center pt-5">
                                <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                    <span class="sr-only">Close sidebar</span>
                                    <XMarkIcon class="size-6 text-white" aria-hidden="true" />
                                </button>
                            </div>
                        </TransitionChild>
                        <!-- Sidebar component, swap this element with another sidebar if you like -->
                        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2">
                            <div class="flex h-16 shrink-0 items-center">
                                <img class="h-8 w-auto" src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600" alt="Your Company" />
                            </div>
                            <nav class="flex flex-1 flex-col">
                                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                    <li>
                                        <ul role="list" class="-mx-2 space-y-1">
                                            <li v-for="item in navigation" :key="item.name">
                                                <a :href="item.href" :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                                                    <component :is="item.icon" :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'size-6 shrink-0']" aria-hidden="true" />
                                                    {{ item.name }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li>
                                        <div class="text-xs/6 font-semibold text-gray-400">Your teams</div>
                                        <ul role="list" class="-mx-2 mt-2 space-y-1">
                                            <li v-for="team in teams" :key="team.name">
                                                <a :href="team.href" :class="[team.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                                                    <span :class="[team.current ? 'border-indigo-600 text-indigo-600' : 'border-gray-200 text-gray-400 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex size-6 shrink-0 items-center justify-center rounded-lg border bg-white text-[0.625rem] font-medium']">{{ team.initial }}</span>
                                                    <span class="truncate">{{ team.name }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- Static sidebar for desktop -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col" :class="isFullSideBar ? 'lg:w-72' : 'lg:w-16'">
        <!-- Sidebar component, swap this element with another sidebar if you like -->
        <div class="flex grow flex-col gap-y-5 overflow-y-auto overflow-x-auto relative border-r border-gray-200 bg-artwork-navigation-background">
            <div class="flex h-16 shrink-0 items-center justify-center">
                <div :class="isFullSideBar ? 'w-full flex mx-6' : ''" class="mt-5">
                    <div class="group relative">
                        <div class="cursor-pointer absolute group-hover:block hidden bg-artwork-navigation-background/70 z-10 h-full w-full" @click="isFullSideBar = !isFullSideBar">
                            <div class="flex items-center justify-center h-full w-full">
                                <component is="IconChevronsRight" v-if="!isFullSideBar" class="h-6 w-6 text-white" aria-hidden="true"/>
                                <component is="IconChevronsLeft" v-else class="h-6 w-6 text-white" aria-hidden="true"/>
                            </div>
                        </div>
                        <div class="font-bold text-secondaryHover block">
                            <img :src="usePage().props.small_logo" :class="isFullSideBar ? 'h-12 w-12' : 'h-12 w-12'" class="object-cover" alt="artwork-logo"/>
                        </div>
                    </div>
                    <div v-if="isFullSideBar" class="ml-4">
                        <img :src="usePage().props.big_logo" :class="isFullSideBar ? 'h-12 w-12' : 'h-16 w-16'" alt="artwork-logo"/>
                    </div>
                </div>
            </div>
            <nav class="flex flex-1 flex-col px-6">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                        <ul role="list" class="-mx-3 space-y-1">
                            <li v-for="item in navigation" :key="item.name">
                                <a v-if="!item.isMenu" :href="item.href" :class="[item.current ? 'bg-gray-50/10 text-white' : 'text-white hover:bg-gray-50/10 hover:text-artwork-buttons-hover', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                                    <component :stroke-width="item.current ? '1.5' : '1'" :is="item.icon" :class="[item.current ? 'text-white' : 'text-white group-hover:text-artwork-buttons-hover', 'size-6 shrink-0']" aria-hidden="true" />
                                    <span v-if="isFullSideBar">{{ item.name }}</span>
                                </a>
                                <div v-else class="hover:bg-gray-50/10 hover:text-white  group flex gap-x-3 rounded-md text-sm/6 font-semibold p-2 relative">
                                    <BaseMenu no-relative tooltip-direction="right" has-no-offset show-custom-icon :icon="item.icon" white-icon dots-size="w-6 h-6 min-h-6 min-w-6">
                                        <div v-for="subMenu in item.subMenus" :key="subMenu.name">
                                            <BaseMenuItem as-link :href="subMenu.href" :icon="subMenu.icon" :title="subMenu.name" class="!text-white" />
                                        </div>
                                    </BaseMenu>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <li class="-mx-2 mt-auto">
                        <a href="#" class="flex items-center py-3 text-sm/6 font-semibold text-gray-900 ">
                            <img class="size-8 min-w-8 min-h-8 rounded-full object-cover bg-gray-50" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" />
                            <span v-if="isFullSideBar">
                                <span class="sr-only">Your profile</span>
                                <span aria-hidden="true">Tom Cook</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="sticky top-0 z-40 flex items-center gap-x-6 bg-white px-4 py-4 shadow-xs sm:px-6 lg:hidden">
        <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
            <span class="sr-only">Open sidebar</span>
            <Bars3Icon class="size-6" aria-hidden="true" />
        </button>
        <div class="flex-1 text-sm/6 font-semibold text-gray-900">Dashboard</div>
        <a href="#">
            <span class="sr-only">Your profile</span>
            <img class="size-8 rounded-full bg-gray-50" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" />
        </a>
    </div>
</template>

<script setup>

import {IconHome, IconUsers, IconSettings, IconBell, IconCurrencyEuro, IconListCheck, IconFileText, IconBuildingWarehouse, IconCalendarCheck, IconGeometry, IconCalendarMonth, IconCalendarUser, IconCalendarCog} from "@tabler/icons-vue";
import {ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import {Dialog, DialogPanel, TransitionChild, TransitionRoot} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({})

const navigation = [
    {
        name: 'Dashboard',
        href: route('dashboard'),
        icon: IconHome,
        current: route().current('dashboard'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Projects',
        href: route('projects'),
        icon: IconGeometry,
        current: route().current('projects'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Calendar',
        href: route('events'),
        icon: IconCalendarMonth,
        current: route().current('events'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Shift plan',
        href: route('shifts.plan'),
        icon: IconCalendarUser,
        current: route().current('shifts.plan'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Planning Calendar',
        href: route('planning-event-calendar.index'),
        icon: IconCalendarCog,
        current: route().current('planning-event-calendar.index'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Event Verifications',
        href: route('event-verifications.index'),
        icon: IconCalendarCheck,
        current: route().current('event-verifications.index'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Inventory',
        href: route('inventory-management.inventory'),
        icon: IconBuildingWarehouse,
        current: route().current('inventory-management.inventory'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'To-dos',
        href: route('tasks.own'),
        icon: IconListCheck,
        current: route().current('tasks.own'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Sources of funding',
        href: route('money_sources.index'),
        icon: IconCurrencyEuro,
        current: route().current('money_sources.index'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Users',
        href: route('users'),
        icon: IconUsers,
        current: route().current('users'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Contracts',
        href: route('contracts.index'),
        icon: IconFileText,
        current: route().current('contracts.index'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Dashboard',
        href: '#',
        icon: IconSettings,
        current: true,
        isMenu: true,
        showToolTipForItem: false,
        has_permission: true,
        subMenus: [
            { name: 'Tool Settings', href: '#', icon: IconHome, current: false },
            { name: 'Shift settings', href: '#', icon: IconHome, current: false },
            { name: 'Manufacturers', href: '#', icon: IconHome, current: false },
            { name: 'Inventory', href: '#', icon: IconHome, current: false },
            { name: 'Rooms', href: '#', icon: IconHome, current: false },
            { name: 'Projects', href: '#', icon: IconHome, current: false },
            { name: 'Calendar', href: '#', icon: IconHome, current: false },
            { name: 'Events', href: '#', icon: IconHome, current: false },
            { name: 'Checklists', href: '#', icon: IconHome, current: false },
            { name: 'Sources of funding', href: '#', icon: IconHome, current: false },
            { name: 'Budget', href: '#', icon: IconHome, current: false },
        ]
    },
    {
        name: 'Notifications',
        href: route('notifications.index'),
        icon: IconBell,
        current: route().current('notifications.*'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
]

const sidebarOpen = ref(false)
const isFullSideBar = ref(false)
</script>

<style scoped>

</style>