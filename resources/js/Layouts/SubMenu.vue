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
                                    <component is="IconX" class="size-6 text-white" aria-hidden="true" />
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
                                                    {{ $t(item.name) }}
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
        <div class="flex grow flex-col gap-y-5 overflow-y-auto overflow-x-auto bg-artwork-navigation-background">
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
                            <img :src="usePage().props.small_logo" :class="isFullSideBar ? 'h-12 w-12 min-w-12 min-h-12' : 'h-12 w-12 min-w-12 min-h-12'" class="object-cover" alt="artwork-logo"/>
                        </div>
                    </div>
                    <div v-if="isFullSideBar" class="ml-4">
                        <img :src="usePage().props.big_logo" :class="isFullSideBar ? 'h-12 w-auto' : 'h-16 w-16'" alt="artwork-logo"/>
                    </div>
                </div>
            </div>
            <nav class="flex flex-1 flex-col px-6">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                        <ul role="list" class="-mx-3 space-y-3">
                            <li v-for="item in navigation" :key="item.name">
                                <div @mouseover="showToolTipForItem(item)" @mouseleave="hideToolTipForItem(item)">
                                    <Link v-if="!item.isMenu && item.has_permission" :href="item.href" :class="[item.current ? 'bg-gray-50/10 text-white' : 'text-white hover:bg-gray-50/10 hover:text-artwork-buttons-hover', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                                        <component :stroke-width="item.current ? '1.5' : '1'" :is="item.icon" :class="[item.current ? 'text-white' : 'text-white group-hover:text-artwork-buttons-hover', 'size-6 min-w-6 min-h-6 shrink-0']" aria-hidden="true" />
                                        <span v-if="isFullSideBar">{{ $t(item.name) }}</span>
                                    </Link>
                                    <div v-else>
                                        <div v-if="item.has_permission" class="hover:bg-gray-50/10 hover:text-white  group flex gap-x-3 rounded-md text-sm/6 font-semibold p-2">
                                            <BaseMenu text-with-margin-left white-menu-background menu-width="!w-fit"  :menu-button-text="item.name" :show-menu-button-text="isFullSideBar" no-relative tooltip-direction="right" has-no-offset show-custom-icon :icon="item.icon" white-icon dots-size="w-6 h-6 min-h-6 min-w-6">
                                                <div v-for="subMenu in item.subMenus" :key="subMenu.name">
                                                    <BaseMenuItem white-menu-background as-link v-if="subMenu.has_permission" :href="subMenu.href" :icon="subMenu.icon" :title="subMenu.name" />
                                                </div>
                                            </BaseMenu>
                                        </div>
                                    </div>
                                </div>
                                <div class="absolute left-14" :class="item.showToolTipForItem ? 'block' : 'hidden'">
                                    <div class="p-2 text-xs leading-tight text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit font-lexend">
                                        {{ $t(item.name) }}
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                    <li class="mt-auto">
                        <ul role="list" class="-mx-3 space-y-2">
                            <li v-for="item in subNavigation" :key="item.name">
                                <div @mouseover="showToolTipForItem(item)" @mouseleave="hideToolTipForItem(item)">
                                    <Link v-if="!item.isMenu" :href="item.href" :class="[item.current ? 'bg-gray-50/10 text-white' : 'text-white hover:bg-gray-50/10 hover:text-artwork-buttons-hover', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                                        <component :stroke-width="item.current ? '1.5' : '1'" :is="item.icon" :class="[item.current ? 'text-white' : 'text-white group-hover:text-artwork-buttons-hover', 'size-6 min-w-6 min-h-6 shrink-0']" aria-hidden="true" />
                                        <span v-if="isFullSideBar">{{ $t(item.name) }}</span>
                                    </Link>
                                    <div v-else class="hover:bg-gray-50/10 hover:text-white  group flex gap-x-3 rounded-md text-sm/6 font-semibold p-2">
                                        <BaseMenu :no-tooltip="true" menu-width="!w-fit" white-menu-background :menu-button-text="item.name" :show-menu-button-text="isFullSideBar" no-relative tooltip-direction="right" has-no-offset show-custom-icon :icon="item.icon" white-icon dots-size="w-6 h-6 min-h-6 min-w-6">
                                            <div v-for="subMenu in item.subMenus" :key="subMenu.name">
                                                <BaseMenuItem white-menu-background as-link :href="subMenu.href" :icon="subMenu.icon" :title="subMenu.name" />
                                            </div>
                                        </BaseMenu>
                                    </div>
                                </div>
                                <div class="absolute left-14" :class="item.showToolTipForItem ? 'block' : 'hidden'">
                                    <div class="p-2 text-xs text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit font-lexend">
                                        {{ $t(item.name) }}
                                    </div>
                                </div>
                            </li>
                            <li>
                                <Popover class="ml-1">
                                    <Float auto-placement portal :offset="{ mainAxis: 150, crossAxis: 250}">
                                        <PopoverButton>
                                            <div class="flex items-center gap-x-3 py-3 text-sm/6 font-semibold text-white ">
                                                <img class="size-8 min-w-8 min-h-8 rounded-full object-cover bg-gray-50" :src="usePage().props.auth.user.profile_photo_url" alt="" />
                                                <span v-if="isFullSideBar">
                                                    <span class="sr-only">Your profile</span>
                                                    <span aria-hidden="true">{{ usePage().props.auth.user.full_name }}</span>
                                                </span>
                                            </div>
                                        </PopoverButton>

                                        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                                            <PopoverPanel class="absolute left-1/2 z-10 flex w-screen max-w-max card glassy -translate-x-1/2 p-3">
                                                <div class="w-screen max-w-md flex-auto overflow-hidden card white p-3">
                                                    <div class="flex w-full items-center justify-between space-x-6 p-2">
                                                        <div class="flex-1 truncate ">
                                                            <div class="flex items-center space-x-3">
                                                                <div class="font-bold headline h2">{{ usePage().props.auth.user.full_name }}</div>
                                                                <span class="inline-flex shrink-0 items-center rounded-full bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset"> {{ usePage().props.auth.user.position }}</span>
                                                            </div>
                                                            <p class="mt-1 truncate text-sm text-gray-500">{{ usePage().props.auth.user.business }}</p>
                                                        </div>
                                                        <img class="size-14 shrink-0 rounded-full object-cover bg-gray-300" :src="usePage().props.auth.user.profile_photo_url" alt="" />
                                                    </div>
                                                    <div>
                                                        <div class="py-2 divide-x divide-gray-200 divide-dashed border-t border-gray-200 border-dashed flex items-center justify-between">
                                                            <div class="flex w-0 flex-1">
                                                                <div @click="logout" class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent text-sm font-semibold text-gray-900 group hover:text-red-500 transition ease-in-out duration-200 cursor-pointer">
                                                                    <component is="IconLogout" class="size-5 text-gray-400 group-hover:text-red-500 transition ease-in-out duration-200" aria-hidden="true" />
                                                                    {{ $t('Logout') }}
                                                                </div>
                                                            </div>
                                                            <Link :href="route('user.edit.info', {user: usePage().props.auth.user.id})" class="-ml-px flex w-0 flex-1">
                                                                <div class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent text-sm font-semibold text-gray-900 group hover:text-artwork-buttons-create transition ease-in-out duration-200 cursor-pointer">
                                                                    <component is="IconUserCircle" class="size-5 text-gray-400 group-hover:text-artwork-buttons-create transition ease-in-out duration-200" aria-hidden="true" />
                                                                    {{ $t('Your account') }}
                                                                </div>
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </div>
                                            </PopoverPanel>
                                        </transition>
                                    </Float>
                                </Popover>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="sticky top-0 z-40 flex items-center gap-x-6 bg-white px-4 py-4 shadow-xs sm:px-6 lg:hidden">
        <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
            <span class="sr-only">Open sidebar</span>
            <component is="IconMenu2" class="size-6" aria-hidden="true" />
        </button>
        <div class="flex-1 text-sm/6 font-semibold text-gray-900">Dashboard</div>
        <a href="#">
            <span class="sr-only">Your profile</span>
            <img class="size-8 rounded-full object-cover bg-gray-50" :src="usePage().props.auth.user.profile_photo_url" alt="" />
        </a>
    </div>
