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
                                    <component :is="IconX" class="size-6 text-white" aria-hidden="true" />
                                </button>
                            </div>
                        </TransitionChild>
                        <!-- Sidebar component, swap this element with another sidebar if you like -->
                        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2">
                            <div class="flex h-16 shrink-0 items-center">
                                <img class="h-8 w-auto" :src="usePage().props.big_logo" alt="artwork-logo" />
                            </div>
                            <nav class="flex flex-1 flex-col">
                                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                    <li>
                                        <ul role="list" class="-mx-2 space-y-1">
                                            <li v-for="item in navigation" :key="item.name">
                                                <div v-if="item.has_permission && (!item.isMenu || getVisibleSubMenus(item).length !== 0)">
                                                    <a
                                                        v-if="!item.isMenu"
                                                        :href="item.href"
                                                        :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']"
                                                    >
                                                        <PropertyIcon :name="item.icon" :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'size-6 shrink-0']" aria-hidden="true" />
                                                        {{ $t(item.name) }}
                                                    </a>

                                                    <a
                                                        v-else-if="getSingleVisibleSubMenu(item)"
                                                        :href="getSingleVisibleSubMenu(item).href"
                                                        :class="[(getSingleVisibleSubMenu(item).current) ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']"
                                                    >
                                                        <PropertyIcon :name="item.icon" :class="[(getSingleVisibleSubMenu(item).current) ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'size-6 shrink-0']" aria-hidden="true" />
                                                        {{ $t(item.name) }}
                                                    </a>

                                                    <div v-else>
                                                        <div class="text-gray-700 group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold">
                                                            <PropertyIcon :name="item.icon" class="text-gray-400 size-6 shrink-0" aria-hidden="true" />
                                                            {{ $t(item.name) }}
                                                        </div>
                                                        <ul class="ml-8 space-y-1">
                                                            <li v-for="subMenu in item.subMenus" :key="subMenu.name">
                                                                <a v-if="subMenu.has_permission" :href="subMenu.href" :class="[subMenu.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6']">
                                                                    <PropertyIcon :name="subMenu.icon" :class="[subMenu.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'size-5 shrink-0']" aria-hidden="true" />
                                                                    {{ $t(subMenu.name) }}
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
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
    <div
        class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col"
        :class="isFullSideBar ? 'lg:w-72' : 'lg:w-16'"
    >
        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-artwork-navigation-background">
            <!-- Brand -->
            <div class="flex h-16 shrink-0 items-center justify-center">
                <div :class="isFullSideBar ? 'w-full flex mx-6' : ''" class="mt-5">
                    <div class="group relative">
                        <div
                            class="absolute inset-0 hidden cursor-pointer bg-artwork-navigation-background/70 group-hover:block z-10"
                            @click="isFullSideBar = !isFullSideBar"
                        >
                            <div class="flex h-full w-full items-center justify-center">
                                <component :is="IconChevronsRight" v-if="!isFullSideBar" class="h-6 w-6 text-white" />
                                <component :is="IconChevronsLeft" v-else class="h-6 w-6 text-white" />
                            </div>
                        </div>
                        <img
                            :src="usePage().props.small_logo"
                            class="h-12 w-12 min-w-12 min-h-12 object-cover"
                            alt="artwork-logo"
                        />
                    </div>
                    <div v-if="isFullSideBar" class="ml-4">
                        <img :src="usePage().props.big_logo" class="h-12 w-auto" alt="artwork-logo" />
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav :class="['flex flex-1 flex-col', isFullSideBar ? 'px-6' : 'px-1']">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                        <!-- Haupt-Navigation -->
                        <ul role="list" :class="isFullSideBar ? 'space-y-2' : 'space-y-2'">
                            <li v-for="item in navigation" :key="item.name">
                                <!-- Link-Eintrag -->
                                <Link
                                    v-if="!item.isMenu && item.has_permission"
                                    :href="item.href"
                                    :prefetch="item.prefetch"
                                    :aria-current="item.current ? 'page' : undefined"
                                    :class="[
                                      'w-full group flex items-center rounded-lg h-10 select-none transition-colors',
                                      isFullSideBar ? 'justify-start gap-3 px-2' : 'justify-center px-0',
                                      item.current
                                        ? 'bg-white/10 text-white'
                                        : 'text-white hover:bg-white/10 hover:text-artwork-buttons-hover'
                                    ]"
                                >
                                    <ToolTipComponent
                                        v-if="!isFullSideBar"
                                        :icon="item.icon"
                                        :tooltip-text="$t(item.name)"
                                        direction="right"
                                        classes-button="flex items-center justify-center"
                                        white-icon
                                        icon-size="size-6"
                                    />
                                    <PropertyIcon
                                        v-else
                                        :name="item.icon"
                                        :stroke-width="1.5"
                                        class="size-6 min-w-6 min-h-6 text-white group-hover:text-artwork-buttons-hover"
                                        aria-hidden="true"
                                    />
                                    <span v-if="isFullSideBar" class="truncate">{{ $t(item.name) }}</span>
                                </Link>

                                <!-- Menü-Eintrag -->
                                <div v-else-if="item.has_permission && getVisibleSubMenus(item).length !== 0" class="w-full">
                                    <Link
                                        v-if="getSingleVisibleSubMenu(item)"
                                        :href="getSingleVisibleSubMenu(item).href"
                                        :prefetch="item.prefetch"
                                        :aria-current="getSingleVisibleSubMenu(item).current ? 'page' : undefined"
                                        :class="[
                                          'w-full group flex items-center rounded-lg h-10 select-none transition-colors',
                                          isFullSideBar ? 'justify-start gap-3 px-2' : 'justify-center px-0',
                                          getSingleVisibleSubMenu(item).current
                                            ? 'bg-white/10 text-white'
                                            : 'text-white hover:bg-white/10 hover:text-artwork-buttons-hover'
                                        ]"
                                    >
                                        <ToolTipComponent
                                            v-if="!isFullSideBar"
                                            :icon="item.icon"
                                            :tooltip-text="$t(item.name)"
                                            direction="right"
                                            classes-button="flex items-center justify-center"
                                            white-icon
                                            icon-size="size-6"
                                        />
                                        <PropertyIcon
                                            v-else
                                            :name="item.icon"
                                            :stroke-width="1.5"
                                            class="size-6 min-w-6 min-h-6 text-white group-hover:text-artwork-buttons-hover"
                                            aria-hidden="true"
                                        />
                                        <span v-if="isFullSideBar" class="truncate">{{ $t(item.name) }}</span>
                                    </Link>

                                    <div
                                        v-else
                                        :class="[
                                        'w-full group flex items-center rounded-lg h-10 select-none transition-colors',
                                        isFullSideBar ? 'justify-start gap-3 px-2' : 'justify-center px-0',
                                        item.current ? 'text-white hover:bg-white/10' : 'text-white hover:bg-white/10 hover:text-white'
                                      ]"
                                    >
                                        <BaseMenu
                                            stroke-width="1.5"
                                            text-with-margin-left
                                            :translation-key="item.name"
                                            :menu-button-text="item.name"
                                            :show-menu-button-text="isFullSideBar"
                                            no-relative
                                            tooltip-direction="right"
                                            has-no-offset
                                            show-custom-icon
                                            :icon="item.icon"
                                            white-icon
                                            dots-size="size-6"
                                            :classes-button="isFullSideBar
                      ? '!h-10 !px-0 flex items-center gap-3 text-current w-full justify-start'
                      : '!h-10 !px-0 flex items-center text-current w-full justify-center'"
                                        >
                                            <template v-for="subMenu in item.subMenus" :key="subMenu.name">
                                                <BaseMenuItem
                                                    v-if="subMenu.has_permission"
                                                    white-menu-background
                                                    as-link
                                                    :href="subMenu.href"
                                                    :icon="subMenu.icon"
                                                    :title="subMenu.name"
                                                />
                                            </template>
                                        </BaseMenu>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>

                    <!-- Footer-Navigation -->
                    <li class="mt-auto">
                        <ul role="list" class="space-y-1">
                            <li v-for="item in subNavigation" :key="item.name">
                                <Link
                                    v-if="!item.isMenu && item.has_permission"
                                    :href="item.href"
                                    :class="[
                                      'w-full group flex items-center rounded-lg h-10 select-none transition-colors',
                                      isFullSideBar ? 'justify-start gap-3 px-2' : 'justify-center px-0',
                                      item.current
                                        ? 'bg-gray-50/10 text-white'
                                        : 'text-white hover:bg-gray-50/10 hover:text-artwork-buttons-hover'
                                    ]"
                                >
                                    <PropertyIcon
                                        :name="item.icon"
                                        :stroke-width="1"
                                        class="size-6 min-w-6 min-h-6 text-white group-hover:text-artwork-buttons-hover"
                                    />
                                    <span v-if="isFullSideBar" class="truncate">{{ $t(item.name) }}</span>
                                </Link>

                                <!-- Falls subNavigation Einträge mit Menüs bekommt -->
                                <div v-else class="w-full">
                                    <div
                                        :class="[
                                        'w-full group flex items-center rounded-lg h-10 select-none transition-colors bg-transparent',
                                        isFullSideBar ? 'justify-start gap-3 px-2' : 'justify-center px-0',
                                        'text-white hover:bg-gray-50/10 hover:text-white'
                                      ]"
                                    >
                                        <BaseMenu
                                            :no-tooltip="true"
                                            :stroke-width="1"
                                            menu-width="!w-fit"
                                            white-menu-background
                                            :menu-button-text="item.name"
                                            :show-menu-button-text="isFullSideBar"
                                            no-relative
                                            tooltip-direction="right"
                                            has-no-offset
                                            show-custom-icon
                                            :icon="item.icon"
                                            white-icon
                                            dots-size="size-6"
                                            :classes-button="isFullSideBar
                                              ? '!h-10 !px-0 flex items-center gap-3 text-current w-full justify-start'
                                              : '!h-10 !px-0 flex items-center text-current w-full justify-center'"
                                        >
                                            <template v-for="subMenu in item.subMenus" :key="subMenu.name">
                                                <BaseMenuItem
                                                    white-menu-background
                                                    as-link
                                                    :href="subMenu.href"
                                                    :icon="subMenu.icon"
                                                    :title="subMenu.name"
                                                />
                                            </template>
                                        </BaseMenu>
                                    </div>
                                </div>
                            </li>

                            <!-- Profil / Account Popover -->
                            <li>
                                <Popover class="relative">
                                    <Float
                                        portal
                                        auto-placement
                                        :offset="{ mainAxis: 12, crossAxis: isFullSideBar ? 0 : 12 }"
                                        :shift="12"
                                        :flip="12"
                                    >
                                        <!-- Trigger -->
                                        <PopoverButton
                                            class="group flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-white/5 focus:outline-none focus:ring-0 focus:ring-white/15"
                                            :class="isFullSideBar ? 'justify-start' : 'justify-center px-3'"
                                        >
                                            <div class="relative">
                                                <img
                                                    class="h-9 w-9 rounded-full object-cover ring-1 ring-white/10 bg-gray-100"
                                                    :src="usePage().props.auth.user.profile_photo_url"
                                                    alt=""
                                                />
                                            </div>

                                            <div v-if="isFullSideBar" class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="truncate text-sm font-semibold text-white">
                                                      {{ usePage().props.auth.user.full_name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </PopoverButton>

                                        <!-- Panel -->
                                        <transition
                                            enter-active-class="transition ease-out duration-150"
                                            enter-from-class="opacity-0 translate-y-1 scale-[0.98]"
                                            enter-to-class="opacity-100 translate-y-0 scale-100"
                                            leave-active-class="transition ease-in duration-120"
                                            leave-from-class="opacity-100 translate-y-0 scale-100"
                                            leave-to-class="opacity-0 translate-y-1 scale-[0.98]"
                                        >
                                            <PopoverPanel
                                                class="z-50 w-[340px] overflow-hidden rounded-2xl border border-white/10 bg-[#0B1220]/95 shadow-2xl backdrop-blur-xl"
                                            >
                                                <!-- Header -->
                                                <div class="p-4">
                                                    <div class="flex items-center gap-3">
                                                        <img
                                                            class="h-12 w-12 rounded-2xl object-cover ring-1 ring-white/10 bg-gray-100"
                                                            :src="usePage().props.auth.user.profile_photo_url"
                                                            alt=""
                                                        />

                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <div class="truncate text-sm font-semibold text-white">
                                                                    {{ usePage().props.auth.user.full_name }}
                                                                </div>

                                                                <span
                                                                    v-if="usePage().props.auth.user.position"
                                                                    class="inline-flex shrink-0 items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px]
                           font-medium text-emerald-200 ring-1 ring-emerald-500/20"
                                                                >
                    {{ usePage().props.auth.user.position }}
                  </span>
                                                            </div>

                                                            <div class="mt-0.5 truncate text-xs text-white/60">
                                                                {{ usePage().props.auth.user.business }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Actions -->
                                                <div class="border-t border-white/10 p-2">
                                                    <Link
                                                        :href="route('user.edit.info', { user: usePage().props.auth.user.id })"
                                                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-white/90
                     transition hover:bg-white/5 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/15"
                                                    >
                                                        <PropertyIcon
                                                            name="IconUserCircle"
                                                            class="h-5 w-5 text-white/50 transition group-hover:text-white/80"
                                                        />
                                                        <span class="flex-1">{{ $t('Your account') }}</span>
                                                        <PropertyIcon name="IconChevronRight" class="h-4 w-4 text-white/40 group-hover:text-white/70" />
                                                    </Link>

                                                    <button
                                                        type="button"
                                                        @click="logout"
                                                        class="group mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-white/90
                     transition hover:bg-red-500/10 hover:text-red-200 focus:outline-none focus:ring-2 focus:ring-red-500/30 cursor-pointer"
                                                    >
                                                        <PropertyIcon
                                                            name="IconLogout"
                                                            class="h-5 w-5 text-white/50 transition group-hover:text-red-200"
                                                        />
                                                        <span class="">{{ $t('Logout') }}</span>
                                                    </button>
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
            <component :is="IconMenu2" class="size-6" aria-hidden="true" />
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
import {
    IconBell,
    IconBrowserShare,
    IconBuildingWarehouse,
    IconCalendarCheck,
    IconCalendarClock,
    IconCalendarCog,
    IconCalendarExclamation, IconCalendarMonth,
    IconCalendarTime,
    IconCalendarUser, IconChevronsLeft, IconChevronsRight,
    IconCurrencyEuro,
    IconDoor,
    IconFileText,
    IconGeometry,
    IconHome,
    IconListCheck,
    IconLockSquareRounded, IconLogout, IconMenu2, IconMoneybag,
    IconParentheses,
    IconSettings, IconTicket,
    IconTimelineEventPlus, IconTrash, IconUserCircle,
    IconUsers, IconX
} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
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

const getVisibleSubMenus = (item) => {
    if (!item?.subMenus?.length) {
        return []
    }

    return item.subMenus.filter((subMenu) => subMenu.has_permission)
}

const getSingleVisibleSubMenu = (item) => {
    if (!item?.isMenu) {
        return null
    }

    const visible = getVisibleSubMenus(item)
    return visible.length === 1 ? visible[0] : null
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
        prefetch: false
    },
    {
        name: 'Projects',
        href: route('projects'),
        icon: 'IconGeometry',
        current: route().current('projects'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('projects'),
        prefetch: ['projects']
    },
    {
        name: 'Calendar',
        href: '#',
        icon: 'IconCalendarClock',
        current: true,
        isMenu: true,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('room_assignment'),
        prefetch: false,
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
        has_permission: moduleIsVisible('shift_plan'),
        prefetch: false,
        subMenus: [
            {
                name: 'Duty rosters',
                href: route('shifts.plan'),
                icon: 'IconCalendarUser',
                current: route().current('shifts.plan'),
                has_permission: can('can view shift plan') || is('artwork admin'),
            },
            {
                name: 'My Operational plan',
                href: route('user.operationPlan', usePage().props.auth.user.id),
                icon: 'IconCalendarUser',
                current: route().current('user.operationPlan'),
                has_permission: moduleIsVisible('shift_plan'),
            },
            /* routes to old page, now we have new shift templates in shift-admin-settings, maybe build in link to new page in admin settings or just leave it out
            {
                name: 'Shift templates',
                href: route('shifts.presets'),
                icon: 'IconCalendarTime',
                current: route().current('shifts.presets') || route().current('shifts.timeline-presets.index'),
                has_permission: can('can view shift plan') || moduleIsVisible('shift_plan') || is('artwork admin'),
            },
            */
            {
                name: 'Work time change requests',
                href: route('work-time-request.index'),
                icon: 'IconTimelineEventPlus',
                current: route().current('work-time-request.index'),
                has_permission: moduleIsVisible('shift_plan'),
            },

            // --- NEU: Prüfungsanfragen ---
            {
                name: 'Shift plan Review requests',
                href: route('shifts.approvals.review'),
                icon: 'IconLockSquareRounded',
                current: route().current('shifts.approvals.review'),
                has_permission: usePage().props.canSeeShiftPlanReview,
            },

            // --- NEU: Änderungsliste ---
            {
                name: 'Shift plan Change list',
                href: route('shifts.approvals.changes'),
                icon: 'IconListCheck',
                current: route().current('shifts.approvals.changes'),
                has_permission: usePage().props.canSeeShiftPlanChangeList,
            },

            // --- NEU: Angefragte Dienstpläne ---
            {
                name: 'Requested duty rosters',
                href: route('shifts.approvals.requests'),
                icon: 'IconCalendarCheck',
                current: route().current('shifts.approvals.requests'),
                has_permission: usePage().props.canSeeShiftPlanRequestedPlans,
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
        prefetch: false,
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
        prefetch: false,
    },
    {
        name: 'Sources of funding',
        href: route('money_sources.index'),
        icon: 'IconCurrencyEuro',
        current: route().current('money_sources.index'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('sources_of_funding') && (can('view edit add money_sources | can edit and delete money sources') || is('artwork admin')),
        prefetch: false,
    },
    {
        name: 'Users',
        href: route('users'),
        icon: 'IconUsers',
        current: route().current('users'),
        isMenu: false,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('users'),
        prefetch: false,
    },
    {
        name: 'Documents',
        href: '#',
        icon: 'IconFileText',
        current: route().current('contracts.index') || route().current('document-requests.index'),
        isMenu: true,
        showToolTipForItem: false,
        has_permission: moduleIsVisible('contracts') && (can('view edit upload contracts | can see and download contract modules | can create document requests | can edit document requests') || is('artwork admin')),
        prefetch: false,
        subMenus: [
            {
                name: 'Contracts',
                href: route('contracts.index'),
                icon: 'IconFileText',
                current: route().current('contracts.index'),
                has_permission: can('view edit upload contracts | can see and download contract modules') || is('artwork admin')
            },
            {
                name: 'Document requests',
                href: route('document-requests.index'),
                icon: 'IconFileDescription',
                current: route().current('document-requests.index'),
                has_permission: can('view edit upload contracts') || can('can create document requests') || can('can edit document requests') || is('artwork admin')
            },
        ]
    },
    {
        name: 'System',
        href: '#',
        icon: 'IconSettings',
        current: true,
        isMenu: true,
        showToolTipForItem: false,
        prefetch: false,
        has_permission: can('change tool settings | create, delete and update rooms | change project settings | change event settings | admin checklistTemplates | set.create_edit | set.delete | shift.settings_view_edit') || is('artwork admin'),
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
                has_permission: is('artwork admin') || can('shift.settings_view_edit')
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
        prefetch: false,
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
