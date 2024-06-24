<template>
    <app-layout :title="title">
        <div v-if="$page.props.jetstream.canUpdateProfileInformation">
            <div>
                <div class="max-w-screen-lg pl-14 pr-4">
                    <div class="headline1 mb-5" v-if="user_to_edit.id === $page.props.user.id">
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
                                    <div v-for="tab in tabs" v-show="tab.has_permission" :key="tab.name" @click="changeTab(tab.id)"
                                         :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']"
                                         :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}
                                    </div>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="">
                    <div class="w-full pl-14 pr-4">
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
                {id: 1, name: this.$t('Operational plan'), href: '#', current: this.currentTab === 'shiftplan', has_permission: this.$can('can plan shifts') || this.hasAdminRole()},
                {id: 2, name: this.$t('Conditions'), href: '#', current: this.currentTab === 'terms', has_permission: this.$can('can manage workers') || this.hasAdminRole()},
                {id: 3, name: this.$t('Personal data'), href: '#', current: this.currentTab === 'info', has_permission: true},
                {id: 4, name: this.$t('User permissions'), href: '#', current: this.currentTab === 'permissions', has_permission: this.hasAdminRole()},
                {id: 5, name: this.$t('Work profile'), href: '#', current: this.currentTab === 'workProfile', has_permission: this.$can('can manage workers') || this.hasAdminRole()},
            ],
            title: null
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
    mounted() {
        if(this.user_to_edit.id === this.$page.props.user.id){
            this.title = this.$t('My account')
        } else {
            this.title = this.$t('User account') + ' - ' + this.user_to_edit.first_name + ' ' + this.user_to_edit.last_name
        }
    }
}
</script>


<style scoped>

</style>
