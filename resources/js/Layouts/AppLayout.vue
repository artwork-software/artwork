<template>
    <!-- Static sidebar for desktop -->
    <div class="my-auto w-full">
        <div class="sidebar fixed z-50 top-0 bottom-0 p-2 w-full sm:w-16 bg-primary hidden sm:block">
            <div class="w-full py-2 flex flex-col items-center">
                <div class="text-2xl font-bold text-secondaryHover">
                    <img src="/Svgs/Logos/artwork_logo_small.svg" class="h-16 w-16 mb-8" alt="artwork-logo"/>
                </div>
                <!-- <img alt="small-logo" v-else :src="$page.props.small_logo" class="rounded-full h-16 w-16"/> -->
                <div class="flex-1 w-full space-y-1">
                    <a v-for="item in navigation" :key="item.name" :href="item.href"
                       :class="[isCurrent(item.route) ? ' text-secondaryHover xsWhiteBold' : 'xxsLight  hover:bg-primaryHover hover:text-secondaryHover', 'group w-full py-3 rounded-md flex flex-col items-center', checkPermission(item) ? 'block': 'hidden']">
                        <img :src="isCurrent(item.route) ? item.svgSrc_active : item.svgSrc"
                             alt="menu-item"
                             :class="[isCurrent(item.route) ? ' text-secondaryHover' : 'xxsLight group-hover:text-secondaryHover', 'mb-1']"
                             aria-hidden="true"/>
                    </a>
                    <Menu as="div" class="my-auto" v-if="
                        $canAny([
                            'usermanagement',
                            'admin checklistTemplates',
                            'teammanagement',
                            'update departments',
                            'change tool settings',
                            'change project settings',
                            'change event settings',
                            'change system notification',
                            'view budget templates'
                        ]) || hasAdminRole()
                        ">
                        <div class="flex">
                            <MenuButton
                            >
                                <div
                                    class="w-full cursor-pointer p-1 -mt-2">
                                    <img class="h-16 w-16" :src="isCurrent(this.managementRoutes) ? '/Svgs/IconSvgs/icon_system_settings_active.svg' : '/Svgs/IconSvgs/icon_system_settings_idle.svg'"
                                         alt="Systemeinstellungen"
                                         aria-hidden="true"/>
                                </div>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="z-50 managementMenu max-h-40 overflow-y-auto opacity-100 relative origin-top-left ml-14 -mt-12 w-36 shadow-lg py-1 bg-primary ring-1 ring-black focus:outline-none">
                                <div class="z-50" v-for="item in managementNavigation" :key="item.name">
                                    <MenuItem v-if="item.has_permission" v-slot="{ active }">
                                        <Link :href="item.href"
                                              :class="[isCurrent(item.route) ? 'text-secondaryHover xsWhiteBold' : 'xxsLight hover:bg-primaryHover hover:text-secondaryHover', 'group w-full px-2 py-3 rounded-md flex flex-col items-center ']">
                                            {{ item.name }}
                                        </Link>
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
            <!--   Top Menu     -->
            <div class="sticky top-0 z-40 flex-shrink-0 flex h-24">
                <button type="button"
                        class="px-5 border-r border-primaryText text-primaryText focus:outline-none sm:hidden"
                        @click="openSideBarOnMobile">
                    <span class="sr-only">Open sidebar</span>
                    <MenuAlt2Icon class="h-6 w-6" aria-hidden="true"/>
                </button>
                <div class="px-4 pl-16 ml-3 flex w-full bg-white">
                    <div class="flex justify-start items-center">
                        <Switch @click="toggle_hints()"
                                :class="[$page.props.can.show_hints ?
                                        'bg-success' :
                                        'bg-gray-300',
                                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none']">
                                    <span aria-hidden="true"
                                          :class="[$page.props.can.show_hints ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                        </Switch>
                        <span v-if="$page.props.can.show_hints" class="ml-2 flex w-40">
                                    <SvgCollection svgName="arrowLeft" class="mr-1"/>
                                    <span class="hind">Hilfe einblenden</span>
                                </span>
                    </div>
                    <div class="flex justify-end w-full">
                        <div class="ml-4 flex items-center md:ml-6">
                            <div class="flex items-center mr-4">

                                <Link v-if="hasAdminRole()"
                                      class="inset-y-0 mr-1"
                                      :href="getTrashRoute()">
                                    <TrashIcon class="h-5 w-5" aria-hidden="true"/>
                                </Link>
                                <Link :href="route('notifications.index')" type="button"
                                      class="p-1 rounded-full text-black hover:text-primaryText focus:outline-none">
                                    <span class="sr-only">View notifications</span>
                                    <BellIcon class="h-6 w-6" aria-hidden="true"/>
                                </Link>
                            </div>

                            <Menu as="div" class="ml-3 relative">
                                <div>
                                    <MenuButton @click="showUserMenu = !showUserMenu"
                                                class="flex items-center rounded-full focus:outline-none">
                                        <span class="sr-only">Open user menu</span>
                                        <p class="xsDark flex mr-4 pl-2">Hallo
                                            {{ $page.props.user.first_name }}
                                            <ChevronUpIcon v-if="showUserMenu"
                                                           class="ml-1 flex-shrink-0 mt-0.5 h-4 w-4"></ChevronUpIcon>
                                            <ChevronDownIcon v-else
                                                             class="ml-1 flex-shrink-0 mt-0.5  h-4 w-4"></ChevronDownIcon>
                                        </p>
                                        <img class="h-10 w-10 rounded-full object-cover"
                                             :src="$page.props.user.profile_photo_url"
                                             alt=""/>
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="origin-top-right absolute right-0 mt-2 w-48 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <MenuItem v-slot="{ active }">
                                            <Link :href="route('profile.show')"
                                                  :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                Dein Konto
                                            </Link>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a @click="logout"
                                               :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor cursor-pointer']">Ausloggen</a>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                </div>
            </div>
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
            <!-- Notification -->

            <!--     Main       -->
            <main class="main pl-5">
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
import {Link, usePage} from "@inertiajs/inertia-vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {isAdmin} from "@/Helper/PermissionHelper";
import Permissions from "@/mixins/Permissions.vue";

