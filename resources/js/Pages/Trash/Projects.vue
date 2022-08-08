<template>
    <div v-if="trashed_projects.length > 0" v-for="(project,index) in trashed_projects" :key="project.id"
         class="mt-5 border-b-2 border-gray-200 w-full">
        <div
            class="py-5 flex justify-between">
            <div class="flex">
                <div class="w-full mr-6">
                    <div class="flex my-auto">
                        <p class="text-2xl subpixel-antialiased text-gray-900">{{ project.name }}</p>
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
                                class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <MenuItem v-for="department in project.departments" v-slot="{ active }">
                                    <div
                                        :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
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
                        <img :data-tooltip-target="user.id" class="h-9 w-9 rounded-full ring-2 ring-white"
                             :src="user.profile_photo_url"
                             alt=""/>
                        <UserTooltip :user="user" />
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
                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <MenuItem v-for="user in project.users" v-slot="{ active }">
                                        <div
                                            :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
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

                <Menu as="div" class="my-auto relative">
                    <div class="flex">
                        <MenuButton
                            class="flex">
                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                              aria-hidden="true"/>
                        </MenuButton>
                    </div>
                    <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }">
                                    <Link as="button" method="patch"
                                          :href="route('projects.restore', { id: project.id })"
                                          :class="[active ? 'bg-primaryHover text-white' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                        <RefreshIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Wiederherstellen
                                    </Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <Link as="button" method="delete"
                                          :href="route('projects.force', { id: project.id })"
                                          :class="[active ? 'bg-primaryHover text-white' :
                                          'text-secondary',
                                          'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Entgültig löschen
                                    </Link>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>

            </div>

        </div>
        <div class="mb-2 text-secondary flex items-center">
                    <span class="subpixel-antialiased">
                    zuletzt geändert:
                    </span>
            <div class="flex items-center" v-if="project.project_history.length !== 0">
                <img :data-tooltip-target="project.project_history[0].user.id"
                    :src="project.project_history[0].user.profile_photo_url"
                    :alt="project.project_history[0].user.name"
                    class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                <UserTooltip :user="project.project_history[0].user" />
                <span class="ml-2 subpixel-antialiased">
                                    {{ project.project_history[0].created_at }}
                                </span>
                <button class="ml-4 subpixel-antialiased flex items-center cursor-pointer"
                        @click="openProjectHistoryModal(project.project_history)">
                    <ChevronRightIcon
                        class="-mr-0.5 h-4 w-4 text-primaryText group-hover:text-white"
                        aria-hidden="true"/>
                    Verlauf ansehen
                </button>
            </div>
            <div v-else class="ml-2 text-secondary subpixel-antialiased">
                Noch kein Verlauf verfügbar
            </div>

        </div>
        <jet-dialog-modal :show="showProjectHistory" @close="closeProjectHistoryModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Projektverlauf
                    </div>
                    <XIcon @click="closeProjectHistoryModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary subpixel-antialiased">
                        Hier kannst du nachvollziehen, was von wem wann geändert wurde.
                    </div>
                    <div class="flex w-full flex-wrap mt-4">
                        <div class="flex w-full my-1" v-for="historyItem in projectHistoryToDisplay">
                            <span class="text-secondary my-auto text-sm subpixel-antialiased">
                        {{ historyItem.created_at }}:
                    </span>
                            <img :data-tooltip-target="historyItem.user.id" :src="historyItem.user.profile_photo_url" :alt="historyItem.user.name"
                                 class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                            <UserTooltip :user="historyItem.user" />
                            <div class="text-secondary subpixel-antialiased ml-2 text-sm my-auto">
                                {{ historyItem.description }}
                            </div>
                        </div>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>

    </div>

</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import TrashLayout from "@/Layouts/TrashLayout";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import { MenuButton,Menu, MenuItems, MenuItem } from "@headlessui/vue";
import { ChevronDownIcon, DotsVerticalIcon, ChevronRightIcon, XIcon, RefreshIcon } from "@heroicons/vue/solid";
import { TrashIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetDialogModal from "@/Jetstream/DialogModal";
import {Link} from "@inertiajs/inertia-vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip";
export default {
    props: ['trashed_projects'],
    name: "Projects",
    layout: [AppLayout, TrashLayout],
    data() {
      return {
          showProjectHistory: false,
          projectHistoryToDisplay: []
      }
    },
    methods: {
        openProjectHistoryModal(projectHistory) {
            this.projectHistoryToDisplay = projectHistory;
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
            this.projectHistoryToDisplay = [];
        }
    },
    components: {
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
