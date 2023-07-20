<template>
    <app-layout>
        <div v-if="$page.props.jetstream.canUpdateProfileInformation">
            <div>
                <div class="max-w-screen-lg py-4 pl-20 pr-4">
                    <div class="flex">
                        <img class="mt-6 h-16 w-16 rounded-full flex justify-start object-cover"
                             :src="user_to_edit.profile_photo_url"
                             alt=""/>
                        <div class="mt-6 flex flex-grow w-full">
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
                                    <div v-for="tab in tabs" :key="tab.name" @click="changeTab(tab.id)"
                                         :class="[tab.current ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']"
                                         :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}
                                    </div>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="">
                    <div class="w-full pl-20 pr-4">
                        <slot></slot>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>


import ProjectShowHeaderComponent from "@/Pages/Projects/Components/ProjectShowHeaderComponent.vue";
import InfoTab from "@/Pages/Projects/Components/TabComponents/InfoTab.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import ProjectSecondSidenav from "@/Layouts/Components/ProjectSecondSidenav.vue";
import ProjectShiftSidenav from "@/Layouts/Components/ProjectShiftSidenav.vue";
import ProjectSidenav from "@/Layouts/Components/ProjectSidenav.vue";
import {Inertia} from "@inertiajs/inertia";

export default {
    components: {
        ProjectSidenav,
        ProjectShiftSidenav,
        ProjectSecondSidenav,
        BaseSidenav,
        AppLayout, InfoTab, ProjectShowHeaderComponent
    },
    props: [
        'user_to_edit',
        'currentTab',

    ],
    data() {
        return {
            show: false,
            tabs: [
                {id: 1, name: 'Einsatzplan', href: '#', current: this.currentTab === 'shiftplan'},
                {id: 2, name: 'Konditionen', href: '#', current: this.currentTab === 'terms'},
                {id: 3, name: 'Persönliche Daten', href: '#', current: this.currentTab === 'info'},
                {id: 4, name: 'Nutzerrechte', href: '#', current: this.currentTab === 'permissions'},
            ],
            currentTab: 1,
        }
    },
    mounted() {
        this.show = true;
        setTimeout(() => {
            this.show = false;
        }, 1000)
    },
    methods: {
        changeTab(selectedTab) {
            if (selectedTab === 1) {
                Inertia.get(route('user.edit.shiftplan', {user: this.user_to_edit.id}));
            } else if (selectedTab === 2) {
                Inertia.get(route('user.edit.terms', {user: this.user_to_edit.id}));
            } else if (selectedTab === 3) {
                Inertia.get(route('user.edit.info', {user: this.user_to_edit.id}));
            } else if (selectedTab === 4) {
                Inertia.get(route('user.edit.permissions', {user: this.user_to_edit.id}));
            } else {
                Inertia.get(route('user.edit.shiftplan', {user: this.user_to_edit.id}));
            }
        },
    }
}
</script>


<style scoped>

</style>
