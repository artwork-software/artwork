<template>
    <app-layout :title="$t(title)">
        <div v-if="$page.props.jetstream.canUpdateProfileInformation" class="mx-auto container">
            <div>
                <div class="mt-5 sticky top-0 card white p-5 z-100 mb-5">
                    <div class="headline1 mb-5" v-if="user_to_edit.id === $page.props.auth.user.id">
                        {{ $t('My account')}}
                    </div>
                    <div class="flex items-center gap-4">
                        <img class="h-16 w-16 rounded-full flex justify-start object-cover"
                             :src="user_to_edit.profile_photo_url"
                             alt=""/>
                        <div>
                            <div class="flex flex-grow w-full gap-x-1">
                                <div class="headline1">
                                    {{ user_to_edit.first_name }}
                                </div>
                                <div class="headline1">
                                    {{ user_to_edit.last_name }}
                                </div>
                            </div>
                            <p class="text-gray-500 text-sm">{{ user_to_edit.email }}</p>
                        </div>

                    </div>
                    <div class="mt-5">
                        <div class="hidden sm:block">
                            <div class="artwork">
                                <nav class="-mb-px flex space-x-8 font-lexend text-sm" aria-label="Tabs">
                                    <Link v-for="tab in tabs" v-show="tab.has_permission" :href="tab.href" :key="tab.name" :preserve-state="false"
                                         :class="[tab.current ? 'card glassy !flex !flex-row !w-fit !flex-grow-0 px-3 !border-gray-200' : 'border-transparent', 'py-2 px-1 cursor-pointer flex items-center gap-x-2']"
                                         :aria-current="tab.current ? 'page' : undefined">
                                        <component :is="tab.icon" class="inline-block h-5 w-5 text-artwork-buttons-create" aria-hidden="true"/>
                                        {{ $t(tab.name) }}
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card white p-5">
                    <div class="w-full">
                        <slot></slot>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import InfoTab from "@/Pages/Projects/Tab/Components/InfoTab.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import ProjectSecondSidenav from "@/Layouts/Components/ProjectSecondSidenav.vue";
import ProjectShiftSidenav from "@/Layouts/Components/ProjectShiftSidenav.vue";
import {Link, router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "UserEditHeader",
    components: {
        Link,
        ProjectShiftSidenav,
        ProjectSecondSidenav,
        BaseSidenav,
        AppLayout,
        InfoTab
    },
    props: [
        'user_to_edit',
        'currentTab',
    ],
    data() {
        return {
            show: false,
            tabs: [
                {id: 1, name: 'Operational plan', href: route('user.edit.shiftplan', {user: this.user_to_edit.id}), current: route().current('user.edit.shiftplan'), has_permission: this.$can('can plan shifts') || this.hasAdminRole(), icon: 'IconCalendarUser'},
                {id: 2, name: 'Conditions', href: route('user.edit.terms', {user: this.user_to_edit.id}), current: route().current('user.edit.terms'), has_permission: this.$can('can manage workers') || this.hasAdminRole(), icon: 'IconTaxEuro'},
                {id: 3, name: 'Personal data', href: route('user.edit.info', {user: this.user_to_edit.id}), current: route().current('user.edit.info'), has_permission: true, icon: 'IconUser'},
                {id: 4, name: 'User permissions', href: route('user.edit.permissions', {user: this.user_to_edit.id}), current: route().current('user.edit.permissions'), has_permission: this.hasAdminRole(), icon: 'IconLicense'},
                {id: 5, name: 'Work profile', href: route('user.edit.workProfile', {user: this.user_to_edit.id}), current: route().current('user.edit.workProfile'), has_permission: this.$can('can manage workers') || this.hasAdminRole(), icon: 'IconBriefcase'},
                {id: 5, name: 'Work Time Pattern', href: route('user.edit.work-time-pattern', {user: this.user_to_edit.id}), current: route().current('user.edit.work-time-pattern'), has_permission: this.$can('can manage workers') || this.hasAdminRole(), icon: 'IconClockHour10'},
                {id: 5, name: 'Employment contract', href: route('user.edit.contract', {user: this.user_to_edit.id}), current: route().current('user.edit.contract'), has_permission: this.$can('can manage workers') || this.hasAdminRole(), icon: 'IconContract'},
            ],
            title: this.user_to_edit.id === this.$page.props.auth.user.id ? 'My account' : 'User account' + ' - ' + this.user_to_edit.first_name + ' ' + this.user_to_edit.last_name
        }
    },
    methods: {
        changeTab(selectedTab) {
            if (selectedTab === 1) {
                router.get(route('user.edit.shiftplan', {user: this.user_to_edit.id}));
            } else if (selectedTab === 2) {
                router.get(route('user.edit.terms', {user: this.user_to_edit.id}));
            } else if (selectedTab === 3) {
                router.get(route('user.edit.info', {user: this.user_to_edit.id}));
            } else if (selectedTab === 4) {
                router.get(route('user.edit.permissions', {user: this.user_to_edit.id}));
            } else if (selectedTab === 5) {
                router.get(route('user.edit.workProfile', {user: this.user_to_edit.id}));
            } else {
                router.get(route('user.edit.shiftplan', {user: this.user_to_edit.id}));
            }
        },
    },
}
</script>


<style scoped>

</style>
