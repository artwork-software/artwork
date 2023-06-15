<template>
    <div class="my-12 pl-10 pr-10">
        <div class="flex flex-col" v-if="isProjectMember(project.id)">
            <div v-if="currentGroup" class="bg-secondaryHover -mb-6 z-20 w-fit pr-6 pb-0.5">
                <div class="flex items-center">
                        <span v-if="!project.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true"/>
                        </span>
                    Gehört zu <a :href="'/projects/' + currentGroup.id" class="text-buttonBlue ml-1">
                    {{ currentGroup?.name }}</a>
                </div>
            </div>
            <div class="flex z-10" v-if="this.project.key_visual_path !== null">
                <img :src="'/storage/keyVisual/header_' + this.project.key_visual_path" alt="Aktuelles Key-Visual"
                     class="rounded-md w-full h-[200px]">
            </div>
            <div v-else class="w-full h-40 bg-gray-200 flex justify-center items-center">
                <img src="/Svgs/IconSvgs/placeholder.svg" alt="Aktuelles Key-Visual"
                     class="rounded-md ">
            </div>
            <div class="mt-2 subpixel-antialiased text-secondary text-xs flex items-center"
                 v-if="project.project_history.length">
                <div>
                    zuletzt geändert:
                </div>
                <img v-if="project.project_history[0]?.changes[0]?.changed_by"
                     :data-tooltip-target="project.project_history[0].changes[0].changed_by?.id"
                     :src="project.project_history[0].changes[0].changed_by?.profile_photo_url"
                     :alt="project.project_history[0].changes[0].changed_by?.first_name"
                     class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                <UserTooltip v-if="project.project_history[0]?.changes[0]?.changed_by"
                             :user="project.project_history[0].changes[0].changed_by"/>
                <span class="ml-2 subpixel-antialiased">
                        {{ project.project_history[0]?.created_at }}
                    </span>
                <button class="ml-4 subpixel-antialiased text-buttonBlue flex items-center cursor-pointer"
                        @click="openProjectHistoryModal()">
                    <ChevronRightIcon
                        class="-mr-0.5 h-4 w-4  group-hover:text-white"
                        aria-hidden="true"/>
                    Verlauf ansehen
                </button>
            </div>
            <div class="flex justify-between items-center">
                <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl items-center">
                        <span v-if="project.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-6 w-6 mr-2" aria-hidden="true"/>
                        </span>
                    {{ project?.name }}
                    <span class="rounded-full items-center font-medium px-3 py-1 my-2 text-sm ml-2 mb-1 inline-flex"
                          :class="this.states.find(state => state.id === projectState)?.color">
                            {{ this.states.find(state => state.id === projectState)?.name }}
                        </span>
                </h2>
                <Menu as="div" class="my-auto mt-3 relative"
                      v-if="$can('write projects') || $role('artwork admin') || projectManagerIds.includes(this.$page.props.user.id) || projectCanWriteIds.includes(this.$page.props.user.id)">
                    <div class="flex items-center -mt-1">
                        <MenuButton
                            class="flex bg-tagBg p-0.5 rounded-full">
                            <DotsVerticalIcon
                                class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
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
                            class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem
                                    v-if="$role('artwork admin') || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || $can('write projects')"
                                    v-slot="{ active }">
                                    <a @click="openEditProjectModal"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <PencilAltIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Basisdaten bearbeiten
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="duplicateProject(this.project)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <DuplicateIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Duplizieren
                                    </a>
                                </MenuItem>
                                <MenuItem
                                    v-if="
                                            projectDeletePermissionUsers.includes(this.$page.props.user.id) ||
                                            $role('artwork admin')
                                        "
                                    v-slot="{ active }">
                                    <a @click="openDeleteProjectModal(this.project)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        In den Papierkorb legen
                                    </a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
            <div class="mt-3" v-if="projectGroups.length > 0">
                <TagComponent v-for="projectGroup in projectGroups" :method="deleteProjectFromGroup"
                              :displayed-text="projectGroup.name" :property="projectGroup"></TagComponent>
            </div>

            <div class="w-full mt-5 text-secondary subpixel-antialiased">
                <div v-if="firstEventInProject && lastEventInProject">
                    Zeitraum/Öffnungszeiten: {{ firstEventInProject?.start_time }} <span
                    v-if="firstEventInProject?.start_time">Uhr -</span> {{ lastEventInProject?.end_time }} <span
                    v-if="lastEventInProject?.end_time">Uhr</span>
                </div>
                <div v-if="RoomsWithAudience">
                    Termine mit Publikum in: <span>{{ locationString }}</span>
                </div>
                <div v-if="!RoomsWithAudience && !(firstEventInProject && lastEventInProject)">
                    Noch keine Termine innerhalb dieses Projektes
                </div>
            </div>
        </div>
    </div>
</template>


<script>

import {defineComponent} from "vue";
import {ChevronRightIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {DuplicateIcon, PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";

export default defineComponent({
    components: {TrashIcon, DuplicateIcon, UserTooltip, TagComponent, PencilAltIcon, ChevronRightIcon, DotsVerticalIcon}
})
</script>

<style scoped>

</style>
