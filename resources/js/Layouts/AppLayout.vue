<template>
    <Head>
        <link rel="icon" type="image/png" :href="$page.props.small_logo" />
        <title>{{ title }} - {{ $page.props.page_title }}</title>
    </Head>
    <!-- Static sidebar for desktop -->
    <div class="my-auto w-full relative">
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
                                <img :src="$page.props.small_logo" :class="fullSidenav ? 'h-12 w-12' : 'h-12 w-12'" class="object-cover" alt="artwork-logo"/>
                            </div>
                        </div>
                        <div v-if="fullSidenav" class="ml-4">
                            <img :src="$page.props.big_logo" :class="fullSidenav ? 'h-12 w-12' : 'h-16 w-16'" alt="artwork-logo"/>
                        </div>
                    </div>
                    <div :class="computedWindowInnerHeight > 855 ? 'mt-4' : 'mt-0'"  class="flex flex-col w-full space-y-0.5 overflow-y-auto managementMenu">
                        <template v-for="item in navigation">
                            <Link v-if="item.desiredClickHandler"
                                  :href="item.href"
                                  @mouseover="showToolTipForItem(item)"
                                  @mouseleave="hideToolTipForItem(item)"
                                  @click.middle="useProjectTimePeriodAndRedirect(null, true)"
                                  @click="item.desiredClickHandler"
                                  :class="[isCurrent(item.route) ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full h-12 rounded-md flex flex-row justify-center items-center transition-all duration-300 ease-in-out hover:font-bold text-xs', item.has_permission ? 'block': 'hidden']">
                                <Component :is="item.icon" :stroke-width="isCurrent(item.route) ? 2 : 1" :class="[isCurrent(item.route) ? 'text-white' : 'text-white group-hover:text-white group-hover:font-bold', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                                <div class="ml-4 w-32" v-if="fullSidenav">
                                    {{ $t(item.name) }}
                                </div>
                                <div :style="[{ display: item.showToolTipForItem ? 'block' : 'none' }]" class="absolute left-14">
                                    <div class="p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit">
                                        {{ $t(item.name) }}
                                    </div>
                                </div>
                            </Link>
                            <Link v-else
                               @mouseover="showToolTipForItem(item)"
                               @mouseleave="hideToolTipForItem(item)"
                               :key="item.name"
                               :href="item.href"
                               :class="[isCurrent(item.route) ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full h-12 rounded-md flex flex-row justify-center items-center transition-all duration-300 ease-in-out hover:font-bold text-xs', item.has_permission ? 'block': 'hidden']"
                            >
                                <Component :is="item.icon" :stroke-width="isCurrent(item.route) ? 2 : 1" :class="[isCurrent(item.route) ? 'text-white' : 'text-white group-hover:text-white group-hover:font-bold', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                                <!--<ToolTipNavigationComponent v-else :tooltip-text="item.name" :icon="item.icon" :icon-size="'h-7 w-7'" :stroke="isCurrent(item.route) ? 2 : 1" direction="right" :classes="[isCurrent(item.route) ? 'text-white' : 'text-white group-hover:text-white group-hover:font-bold', 'h-7 w-7 shrink-0']"/>-->
                                <div class="ml-4 w-32" v-if="fullSidenav">
                                    {{ $t(item.name) }}
                                </div>
                                <div :style="[{ display: item.showToolTipForItem ? 'block' : 'none' }]" class="absolute left-14">
                                    <div class="p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit">
                                        {{ $t(item.name) }}
                                    </div>
                                </div>
                            </Link>
                        </template>
                        <Menu as="div" class="flex flex-col items-center">
                            <MenuButton
                                @mouseover="!fullSidenav ? hoverManagementMenu = true : null"
                                @mouseleave="hoverManagementMenu = false"
                                ref="menuButton"
                                @click="setHeightOfMenuItems"
                                :class="[isCurrent(this.managementRoutes) ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full h-12 rounded-md flex flex-row justify-center items-center transition-all duration-3000 ease-in-out hover:font-bold text-xs']">
                                <div class="flex items-center" :class="fullSidenav ? '' : ''">
                                    <Component :is="IconAdjustmentsAlt" :stroke-width="isCurrent(this.managementRoutes) ? 2 : 1" :class="[isCurrent(this.managementRoutes) ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                                    <div class="ml-4 w-32 text-left" v-if="fullSidenav">
                                        {{ $t('System') }}
                                    </div>
                                </div>
                                <div :style="[{ display: hoverManagementMenu ? 'block' : 'none' }]" class="absolute left-14">
                                    <div class="p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit">
                                        {{ $t('System') }}
                                    </div>
                                </div>
                            </MenuButton>
                            <transition enter-active-class="transition-enter-active"
                                        enter-from-class="transition-enter-from"
                                        enter-to-class="transition-enter-to"
                                        leave-active-class="transition-leave-active"
                                        leave-from-class="transition-leave-from"
                                        leave-to-class="transition-leave-to">
                                <MenuItems ref="menuItems" :class="fullSidenav ? 'ml-40' : 'ml-14'"
                                           class="z-100 managementMenu max-h-52 rounded-lg overflow-y-auto opacity-100 absolute origin-top-left w-48 shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black focus:outline-none">
                                    <div class="z-100" v-for="item in managementNavigation" :key="item.name">
                                        <MenuItem v-if="item.has_permission" v-slot="{ active }">
                                            <Link :href="item.href"
                                                  :class="[item.isCurrent ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full py-3 rounded-md flex flex-col items-center transition-all duration-300 ease-in-out hover:font-bold text-xs']">
                                                {{ $t(item.name) }}
                                            </Link>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                        <a @mouseover="!fullSidenav ? hoverTrashMenu = true : null"
                           @mouseleave="hoverTrashMenu = false" :href="getTrashRoute()" v-if="hasAdminRole()" :class="[isCurrentTrashRoute() ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full h-12 rounded-md flex flex-row justify-center items-center transition-all duration-300 ease-in-out hover:font-bold text-xs']">
                            <div class="flex items-center">
                                <component :is="IconTrash" :stroke-width="isCurrentTrashRoute() ? 2 : 1" :class="[isCurrentTrashRoute() ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                                <div class="ml-4 w-32" v-if="fullSidenav">
                                    {{  $t('Recycle bin') }}
                                </div>
                            </div>
                            <div :style="[{ display: hoverTrashMenu ? 'block' : 'none' }]" class="absolute left-14">
                                <div class="p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit">
                                    {{  $t('Recycle bin') }}
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="flex flex-col justify-end w-full">
                    <a @mouseover="!fullSidenav ? hoverNotificationsMenu = true : null"
                       @mouseleave="hoverNotificationsMenu = false" :href="route('notifications.index')" :class="[route().current('notifications.*')  ? 'font-bold' : ' hover:bg-artwork-navigation-color/10', 'text-artwork-navigation-color group w-full h-12 rounded-md flex flex-row justify-center items-center transition-all duration-300 ease-in-out hover:font-bold text-xs']">
                        <div class="relative flex flex-row justify-center items-center transition-all duration-300 ease-in-out hover:font-bold text-xs">
                            <Component :is="IconBell" :stroke-width="route().current('notifications.*') ? 2 : 1" :class="[route().current('notifications.*') ? 'text-white' : 'text-white group-hover:text-white', 'h-7 w-7 shrink-0']" aria-hidden="true"/>
                            <div v-if="this.$page.props.auth.user.show_notification_indicator === true"
                                 style="font-size: 7px;"
                                 class="w-3 h-3 block absolute top-0 right-0 rounded-full bg-white text-black text-center">
                            </div>
                            <div class="ml-4 w-32" v-if="fullSidenav">
                                {{ $t('Notifications') }}
                            </div>
                        </div>
                        <div :style="[{ display: hoverNotificationsMenu ? 'block' : 'none' }]" class="absolute left-14">
                            <div class="p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit">
                                {{ $t('Notifications') }}
                            </div>
                        </div>
                    </a>
                    <Menu as="div" class="flex flex-col items-center">
                        <MenuButton @mouseover="!fullSidenav ? hoverUserMenu = true : null"
                                    @mouseleave="hoverUserMenu = false" ref="menuButton" @click="setHeightOfMenuItems" class="text-artwork-navigation-color group w-full h-12 rounded-md flex flex-row justify-center items-center transition-all duration-300 ease-in-out hover:font-bold text-xs hover:bg-artwork-navigation-color/10">
                            <img class="h-7 w-7 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" alt=""/>
                            <div class="ml-4 w-32 text-left" v-if="fullSidenav">
                                Hallo
                                {{ $page.props.auth.user.first_name }}
                            </div>
                            <div :style="[{ display: hoverUserMenu ? 'block' : 'none' }]" class="absolute left-14">
                                <div class="p-2 text-sm leading-tight text-white bg-black rounded-md shadow-lg break-keep min-w-16 w-fit">
                                    {{ $t('Edit Profile') }}
                                </div>
                            </div>
                        </MenuButton>
                        <transition enter-active-class="transition-enter-active"
                                    enter-from-class="transition-enter-from"
                                    enter-to-class="transition-enter-to"
                                    leave-active-class="transition-leave-active"
                                    leave-from-class="transition-leave-from"
                                    leave-to-class="transition-leave-to">
                            <MenuItems ref="menuItems" :class="[fullSidenav ? 'ml-40' : 'ml-14', '']" class="z-50 managementMenu rounded-lg max-h-40 overflow-y-auto opacity-100 absolute origin-top-left w-44 shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black focus:outline-none">
                                <div class="z-50">
                                    <MenuItem v-slot="{ active }">
                                        <Link :href="route('user.edit.info', {user: this.$page.props.auth.user.id})"
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

            <main class="main mx-5">
                <PopupChat v-if="$page.props.auth.user.use_chat"/>
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
import {Link, usePage, Head, router} from "@inertiajs/vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {
    IconAdjustmentsAlt, IconBell, IconBuildingWarehouse,
    IconCalendarMonth,
    IconCalendarUser,
    IconCurrencyEuro, IconFileText,
    IconGeometry, IconLayoutDashboard,
    IconListCheck, IconTrash,
    IconUsers, IconCalendarCog, IconCalendarCheck
} from "@tabler/icons-vue";
import IconLib from "@/Mixins/IconLib.vue";
import TextComponent from "@/Components/Inputs/TextInputComponent.vue";
import NumberComponent from "@/Components/Inputs/NumberInputComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import DateComponent from "@/Components/Inputs/DateInputComponent.vue";
import Linkifyit from 'linkify-it';
import PopupChat from "@/Components/Chat/PopupChat.vue";

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
        PopupChat,
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
        TrashIcon, Head,
        IconCalendarCog,
        IconCalendarCheck
    },
    methods: {
        IconBell,
        IconTrash,
        IconAdjustmentsAlt,
        usePage,
        moduleIsVisible(module) {
            return this.hasAdminRole() || this.$page.props.module_settings[module];
        },
        useProjectTimePeriodAndRedirect(e, handleMiddleClick = false) {
            //in safari if we click with middle-click on the menu item the click event is also triggered
            //if button === 1 (mousewheel click) we return as this method is called by "handleMiddleClick" before
            if (e?.button === 1) {
                return;
            }

            let desiredRoute = route('user.calendar_settings.toggle_calendar_settings_use_project_period');
            let payload = {
                use_project_time_period: false,
                project_id: 0,
                is_axios: handleMiddleClick
            };

            if (handleMiddleClick) {
                axios.patch(desiredRoute, payload);

                return;
            }

            router.patch(desiredRoute, payload);
        },
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
        },
        linkifyBody() {
            const bodyElement = document.body,
                linkify = new Linkifyit();

            function replaceTextWithLinks(element) {
                element.childNodes.forEach((node) => {
                    if (node.nodeType === Node.TEXT_NODE) {
                        const text = node.textContent;
                        const matches = linkify.match(text);

                        if (matches) {
                            const fragment = document.createDocumentFragment();
                            let lastIndex = 0;

                            matches.forEach((match) => {
                                if (match.index > lastIndex) {
                                    fragment.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
                                }

                                const link = document.createElement('a');
                                link.href = match.url;
                                link.target = '_blank';
                                link.rel = 'noopener noreferrer';
                                link.textContent = match.text;
                                fragment.appendChild(link);

                                lastIndex = match.lastIndex;
                            });

                            if (lastIndex < text.length) {
                                fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
                            }

                            node.replaceWith(fragment);
                        }
                    } else if (node.nodeType === Node.ELEMENT_NODE) {
                        replaceTextWithLinks(node);
                    }
                });
            }

            replaceTextWithLinks(bodyElement);
        },
        showToolTipForItem(item) {
            if (!this.fullSidenav){
                item.showToolTipForItem = true;
            }
        },
        hideToolTipForItem(item) {
            item.showToolTipForItem = false;
        },
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
                    has_permission: this.$can('change tool settings') || this.hasAdminRole(),
                    name: 'Tool Settings',
                    href: route('tool.branding'),
                    isCurrent: route().current('tool.branding') ||
                        route().current('tool.communication-and-legal') ||
                        route().current('tool.interfaces')
                },
                {
                    has_permission: this.hasAdminRole(),
                    name: 'Shift settings',
                    href: route('shift.settings'),
                    isCurrent: route().current('shift.settings')
                },
                {
                    has_permission: this.hasAdminRole(),
                    name: 'Manufacturers',
                    href: route('manufacturers.index'),
                    isCurrent: route().current('manufacturers.index')
                },
                {
                    has_permission: this.hasAdminRole(),
                    name: 'Inventory',
                    href: route('inventory-management.settings.index'),
                    isCurrent: route().current('inventory-management.settings.index')
                },
                {
                    name: 'Rooms',
                    has_permission: this.$can('create, delete and update rooms') || this.hasAdminRole(),
                    href: route('areas.management'),
                    isCurrent: route().current('areas.management')
                },
                {
                    name: 'Projects',
                    has_permission: this.$can('change project settings') || this.hasAdminRole(),
                    href: route('project.settings'),
                    isCurrent: route().current('project.settings')
                },
                {
                    name: 'Calendar',
                    has_permission: this.hasAdminRole(),
                    href: route('calendar.settings'),
                    isCurrent: route().current('calendar.settings')
                },
                {
                    name: 'Events',
                    has_permission: this.$can('change event settings') || this.hasAdminRole(),
                    href: route('event_types.management'),
                    isCurrent: route().current('event_types.management')
                },
                {
                    name: 'Checklists',
                    has_permission: this.$can('admin checklistTemplates') || this.hasAdminRole(),
                    href: route('checklist_templates.management'),
                    isCurrent: route().current('checklist_templates.management')
                },
                {
                    name: 'Sources of funding',
                    has_permission: this.hasAdminRole(),
                    href: route('money_sources.settings'),
                    isCurrent: route().current('money_sources.settings')
                },
                {
                    name: 'Budget',
                    has_permission: this.$canAny(
                        [
                            'can manage global project budgets',
                            'can manage all project budgets without docs',
                            'view budget templates',
                            'edit budget templates'
                        ]
                    ) || this.hasAdminRole(),
                    href: desiredBudgetRoute,
                    isCurrent: route().current('budget-settings.general') ||
                        route().current('budget-settings.account-management') ||
                        route().current('budget-settings.templates')
                },
                {
                    has_permission: usePage().props.isNotionKeySet,
                    name: 'Updates',
                    href: route('notion.index'),
                    isCurrent: route().current('notion.index')
                },
            ]
        },
        computedWindowInnerHeight() {
            return this.windowInnerHeight;
        }
    },
    mounted() {
        this.linkifyBody();

        let ev = document.createEvent("Event");
        ev.initEvent("DOMContentLoaded", true, true);
        window.document.dispatchEvent(ev);
        this.$i18n.locale = this.$page.props.selected_language;
        document.documentElement.lang = this.$page.props.selected_language;
        Echo.private('App.Models.User.' + this.$page.props.auth.user.id)
            .notification((notification) => {
                this.pushNotifications.push(notification.message);
                setTimeout(() => {
                    this.closePushNotification(notification.message.id)
                }, 3000)
            });

        window.addEventListener('resize', () => {
            this.windowInnerHeight = window.innerHeight;
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
            testModel4: '',
            windowInnerHeight: window.innerHeight,
            navigation: [{
                name: 'Dashboard',
                href: route('dashboard'),
                route: ['/dashboard'],
                has_permission: true,
                icon: IconLayoutDashboard,
                showToolTipForItem: false
            },
                {
                    name: 'Projects',
                    href: route('projects'),
                    route: ['/projects'],
                    has_permission: this.moduleIsVisible('projects'),
                    icon: IconGeometry,
                    showToolTipForItem: false
                },
                {
                    name: 'Calendar',
                    href: route('events'),
                    route: ['/calendar/view'],
                    desiredClickHandler: this.useProjectTimePeriodAndRedirect,
                    has_permission: this.moduleIsVisible('room_assignment'),
                    icon: IconCalendarMonth,
                    showToolTipForItem: false
                },
                {
                    name: 'Shift plan',
                    href: route('shifts.plan'),
                    route: ['/shifts/view'],
                    has_permission: this.moduleIsVisible('shift_plan') &&
                        this.$can('can view shift plan')  || this.hasAdminRole(),
                    icon: IconCalendarUser,
                    showToolTipForItem: false
                },
                {
                    name: 'Planning Calendar',
                    href: route('planning-event-calendar.index'),
                    route: ['/planning-event-calendar'],
                    has_permission: true,
                    icon: IconCalendarCog,
                    showToolTipForItem: false
                },
                {
                    name: 'Event Verifications',
                    href: route('event-verifications.index'),
                    route: ['/event-verifications'],
                    has_permission: true,
                    icon: IconCalendarCheck,
                    showToolTipForItem: false
                },
                {
                    name: 'Inventory',
                    href: route('inventory-management.inventory'),
                    route: ['/inventory-management', '/inventory-management/scheduling'],
                    has_permission: this.moduleIsVisible('inventory'),
                    icon: IconBuildingWarehouse,
                    showToolTipForItem: false
                },
                {
                    name: 'Inventory',
                    href: route('inventory.index'),
                    route: ['inventory'],
                    has_permission: this.moduleIsVisible('inventory'),
                    icon: IconBuildingWarehouse,
                    showToolTipForItem: false
                },
                {
                    name: 'To-dos',
                    href: route('tasks.own'),
                    route: ['/tasks/own'],
                    has_permission: this.moduleIsVisible('tasks'),
                    icon: IconListCheck,
                    showToolTipForItem: false
                },
                {
                    name: 'Sources of funding',
                    href: route('money_sources.index'),
                    route: ['/money_sources'],
                    has_permission: this.moduleIsVisible('sources_of_funding') && this.$canAny(
                        ['view edit add money_sources', 'can edit and delete money sources']
                    )  || this.hasAdminRole(),
                    icon: IconCurrencyEuro,
                    showToolTipForItem: false
                },
                {
                    name: 'Users',
                    href: route('users'),
                    route: ['/users'],
                    has_permission: this.moduleIsVisible('users'),
                    icon: IconUsers,
                    showToolTipForItem: false
                },
                {
                    name: 'Contracts',
                    href: route('contracts.index'),
                    route: ['/contracts/view'],
                    has_permission: this.moduleIsVisible('contracts') &&
                        this.$canAny(['view edit upload contracts', 'can see and download contract modules']) || this.hasAdminRole(),
                    icon: IconFileText,
                    showToolTipForItem: false
                }],
            hoverManagementMenu: false,
            hoverUserMenu: false,
            hoverTrashMenu: false,
            hoverNotificationsMenu: false,
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
        },
    },
}

</script>

<style>

</style>
