<template>
    <app-layout :title="$t(title)">
        <div v-if="$page.props.jetstream.canUpdateProfileInformation" class="mx-auto container">
            <div>
                <div class="mt-5 pr-4">
                    <div class="headline1 mb-5" v-if="user_to_edit.id === $page.props.auth.user.id">
                        {{ $t('My account')}}
                    </div>
                    <div class="flex">
                        <img class=" h-16 w-16 rounded-full flex justify-start object-cover"
                             :src="user_to_edit.profile_photo_url"
                             alt=""/>
                        <div class="flex flex-grow w-full">
                            <div class="headline1 flex my-auto ml-6">
                                {{ user_to_edit.first_name }}
                            </div>
                            <div class="headline1 flex my-auto ml-2">
                                {{ user_to_edit.last_name }}
                            </div>
                        </div>
                    </div>
                    <div class="my-10">
                        <div class="hidden sm:block">
                            <div class="">
                                <nav class="-mb-px flex space-x-8 uppercase xxsDark" aria-label="Tabs">
                                    <a v-for="tab in tabs" v-show="tab.has_permission" :href="tab.href" :key="tab.name"
                                         :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']"
                                         :aria-current="tab.current ? 'page' : undefined">{{ $t(tab.name) }}
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="">
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
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "UserEditHeader",
    components: {
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
                {id: 1, name: 'Operational plan', href: route('user.edit.shiftplan', {user: this.user_to_edit.id}), current: route().current('user.edit.shiftplan'), has_permission: this.$can('can plan shifts') || this.hasAdminRole()},
                {id: 2, name: 'Conditions', href: route('user.edit.terms', {user: this.user_to_edit.id}), current: route().current('user.edit.terms'), has_permission: this.$can('can manage workers') || this.hasAdminRole()},
                {id: 3, name: 'Personal data', href: route('user.edit.info', {user: this.user_to_edit.id}), current: route().current('user.edit.info'), has_permission: true},
                {id: 4, name: 'User permissions', href: route('user.edit.permissions', {user: this.user_to_edit.id}), current: route().current('user.edit.permissions'), has_permission: this.hasAdminRole()},
                {id: 5, name: 'Work profile', href: route('user.edit.workProfile', {user: this.user_to_edit.id}), current: route().current('user.edit.workProfile'), has_permission: this.$can('can manage workers') || this.hasAdminRole()},
                {id: 5, name: 'Work Time Pattern', href: route('user.edit.work-time-pattern', {user: this.user_to_edit.id}), current: route().current('user.edit.work-time-pattern'), has_permission: this.$can('can manage workers') || this.hasAdminRole()},
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
