<template>
    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ title }} - {{ $page.props.page_title }}</title>
    </Head>
    <!-- Static sidebar for desktop -->
    <div class="my-auto w-full">
        <div :class="this.fullSidenav ? 'sm:w-64' : 'sm:w-16'" id="sidebar"
             class="fixed sidebar z-50 top-0 bottom-0 p-2 w-full bg-artwork-navigation-background hidden sm:block">
            <div class="w-full py-2 flex flex-col h-[100%] items-center justify-between overflow-auto">
                <div class="w-full">
                    <div class="flex items-center justify-center" :class="fullSidenav ? 'w-full' : ''">
                        <div class="group relative">
                            <div class="cursor-pointer absolute group-hover:block hidden bg-artwork-navigation-background/70 z-10 h-full w-full" @click="changeSidenavMode()">
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
                        <a v-for="item in navigation" :key="item.name" :href="item.href" :class="[isCurrent(item.route) ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs', item.has_permission ? 'block': 'hidden']">
                            <div class="flex items-center">
                                <Component :is="item.icon" :stroke-width="isCurrent(item.route) ? 2 : 1" :class="[isCurrent(item.route) ? 'text-white' : 'text-white group-hover:text-white group-hover:font-bold', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
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
                            <MenuButton ref="menuButton" @click="setHeightOfMenuItems" :class="[isCurrent(this.managementRoutes) ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs']">
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
                                <MenuItems ref="menuItems" :class="fullSidenav ? 'ml-40' : 'ml-14'"
                                           class="z-50 managementMenu max-h-40 overflow-y-auto opacity-100 absolute origin-top-left w-48 shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black focus:outline-none">
                                    <div class="z-50" v-for="item in managementNavigation" :key="item.name">
                                        <MenuItem v-if="item.has_permission" v-slot="{ active }">
                                            <Link :href="item.href"
                                                  :class="[item.isCurrent ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs']">
                                                {{ item.name }}
                                            </Link>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                        <a :href="getTrashRoute()" v-if="hasAdminRole()" :class="[isCurrentTrashRoute() ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs']">
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
                    <a :href="route('notifications.index')" :class="[route().current('notifications.*')  ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs']">
                        <div class="flex items-center">
                            <Component :is="IconBell" :stroke-width="route().current('notifications.*') ? 2 : 1" :class="[route().current('notifications.*') ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                            <div class="ml-4 w-32" v-if="fullSidenav">
                                {{ $t('Notifications') }}
                            </div>
                        </div>
                    </a>
                    <Menu as="div" class="flex flex-col items-center">
                        <MenuButton ref="menuButton" @click="setHeightOfMenuItems" class="text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs hover:bg-artwork-navigation-color/10">
                            <div class="flex items-center">
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
                            <MenuItems ref="menuItems" :class="[fullSidenav ? 'ml-40' : 'ml-14', '']" class="z-50 managementMenu max-h-40 overflow-y-auto opacity-100 absolute origin-top-left w-44 shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black focus:outline-none">
                                <div class="z-50">
                                    <MenuItem v-slot="{ active }">
                                        <Link :href="route('user.edit.info', {user: this.$page.props.user.id})"
                                              :class="[active ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs']">
                                            {{ $t('Your account')}}
                                        </Link>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="logout" :class="[active ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-150 ease-in-out hover:font-bold text-xs cursor-pointer']">{{ $t('Log out')}}</a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
        </div>

        <div class="flex flex-col min-h-screen pl-2" @click="fullSidenav = false">
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
import {Link, usePage, Head} from "@inertiajs/vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {
    IconAdjustmentsAlt, IconBell, IconBuildingWarehouse,
    IconCalendarMonth,
    IconCalendarUser,
    IconCurrencyEuro, IconFileText,
    IconGeometry, IconLayoutDashboard,
    IconListCheck, IconTrash,
    IconUsers
} from "@tabler/icons-vue";
import IconLib from "@/Mixins/IconLib.vue";
import TextComponent from "@/Components/Inputs/TextInputComponent.vue";
import NumberComponent from "@/Components/Inputs/NumberInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DateComponent from "@/Components/Inputs/DateInputComponent.vue";

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
        DateComponent,
        TextareaComponent,
        NumberComponent,
        TextComponent,
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
                    has_permission: true,
                    icon: IconLayoutDashboard
                },
                {
                    name: this.$t('Projects'),
                    href: route('projects'),
                    route: ['/projects'],
                    has_permission: true,
                    icon: IconGeometry
                },
                {
                    name: this.$t('Room assignment'),
                    href: route('events'),
                    route: ['/calendar/view'],
                    has_permission: true,
                    icon: IconCalendarMonth
                },
                {
                    name: this.$t('Shift plan'),
                    href: route('shifts.plan'),
                    route: ['/shifts/view'],
                    has_permission: this.$can('can view shift plan') || this.hasAdminRole(),
                    icon: IconCalendarUser
                },
                {
                    name: this.$t('Inventory'),
                    href: route('inventory-management.inventory'),
                    route: ['/inventory-management', '/inventory-management/scheduling'],
                    has_permission: false,
                    icon: IconBuildingWarehouse
                },
                {
                    name: this.$t('Tasks'),
                    href: route('tasks.own'),
                    route: ['/tasks/own'],
                    has_permission: true,
                    icon: IconListCheck
                },
                {
                    name: this.$t('Sources of funding'),
                    href: route('money_sources.index'),
                    route: ['/money_sources'],
                    has_permission: this.$canAny(['view edit add money_sources', 'can edit and delete money sources']) || this.hasAdminRole(),
                    icon: IconCurrencyEuro
                },
                {
                    name: this.$t('Users'),
                    href: route('users'),
                    route: ['/users'],
                    has_permission: true,
                    icon: IconUsers
                },

                {
                    name: this.$t('Contracts'),
                    href: route('contracts.index'),
                    route: ['/contracts/view'],
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
            this.$i18n.locale = this.$page.props.default_language;
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
            testModel: '',
            testModel2: '',
            testModel3: '',
            testModel4: ''
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
    props: {
        title: {
            type: String,
            default: 'Startseite'
        }
    }
}

</script>
