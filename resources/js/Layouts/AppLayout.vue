<template>
    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ $page.props.name }}</title>
    </Head>
    <!-- Static sidebar for desktop -->
    <div class="my-auto w-full">
        <div :class="this.fullSidenav ? 'sm:w-64' : 'sm:w-16'"
             class="sidebar fixed z-50 top-0 bottom-0 p-2 w-full bg-primary hidden sm:block">
            <div class="w-full py-2 flex flex-col h-[100%] items-center justify-between">
                <div>
                    <div class="flex items-center" :class="fullSidenav ? 'w-full' : ''">
                        <div class="group relative">
                            <div class="cursor-pointer absolute group-hover:block hidden bg-primary/70 z-10 h-full w-full" @click="changeSidenavMode()">
                                <div class="flex items-center justify-center h-full w-full">
                                    <IconChevronsRight v-if="!fullSidenav" class="h-6 w-6 text-white" aria-hidden="true"/>
                                    <IconChevronsLeft v-else class="h-6 w-6 text-white" aria-hidden="true"/>
                                </div>
                            </div>
                            <div class="font-bold text-secondaryHover block">
                                <img :src="$page.props.small_logo" :class="fullSidenav ? 'h-fit w-12' : 'h-fit w-16'" alt="artwork-logo"/>
                            </div>
                        </div>
                        <div v-if="fullSidenav" class="ml-4">
                            <img :src="$page.props.big_logo" :class="fullSidenav ? 'h-fit w-12' : 'h-fit w-16'" alt="artwork-logo"/>
                        </div>
                    </div>

                    <!-- <img alt="small-logo" v-else :src="$page.props.small_logo" class="rounded-full h-16 w-16"/> -->
                    <div class="flex-1 w-full space-y-1 mt-8 overflow-y-auto managementMenu">
                        <a v-for="item in navigation" :key="item.name" :href="item.href" :class="[isCurrent(item.route) ? ' text-secondaryHover xsWhiteBold' : 'xxsLight hover:bg-primaryHover hover:text-secondaryHover', 'group w-full py-3 rounded-md flex flex-col items-center', item.has_permission ? 'block': 'hidden']">
                            <div class="flex items-center">
                                <Component :is="item.icon" :stroke-width="isCurrent(item.route) ? 2 : 1" :class="[isCurrent(item.route) ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                                <div class="ml-4 w-32" v-if="fullSidenav">
                                    {{ item.name }}
                                </div>
                            </div>
                        </a>
                        <Menu as="div" class="flex flex-col items-center" v-show="
                        $canAny([
                            'usermanagement',
                            'admin checklistTemplates',
                            'teammanagement',
                            'update departments',
                            'change tool settings',
                            'change project settings',
                            'change event settings',
                            'change system notification',
                            'view budget templates',
                            'create, delete and update rooms'
                        ]) || hasAdminRole()
                        ">
                            <MenuButton ref="menuButton" @click="setHeightOfMenuItems" :class="[isCurrent(this.managementRoutes) ? ' text-secondaryHover xsWhiteBold' : 'xxsLight hover:bg-primaryHover hover:text-secondaryHover', 'group w-full py-3 rounded-md flex flex-col items-center']">
                                <div class="flex items-center" :class="fullSidenav ? '' : ''">
                                    <Component :is="IconAdjustmentsAlt" :stroke-width="isCurrent(this.managementRoutes) ? 2 : 1" :class="[isCurrent(this.managementRoutes) ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                                    <div class="ml-4 w-32 text-left" v-if="fullSidenav">
                                        System
                                    </div>
                                </div>
                            </MenuButton>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems ref="menuItems" :class="fullSidenav ? 'ml-36 left-28' : 'ml-14'"
                                           class="z-50 managementMenu max-h-40 overflow-y-auto opacity-100 absolute origin-top-left w-36 shadow-lg py-1 bg-primary ring-1 ring-black focus:outline-none">
                                    <div class="z-50" v-for="item in managementNavigation" :key="item.name">
                                        <MenuItem v-if="item.has_permission" v-slot="{ active }">
                                            <Link :href="item.href"
                                                  :class="[item.isCurrent ? 'text-secondaryHover xsWhiteBold' : 'xxsLight hover:bg-primaryHover hover:text-secondaryHover', 'group w-full px-2 py-3 rounded-md flex flex-col items-center ']">
                                                {{ item.name }}
                                            </Link>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                        <a   :href="getTrashRoute()" v-if="hasAdminRole()" :class="[isCurrentTrashRoute() ? ' text-secondaryHover xsWhiteBold' : 'xxsLight hover:bg-primaryHover hover:text-secondaryHover', 'group w-full py-3 rounded-md flex flex-col items-center']">
                            <div class="flex items-center">
                                <component :is="IconTrash" :stroke-width="isCurrentTrashRoute() ? 2 : 1" :class="[isCurrentTrashRoute() ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                                <div class="ml-4 w-32" v-if="fullSidenav">
                                    {{  $t('Recycle bin') }}
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="flex flex-col justify-end w-full">
                    <a :href="route('notifications.index')" :class="[route().current('notifications.*') ? ' text-secondaryHover xsWhiteBold' : 'xxsLight hover:bg-primaryHover hover:text-secondaryHover', 'group w-full py-3 rounded-md flex flex-col items-center']">
                        <div class="flex items-center">
                            <Component :is="IconBell" :stroke-width="route().current('notifications.*') ? 2 : 1" :class="[route().current('notifications.*') ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                            <div class="ml-4 w-32" v-if="fullSidenav">
                                {{ $t('Notifications') }}
                            </div>
                        </div>
                    </a>
                    <Menu as="div" class="flex flex-col items-center">
                        <MenuButton ref="menuButton" @click="setHeightOfMenuItems" :class="[isCurrent(this.userNavigation) ? ' text-secondaryHover xsWhiteBold' : 'xxsLight hover:bg-primaryHover hover:text-secondaryHover', 'group w-full py-3 rounded-md flex flex-col items-center']">
                            <div class="flex items-center" :class="fullSidenav ? '' : ''">
                                <img class="h-7 w-7 rounded-full object-cover" :src="$page.props.user.profile_photo_url" alt=""/>
                                <div class="ml-4 w-32 text-left" v-if="fullSidenav">
                                    Hallo
                                    {{ $page.props.user.first_name }}
                                </div>
                            </div>
                        </MenuButton>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems ref="menuItems" :class="fullSidenav ? 'ml-36 left-28' : 'ml-14'"
                                       class="z-50 managementMenu max-h-40 overflow-y-auto opacity-100 absolute origin-top-left w-36 shadow-lg py-1 bg-primary ring-1 ring-black focus:outline-none">
                                <div class="z-50">
                                    <MenuItem v-slot="{ active }">
                                        <Link :href="route('user.edit.info', {user: this.$page.props.user.id})"
                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            {{ $t('Your account')}}
                                        </Link>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="logout" :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor cursor-pointer']">{{ $t('Log out')}}</a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
                <!-- TODO: Hier noch Link zu Über uns Page
                <div class=" absolute bottom-0 mb-10 text-secondary subpixel-antialiased text-sm tracking-wide">
                    <a href="">
                        Über das Tool
                    </a>
                </div>
                -->
            </div>
        </div>

        <div class="pl-2 flex flex-col">
            <div v-if="pushNotifications.length > 0" class="absolute top-16 right-5">
                <div v-for="pushNotification in pushNotifications" :id="pushNotification.id"
                     class="my-2 z-50 flex relative w-full max-w-xs rounded-lg shadow bg-lightBackgroundGray"
                     role="alert">
                    <div class="flex p-4">
                        <div class="inline-flex flex-shrink-0 justify-center items-center rounded-lg">
                            <img alt="Notification" v-if="pushNotification.type === 'success'"
                                 class="h-9 w-9" src="/Svgs/IconSvgs/icon_push_notification_green.svg"/>
                            <img alt="Notification" v-if="pushNotification.type === 'error'" class="h-9 w-9"
                                 src="/Svgs/IconSvgs/icon_push_notification_red.svg"/>
                        </div>
                        <div class="ml-4 xsDark">{{ pushNotification.message }}</div>
                    </div>
                    <button type="button" class="-mt-4 mr-2">
                        <XIcon class="-mt-4 h-5 w-5 text-secondary hover:text-error relative"
                               @click="closePushNotification(pushNotification.id)"/>
                    </button>
                </div>
            </div>

            <main class="main my-5 mx-5">
                <slot></slot>
            </main>
        </div>
    </div>