</template>

<script setup>

import {computed, ref} from "vue";
import {usePage, Link, router} from "@inertiajs/vue3";
import {
    Dialog,
    DialogPanel,
    Popover,
    PopoverButton,
    PopoverPanel,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import { is, can } from 'laravel-permission-to-vuejs'
import {Float} from "@headlessui-float/vue";
import {useI18n} from "vue-i18n";
const { locale } = useI18n();

const props = defineProps({})
const computedBudgetRoute = computed(() => {
    let desiredBudgetRoute = route('budget-settings.general');
    if (can('view budget templates') && can('can manage global project budgets | can manage all project budgets without docs')
    ) {
        desiredBudgetRoute = route('budget-settings.templates');
    }

    return desiredBudgetRoute
})

const moduleIsVisible = (module) => {
    return is('artwork admin') || usePage().props.module_settings[module];
}


const navigation = ref([
    {
        name: 'Dashboard',
        href: route('dashboard'),
        icon: 'IconHome',
        current: route().current('dashboard'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
    {
        name: 'Projects',
        href: route('projects'),
        icon: 'IconGeometry',
        current: route().current('projects'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('projects'),
    },
    {
        name: 'Calendar',
        href: '#',
        icon: 'IconCalendarClock',
        current: true,
        isMenu: true,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('inventory'),
        subMenus: [
            {
                name: 'Calendar',
                href: route('events'),
                icon: 'IconCalendarClock',
                current: route().current('events'),
                has_permission: moduleIsVisible('room_assignment')
            },
            {
                name: 'Planning Calendar',
                href: route('planning-event-calendar.index'),
                icon: 'IconCalendarCog',
                current: route().current('planning-event-calendar.index'),
                has_permission: can('can see planning calendar') || is('artwork admin'),
            },
            {
                name: 'Event Verifications',
                href: route('event-verifications.index'),
                icon: 'IconCalendarCheck',
                current: route().current('event-verifications.index'),
                has_permission: can('can see planning calendar | can edit planning calendar') || is('artwork admin'),
            },
        ]
    },
    {
        name: 'Shift plan',
        href: '#',
        icon: 'IconCalendarUser',
        current: true,
        isMenu: true,
        showToolTipForItem: false,
        has_permission: can('can view shift plan') || moduleIsVisible('shift_plan') || is('artwork admin'),
        subMenus: [
            {
                name: 'Duty rosters',
                href: route('shifts.plan'),
                icon: 'IconCalendarUser',
                current: route().current('shifts.plan'),
                has_permission: can('can view shift plan') || moduleIsVisible('shift_plan') || is('artwork admin'),
            },
            {
                name: 'My Operational plan',
                href: route('user.operationPlan', usePage().props.auth.user.id),
                icon: 'IconCalendarUser',
                current: route().current('user.operationPlan'),
                has_permission: can('can view shift plan') || moduleIsVisible('shift_plan') || is('artwork admin'),
            },
            {
                name: 'Shift templates',
                href: route('shifts.presets'),
                icon: 'IconCalendarTime',
                current: route().current('shifts.presets') || route().current('shifts.timeline-presets.index'),
                has_permission: can('can view shift plan') || moduleIsVisible('shift_plan') || is('artwork admin'),
            },
            {
                name: 'Work time change requests',
                href: route('work-time-request.index'),
                icon: 'IconTimelineEventPlus',
                current: route().current('work-time-request.index'),
                has_permission: can('can view shift plan') || moduleIsVisible('shift_plan') || is('artwork admin'),
            },
            {
                name: 'Shift commitment requests',
                href: route('shifts.commit-requests.index'),
                icon: 'IconLockSquareRounded',
                current: route().current('shifts.commit-requests.index'),
                has_permission: usePage().props.isUserWorkFlowUser && usePage().props.shiftCommitWorkflow || is('artwork admin') && usePage().props.shiftCommitWorkflow,
            },
        ]
    },
    {
        name: 'Inventory System',
        href: '#',
        icon: 'IconBuildingWarehouse',
        current: true,
        isMenu: true,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('inventory'),
        subMenus: [
            {
                name: 'Inventory',
                href: route('inventory.index'),
                icon: 'IconBuildingWarehouse',
                current: route().current('inventory.index'),
                has_permission: moduleIsVisible('inventory')
            },
            {
                name: 'Article Planning',
                href: route('inventory-management.article.planning'),
                icon: 'IconCalendarExclamation',
                current: route().current('inventory-management.article.planning'),
                has_permission: is('artwork admin') || can('inventory.disposition'),
            },
            {
                name: 'Material Issues',
                href: route('issue-of-material.index'),
                icon: 'IconBrowserShare',
                current: route().current('issue-of-material.index'),
                has_permission: is('artwork admin') || can('inventory.disposition'),
            },
        ]
    },
    {
        name: 'To-dos',
        href: route('tasks.own'),
        icon: 'IconListCheck',
        current: route().current('tasks.own'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('tasks'),
    },
    {
        name: 'Sources of funding',
        href: route('money_sources.index'),
        icon: 'IconCurrencyEuro',
        current: route().current('money_sources.index'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: can('view edit add money_sources | can edit and delete money sources') || moduleIsVisible('sources_of_funding'),
    },
    {
        name: 'Users',
        href: route('users'),
        icon: 'IconUsers',
        current: route().current('users'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('users'),
    },
    {
        name: 'Contracts',
        href: route('contracts.index'),
        icon: 'IconFileText',
        current: route().current('contracts.index'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: can('view edit upload contracts | can see and download contract modules') || moduleIsVisible('contracts'),
    },
    {
        name: 'System',
        href: '#',
        icon: 'IconSettings',
        current: true,
        isMenu: true,
        showToolTipForItem: false,
        has_permission: can('change tool settings | create, delete and update rooms | change project settings | change event settings | admin checklistTemplates | set.create_edit | set.delete') || is('artwork admin'),
        subMenus: [
            {
                name: 'Tool Settings',
                href: route('tool.branding'),
                icon: 'IconSettings',
                current: route().current('tool.branding'),
                has_permission: can('change tool settings') || is('artwork admin')
            },
            {
                name: 'Shift settings',
                href: route('shift.settings'),
                icon: 'IconCalendarUser',
                current: route().current('shift.settings'),
                has_permission: is('artwork admin')
            },
            {
                name: 'Inventory',
                href: route('inventory-management.settings.category'),
                icon: 'IconBuildingWarehouse',
                current: route().current('inventory-management.settings.category'),
                has_permission: is('artwork admin')
            },
            {
                name: 'Material Sets',
                href: route('material-sets.index'),
                icon: 'IconParentheses',
                current: route().current('material-sets.index'),
                has_permission: is('artwork admin') || can('set.create_edit | set.delete')
            },
            {
                name: 'Rooms',
                href: route('areas.management'),
                icon: 'IconDoor',
                current: route().current('areas.management'),
                has_permission: can('create, delete and update rooms') || is('artwork admin')
            },
            {
                name: 'Projects',
                href: route('project.settings'),
                icon: 'IconGeometry',
                current: route().current('project.settings'),
                has_permission: can('change project settings') || is('artwork admin')
            },
            {
                name: 'Calendar',
                href: route('calendar.settings'),
                icon: 'IconCalendarMonth',
                current: route().current('calendar.settings'),
                has_permission: is('artwork admin')
            },
            {
                name: 'Events',
                href: route('event_types.management'),
                icon: 'IconTicket',
                current: route().current('event_types.management'),
                has_permission: can('change event settings') || is('artwork admin')
            },
            {
                name: 'Checklists',
                href: route('checklist_templates.management'),
                icon: 'IconListCheck',
                current: route().current('checklist_templates.management'),
                has_permission: can('admin checklistTemplates') || is('artwork admin')
            },
            {
                name: 'Sources of funding',
                href: route('money_sources.settings'),
                icon: 'IconCurrencyEuro',
                current: route().current('money_sources.settings'),
                has_permission: is('artwork admin')
            },
            {
                name: 'Budget',
                href: computedBudgetRoute,
                icon: 'IconMoneybag',
                current: route().current('tool.branding'),
                has_permission: is('artwork admin')
            },
        ]
    },
    {
        name: 'Recycle bin',
        href: route('projects.trashed'),
        icon: 'IconTrash',
        current: route().current('projects.trashed'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: is('artwork admin'),
    },
])

const subNavigation = ref([
    {
        name: 'Notifications',
        href: route('notifications.index'),
        icon: 'IconBell',
        current: route().current('notifications.*'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: true,
    },
]);

const sidebarOpen = ref(false)
const isFullSideBar = ref(false)

const logout = () => {
    document.documentElement.lang = usePage().props.default_language
    locale.value = usePage().props.default_language
    document.documentElement.setAttribute('lang', usePage().props.default_language)
    router.post(route('logout'), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {

        }
    })
}

const showToolTipForItem = (item) => {
    if(!isFullSideBar.value){
        item.showToolTipForItem = true
    }
}

const hideToolTipForItem = (item) => {
    item.showToolTipForItem = false
}




</script>

<style scoped>

</style>