const navigation = [
    {
        name: 'Dashboard',
        href: route('dashboard'),
        route: ['/dashboard'],
        svgSrc: '/Svgs/Sidebar/icon_dashboard.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_dashboard_active.svg',
        has_permission: 'all'
    },
    {
        name: 'Projekte',
        href: route('projects'),
        route: ['/projects'],
        svgSrc: '/Svgs/Sidebar/icon_projects.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_projects_active.svg',
        has_permission: 'all'
    },
    {
        name: 'Raumbelegung',
        href: route('events'),
        route: ['/calendar/view'],
        svgSrc: '/Svgs/Sidebar/icon_calendar.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_calendar_active.svg',
        has_permission: 'all'
    },
    {
        name: 'Dienstplan',
        href: route('shifts.plan'),
        route: ['/shifts/view'],
        svgSrc: '/Svgs/Sidebar/icon_shift_plan.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_shift_plan_active.svg',
        has_permission: 'can see shift plan'
    },
    {
        name: 'Aufgaben',
        href: route('tasks.own'),
        route: ['/tasks/own'],
        svgSrc: '/Svgs/Sidebar/icon_tasks.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_tasks_active.svg',
        has_permission: 'all'
    },

    {
        name: 'Finanzierungsquellen',
        href: route('money_sources.index'),
        route: ['/money_sources'],
        svgSrc: '/Svgs/Sidebar/icon_money_sources.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_money_sources_active.svg',
        has_permission: 'all'
    },
    {
        name: 'Nutzer*innen',
        href: route('users'),
        route: ['/users'],
        svgSrc: '/Svgs/Sidebar/icon_users_teams.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_users_teams_active.svg',
        has_permission: 'all'
    },

    {
        name: 'Verträge',
        href: route('contracts.view.index'),
        route: ['/contracts/view'],
        svgSrc: '/Svgs/Sidebar/icon_contract.svg',
        svgSrc_active: '/Svgs/Sidebar/icon_contract_active.svg',
        has_permission: 'all'
    }
]

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
    mixins: [Permissions],
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
        TrashIcon
    },
    computed: {
        managementNavigation() {
            return [
                {
                    has_permission: this.checkPermissionGlobalMessageAndToolSettings(),
                    name: 'Tool',
                    href: route('tool.settings'),
                    route: ['/tool/settings']
                },
                /*{
                    has_permission: this.$can('usermanagement') || this.hasAdminRole(),
                    name: 'Nutzer*innen',
                    href: route('users'),
                    route: ['/users']
                },*/
                {
                    has_permission: this.hasAdminRole(),
                    name: 'Schichteinstellungen',
                    href: route('shift.settings'),
                    route: ['/settings/shift']
                },
                /*{
                    name: 'Teams',
                    has_permission: this.$canAny(['teammanagement', 'update departments']) || this.hasAdminRole(),
                    href: route('departments'),
                    route: ['/departments']
                },*/
                {
                    name: 'Räume',
                    has_permission: this.$can('admin rooms') || this.hasAdminRole() ,
                    href: route('areas.management'),
                    route: ['/areas']
                },
                {
                    name: 'Anfragen',
                    has_permission: this.$can('read details room request') || this.hasAdminRole(),
                    href: route('events.requests'),
                    route: ['/events/requests']
                },
                {
                    name: 'Projekte',
                    has_permission: this.$can('change project settings') || this.hasAdminRole(),
                    href: route('project.settings'),
                    route: ['/settings/projects']
                },
                {
                    name: 'Termine',
                    has_permission: this.$can('change event settings') || this.hasAdminRole(),
                    href: route('event_types.management'),
                    route: ['/event_types']
                },
                {
                    name: 'Checklisten',
                    has_permission: this.$can('admin checklistTemplates') || this.hasAdminRole(),
                    href: route('checklist_templates.management'),
                    route: ['/checklist_templates']
                },
                {
                    name: 'Budget Vorlagen',
                    has_permission: this.hasAdminRole() || this.$can('view budget templates'),
                    href: route('templates.view.index'),
                    route: ['/templates/index']
                },
            ]
        }
    },
    methods: {
        usePage,
        checkPermissionGlobalMessageAndToolSettings() {
            return this.$canAny(['change tool settings', 'change system notification'] || this.hasAdminRole());

        },
        checkPermission(item) {
            if (item.has_permission === 'is_money_source_admin') {
                if (this.$page.props.myMoneySources.length > 0 || this.$canAny(['can edit and delete money sources', 'view edit add money_sources'])) {
                    return true;
                }
            }else if(item.has_permission === 'can see shift plan'){
                if(this.$can('can view shift plan') || this.hasAdminRole()){
                    return true;
                }
            }
            return item.has_permission === 'all';

        },
        getTrashRoute() {

            if (this.$page.url === '/areas') {

                return route('areas.trashed')

            } else {

                return route('projects.trashed')

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
            this.$inertia.post(route('logout'))
        },

        openSideBarOnMobile() {
            document.querySelector(".sidebar").classList.toggle("hidden");
            document.querySelector(".main").classList.toggle("hidden");
        },
        closePushNotification(id) {
            const pushNotification = document.getElementById(id);
            pushNotification?.remove();
        }
    },
    mounted() {
        let ev = document.createEvent("Event");
        ev.initEvent("DOMContentLoaded", true, true);
        window.document.dispatchEvent(ev);

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
            showPermissions: false
        }
    },
    setup() {
        const sidebarOpen = ref(false)

        return {
            navigation,
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