</template>

<script>
import {ref} from 'vue'
import {Dialog, DialogOverlay, Menu, MenuButton, MenuItem, MenuItems, Switch,} from '@headlessui/vue'
import {BellIcon, ChevronDownIcon, ChevronUpIcon, MenuAlt2Icon, TrashIcon, XIcon} from '@heroicons/vue/outline'
import {SearchIcon} from '@heroicons/vue/solid'
import {Link, usePage, Head} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {isAdmin} from "@/Helper/PermissionHelper";
import Permissions from "@/mixins/Permissions.vue";
import {
    IconAdjustmentsAlt, IconBell,
    IconCalendarMonth,
    IconCalendarUser,
    IconCurrencyEuro, IconFileText,
    IconGeometry, IconLayoutDashboard,
    IconListCheck, IconTrash,
    IconUsers
} from "@tabler/icons-vue";
import IconLib from "@/mixins/IconLib.vue";

const userNavigation = [
    {name: 'Your Profile', href: '#'},
    {name: 'Settings', href: '#'},
]

const managementRoutes = [
    '/tool/settings',
    '/settings/shift',
    '/departments',
    '/areas',
    '/events/requests',
    '/settings/projects',
    '/event_types',
    '/checklist_templates',
    '/templates/index'
]

export default {
    mixins: [Permissions, IconLib],
    components: {
        SvgCollection,
        Dialog,
        DialogOverlay,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        BellIcon,
        MenuAlt2Icon,
        SearchIcon,
        XIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Link,
        Switch,
        TrashIcon, Head
    },
    computed: {
        managementNavigation() {
            //default budget route is general
            let desiredBudgetRoute = route('budget-settings.general');

            //if user has permissions for budget templates but not for managing global project budgets the route is
            //budget templates
            if (
                this.$can('view budget templates') &&
                !this.$canAny([
                    'can manage global project budgets',
                    'can manage all project budgets without docs'
                ])
            ) {
                desiredBudgetRoute = route('budget-settings.templates');
            }

            return [
                {
                    has_permission: this.$can('change tool settings'),
                    name: 'Tool',
                    href: route('tool.branding'),
                    isCurrent: route().current('tool.branding') ||
                        route().current('tool.communication-and-legal') ||
                        route().current('tool.interfaces')
                },
                {
                    has_permission: this.hasAdminRole(),
                    name: this.$t('Shift settings'),
                    href: route('shift.settings'),
                    isCurrent: route().current('shift.settings')
                },
                {
                    name: this.$t('Rooms'),
                    has_permission: this.$can('create, delete and update rooms') || this.hasAdminRole(),
                    href: route('areas.management'),
                    isCurrent: route().current('areas.management')
                },
                {
                    name: this.$t('Projects'),
                    has_permission: this.$can('change project settings') || this.hasAdminRole(),
                    href: route('project.settings'),
                    isCurrent: route().current('project.settings')
                },
                {
                    name: this.$t('Events'),
                    has_permission: this.$can('change event settings') || this.hasAdminRole(),
                    href: route('event_types.management'),
                    isCurrent: route().current('event_types.management')
                },
                {
                    name: this.$t('Checklists'),
                    has_permission: this.$can('admin checklistTemplates') || this.hasAdminRole(),
                    href: route('checklist_templates.management'),
                    isCurrent: route().current('checklist_templates.management')
                },
                {
                    name: this.$t('Sources of funding'),
                    has_permission: this.hasAdminRole(),
                    href: route('money_sources.settings'),
                    isCurrent: route().current('money_sources.settings')
                },
                {
                    name: this.$t('Budget'),
                    has_permission: this.$canAny(
                        [
                            'can manage global project budgets',
                            'can manage all project budgets without docs',
                            'view budget templates',
                            'edit budget templates'
                        ]
                    ),
                    href: desiredBudgetRoute,
                    isCurrent: route().current('budget-settings.general') ||
                        route().current('budget-settings.account-management') ||
                        route().current('budget-settings.templates')
                },
            ]
        },
        navigation() {
            return [
                {
                    name: 'Dashboard',
                    href: route('dashboard'),
                    route: ['/dashboard'],
                    svgSrc: '/Svgs/Sidebar/icon_dashboard.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_dashboard_active.svg',
                    has_permission: true,
                    icon: IconLayoutDashboard
                },
                {
                    name: this.$t('Projects'),
                    href: route('projects'),
                    route: ['/projects'],
                    svgSrc: '/Svgs/Sidebar/icon_projects.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_projects_active.svg',
                    has_permission: true,
                    icon: IconGeometry
                },
                {
                    name: this.$t('Room assignment'),
                    href: route('events'),
                    route: ['/calendar/view'],
                    svgSrc: '/Svgs/Sidebar/icon_calendar.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_calendar_active.svg',
                    has_permission: true,
                    icon: IconCalendarMonth
                },
                {
                    name: this.$t('Shift plan'),
                    href: route('shifts.plan'),
                    route: ['/shifts/view'],
                    svgSrc: '/Svgs/Sidebar/icon_shift_plan.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_shift_plan_active.svg',
                    has_permission: this.$can('can view shift plan') || this.hasAdminRole(),
                    icon: IconCalendarUser
                },
                {
                    name: this.$t('Tasks'),
                    href: route('tasks.own'),
                    route: ['/tasks/own'],
                    svgSrc: '/Svgs/Sidebar/icon_tasks.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_tasks_active.svg',
                    has_permission: true,
                    icon: IconListCheck
                },

                {
                    name: this.$t('Sources of funding'),
                    href: route('money_sources.index'),
                    route: ['/money_sources'],
                    svgSrc: '/Svgs/Sidebar/icon_money_sources.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_money_sources_active.svg',
                    has_permission: this.$canAny(['view edit add money_sources', 'can edit and delete money sources']) || this.hasAdminRole(),
                    icon: IconCurrencyEuro
                },
                {
                    name: this.$t('Users'),
                    href: route('users'),
                    route: ['/users'],
                    svgSrc: '/Svgs/Sidebar/icon_users_teams.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_users_teams_active.svg',
                    has_permission: true,
                    icon: IconUsers
                },

                {
                    name: this.$t('Contracts'),
                    href: route('contracts.index'),
                    route: ['/contracts/view'],
                    svgSrc: '/Svgs/Sidebar/icon_contract.svg',
                    svgSrc_active: '/Svgs/Sidebar/icon_contract_active.svg',
                    has_permission: true,
                    icon: IconFileText
                }
            ]
        }
    },
    methods: {
        IconBell,
        IconTrash,
        IconAdjustmentsAlt,
        usePage,
        getTrashRoute() {
            if (this.$page.url === '/areas') {
                return route('areas.trashed')
            } else {
                return route('projects.trashed')
            }
        },
        isCurrentTrashRoute() {
            if (this.$page.url === '/areas') {
                return route().current('areas.trashed')
            } else {
                return route().current('projects.trashed')
            }
        },
        isCurrent(routes) {
            for (let url of routes) {
                if (this.$page.url.indexOf(url) !== -1) {
                    return true
                }
            }
        },
        toggle_hints() {
            this.$inertia.post(route('toggle.hints'))
        },

        logout() {
            this.$i18n.locale = this.$page.props.default_language; // Für VueI18n 9.x und Vue 3
            document.documentElement.lang = this.$page.props.default_language;
            this.$inertia.post(route('logout'))
        },

        openSideBarOnMobile() {
            document.querySelector(".sidebar").classList.toggle("hidden");
            document.querySelector(".main").classList.toggle("hidden");
        },
        closePushNotification(id) {
            const pushNotification = document.getElementById(id);
            pushNotification?.remove();
        },
        changeSidenavMode() {
            this.fullSidenav = !this.fullSidenav;
        },
        setHeightOfMenuItems() {
            this.$nextTick(() => {
                const menuButton = this.$refs.menuButton.$el || this.$refs.menuButton;
                const menuItems = this.$refs.menuItems.$el || this.$refs.menuItems;
                const offsetLeft = this.fullSidenav ? 80 : 0;
                if (menuButton && menuItems) {
                    const rect = menuButton.getBoundingClientRect();
                    menuItems.style.top = `${rect.bottom - 70}px`;
                    menuItems.style.left = `${rect.left + offsetLeft}px`;
                } else {
                    console.error('Refs are undefined:', { menuButton, menuItems });
                }
            });
        }
    },
    mounted() {

        let ev = document.createEvent("Event");
        ev.initEvent("DOMContentLoaded", true, true);
        window.document.dispatchEvent(ev);
        this.$i18n.locale = this.$page.props.selected_language; // Für VueI18n 9.x und Vue 3
        document.documentElement.lang = this.$page.props.selected_language;
        Echo.private('App.Models.User.' + this.$page.props.user.id)
            .notification((notification) => {
                this.pushNotifications.push(notification.message);
                setTimeout(() => {
                    this.closePushNotification(notification.message.id)
                }, 3000)
            });
    },
    data() {
        return {
            showSystemSettings: false,
            showUserMenu: false,
            pushNotifications: [],
            showPermissions: false,
            hoveredIcon: false,
            fullSidenav: false,
        }
    },
    setup() {
        const sidebarOpen = ref(false)


        return {
            userNavigation,
            sidebarOpen,
            managementRoutes
        }
    },
}

</script>

<style scoped>
.managementMenu {
    overflow: overlay;
}

::-webkit-scrollbar {
    width: 16px;
}

::-webkit-scrollbar-track {
    background-color: transparent;
}

::-webkit-scrollbar-thumb {
    background-color: #A7A6B170;
    border-radius: 16px;
    border: 6px solid transparent;
    background-clip: content-box;
}

::-webkit-scrollbar-thumb:hover {
    background-color: #a8bbbf;
}


</style>
