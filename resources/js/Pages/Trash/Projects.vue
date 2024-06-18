<template>
    <div class="flex w-full justify-between">
        <div>

        </div>
        <div class="flex justify-end items-center ml-8 -mt-14">
            <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                 class="cursor-pointer inset-y-0 mr-3">
                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
            </div>
            <div v-else class="flex items-center w-64 mr-2">
                <div>
                    <input type="text"
                           :placeholder="$t('Search')"
                           v-model="searchText"
                           class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
            </div>
        </div>
    </div>
    <div v-if="filteredTrashedProjects.length > 0" v-for="(project,index) in filteredTrashedProjects" :key="project.id"
         class="mt-5 border-b-2 border-gray-200 w-full">
        <div class="py-5 flex justify-between">
            <div class="flex">
                <div class="w-full mr-6">
                    <div class="flex my-auto">
                        <p class="text-2xl subpixel-antialiased text-gray-900">{{ project.name }}</p>
                        {{ project.access_budget }}
                    </div>
                </div>
            </div>
            <div class="flex ml-20">
                <div class="my-auto -mr-3" v-for="department in project.departments.slice(0,3)">
                    <TeamIconCollection class="h-9 w-9 rounded-full ring-2 ring-white"
                                        :iconName="department.svg_name"
                                        alt=""/>

                </div>
                <div v-if="project.departments.length >= 4" class="my-auto">
                    <Menu as="div" class="relative">
                        <div>
                            <MenuButton class="flex items-center rounded-full focus:outline-none">
                                <ChevronDownIcon
                                    class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <MenuItem v-for="department in project.departments" v-slot="{ active }">
                                    <div
                                        :class="[active ? 'bg-artwork-navigation-color/10 text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TeamIconCollection
                                            class="h-9 w-9 rounded-full ring-2 ring-white"
                                            :iconName="department.svg_name"
                                            alt=""/>
                                        <span class="ml-4">
                                                                {{ department.name }}
                                                            </span>
                                    </div>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
            <div class="flex">
                <div class="flex mr-8">
                    <div class="my-auto -mr-3" v-for="user in project.users.slice(0,3)">
                        <UserPopoverTooltip :user="user" :id="user.id" height="9" width="9"/>
                    </div>
                    <div v-if="project.users.length >= 4" class="my-auto">
                        <Menu as="div" class="relative">
                            <div>
                                <MenuButton class="flex items-center rounded-full focus:outline-none">
                                    <ChevronDownIcon
                                        class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <MenuItem v-for="user in project.users" v-slot="{ active }">
                                        <div
                                            :class="[active ? 'bg-artwork-navigation-color/10 text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <img class="h-9 w-9 rounded-full"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                        </div>
                                    </MenuItem>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>

                <BaseMenu>
                    <MenuItem v-slot="{ active }">
                        <Link as="button" method="patch"
                              :href="route('projects.restore', { id: project.id })"
                              :class="[active ? 'bg-artwork-navigation-color/10 text-white' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                            <RefreshIcon
                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                aria-hidden="true"/>
                            {{  $t('Restore') }}
                        </Link>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <Link as="button" method="delete"
                              :href="route('projects.force', { id: project.id })"
                              :class="[active ? 'bg-artwork-navigation-color/10 text-white' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                            <TrashIcon
                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                aria-hidden="true"/>
                            {{ $t('Delete permanently')}}
                        </Link>
                    </MenuItem>
                </BaseMenu>
            </div>

        </div>
        <div class="mb-4 subpixel-antialiased text-secondary text-xs flex items-center"
             v-if="project.project_history.length">
            <div>
                {{ $t('last modified') }}:
            </div>
            <UserPopoverTooltip v-if="project.project_history[0]?.changes[0]?.changed_by"
                                :user="project.project_history[0].changes[0].changed_by"
                                :id="project.project_history[0].changes[0].changed_by.id"
                                height="4"
                                width="4" class="ml-2"/>
            <span class="ml-2 subpixel-antialiased">
                        {{ project.project_history[0]?.created_at }}
                    </span>
            <button class="ml-4 subpixel-antialiased text-artwork-buttons-create flex items-center cursor-pointer"
                    @click="openProjectHistoryModal(project)">
                <ChevronRightIcon
                    class="-mr-0.5 h-4 w-4  group-hover:text-white"
                    aria-hidden="true"/>
                {{ $t('View history') }}
            </button>
        </div>

        <!-- Project History Modal -->
        <!-- TODO: EINFÜGEN WENN PROJECT HISTORY VON GELÖSCHTEN PROJEKTEN ÜBERARBEITET -->
        <project-history-component
            @closed="closeProjectHistoryModal"
            v-if="showProjectHistory"
            :project_history="projectHistoryToDisplay"
            :access_budget="projectBudgetAccess"
        ></project-history-component>


    </div>

</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {MenuButton, Menu, MenuItems, MenuItem} from "@headlessui/vue";
import {
    ChevronDownIcon,
    DotsVerticalIcon,
    ChevronRightIcon,
    XIcon,
    RefreshIcon,
    SearchIcon
} from "@heroicons/vue/solid";
import {TrashIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {Link} from "@inertiajs/vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default {
    props: ['trashed_projects'],
    name: "Projects",
    layout: [AppLayout, TrashLayout],
    data() {
        return {
            showProjectHistory: false,
            projectHistoryToDisplay: [],
            projectBudgetAccess: {},
            showSearchbar: false,
            searchText: '',
        }
    },
    computed: {
        filteredTrashedProjects() {
            if (this.searchText === '') {
                return this.trashed_projects;
            }
            return this.trashed_projects.filter(project => {
                return project.name.toLowerCase().includes(this.searchText.toLowerCase())
            })
        }
    },
    methods: {
        openProjectHistoryModal(project) {
            this.projectHistoryToDisplay = project.project_history;
            this.projectBudgetAccess = project.access_budget;
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
            this.projectHistoryToDisplay = [];
        },
        closeSearchbar() {
            this.showSearchbar = false
            this.searchText = ''
        }
    },
    components: {
        BaseMenu,
        UserPopoverTooltip,
        Input, SearchIcon,
        ProjectHistoryComponent,
        TrashIcon,
        TeamIconCollection,
        MenuButton,
        ChevronDownIcon,
        Menu, MenuItems, MenuItem, DotsVerticalIcon,
        SvgCollection, ChevronRightIcon, JetDialogModal,
        XIcon,
        RefreshIcon,
        Link,
        UserTooltip
    }
}
</script>

<style scoped>

</style>
