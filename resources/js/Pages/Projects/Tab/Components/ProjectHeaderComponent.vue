<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import {Link} from "@inertiajs/vue3";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {router} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import {XIcon} from "@heroicons/vue/outline";
import Button from "@/Jetstream/Button.vue";
import ProjectGroupAddProject from "@/Pages/Projects/Components/Modals/ProjectGroupAddProject.vue";
import {nextTick} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import PrintLayoutSelectorModal from "@/Pages/Projects/Components/PrintLayoutSelectorModal.vue";
import ProjectStateChangeModal from "@/Layouts/Components/ProjectStateChangeModal.vue";

export default {
    name: "ProjectHeaderComponent",
    mixins: [Permissions, IconLib, ColorHelper],
    components: {
        ProjectStateChangeModal,
        PrintLayoutSelectorModal,
        ToolTipComponent,
        ProjectGroupAddProject,
        Button, XIcon,
        ProjectCreateModal,
        TagComponent,
        BaseModal,
        BaseMenu,
        UserPopoverTooltip,
        JetDialogModal, ProjectHistoryComponent, ProjectDataEditModal,
        Link,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
    },
    props: {
        headerObject: {
            type: Object,
            required: true
        },
        inSidebar: {
            type: Boolean,
            required: false
        },
        project: {
            type: Object,
            required: true
        },
        currentTab: {
            type: Object,
            required: true
        },
        createSettings: {
            type: Object,
            required: false
        },
        first_project_tab_id: {
            type: Number,
            required: false
        },
        printLayouts: {
            type: Object,
            required: false
        }
    },
    data() {
        return {
            showProjectHistory: false,
            editingProject: false,
            deletingProject: false,
            projectToDelete: null,
            showAddProjectToGroup: false,
            showPrintLayoutSelectorModal: false,
            showProjectStateChangeModal: false,
        }
    },
    computed: {
        projectState(){
            return this.headerObject.states.find(state => state.id === this.project?.state)
        }
    },
    methods: {
        hasBudgetAccess() {
            return this.access_budget.filter((user) => user.id === this.$page.props.auth.user.id).length > 0;
        },
        openProjectHistoryModal() {
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
        },
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        },
        duplicateProject(project) {
            this.$inertia.post(`/projects/${project.id}/duplicate`);
        },
        openDeleteProjectModal(project) {
            this.projectToDelete = project;
            this.deletingProject = true;
        },
        closeDeleteProjectModal() {
            this.deletingProject = false;
            this.projectToDelete = null;
        },
        deleteProjectFromGroup(projectGroupId) {
            router.delete(route('projects.group.delete', {
                project: this.project.id,
                projectGroup: projectGroupId
            }), {
                onSuccess: () => {
                    this.headerObject.projectGroups.splice(this.headerObject.projectGroups.findIndex(index => index.id === projectGroupId.id), 1)
                }
            });
        },
        deleteProject() {
            this.nameOfDeletedProject = this.projectToDelete.name;
            router.delete(`/projects/${this.projectToDelete.id}`);
            this.closeDeleteProjectModal();
        },
        locationString() {
            return Object.values(this.headerObject.roomsWithAudience).join(", ");
        },
        openProjectStateChangeModal() {
            this.showProjectStateChangeModal = true;
        },

    }
}
</script>

<template>
    <AppLayout :title="project?.name + ' (' + currentTab.name + ')'">
        <!-- Project Header -->
        <div class="px-10">
            <div class="mt-5">
                <div class="flex flex-col">
                    <!-- if in group -->
                    <div v-if="project?.groups?.length > 0" class="bg-secondaryHover text-sm shadow-sm border border-gray-200 px-3 py-1 rounded-lg -mb-2 z-20 w-fit pr-6 pb-0.5">
                        <div class="flex items-center">
                        <span v-if="!project?.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true"/>
                        </span>
                            {{ $t('Belongs to') }}
                            <span v-for="group in project.groups">
                            <a :href="route('projects.tab', {project: group?.id, projectTab: first_project_tab_id})" class="text-artwork-buttons-create ml-1">
                                {{ group?.name }}
                            </a>,
                        </span>

                        </div>
                    </div>
                    <div class="hidden">
                        <div class="flex z-10" v-if="project?.key_visual_path !== null">
                            <img :src="'/storage/keyVisual/' + project?.key_visual_path"
                                 :alt="$t('Current key visual')"
                                 class="rounded-md mx-auto h-[200px]">
                        </div>
                        <div v-else class="w-full h-40 bg-gray-200 flex justify-center items-center">
                            <img src="/images/place.png" :alt="$t('Current key visual')"
                                 class="rounded-md h-[200px]">
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl items-center">
                        <span v-if="project?.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-6 w-6 mr-2" aria-hidden="true"/>
                        </span>
                            <span v-if="project?.key_visual_path !== null">
                            <img :src="'/storage/keyVisual/' + project?.key_visual_path"
                                 :alt="$t('Current key visual')"
                                 class="mx-auto size-10 rounded-full object-cover mr-2 hover:size-24 duration-300 ease-in-out">
                        </span>
                            {{ project?.name }}

                            <span v-if="projectState" class="rounded-full items-center font-medium px-3 py-1 text-sm ml-2 mb-1 inline-flex border" :style="{backgroundColor: backgroundColorWithOpacity(projectState.color), color: TextColorWithDarken(projectState.color), borderColor: TextColorWithDarken(projectState.color)}">
                            {{ projectState.name }}
                        </span>
                        </h2>
                        <div class="flex items-center justify-center gap-x-4">
                            <ToolTipComponent :tooltip-text="$t('Select print layout')" icon="IconPrinter" :direction="'bottom'" @click="showPrintLayoutSelectorModal = true" stroke="2" />

                            <BaseMenu v-if="$can('write projects') || $role('artwork admin') || headerObject.projectManagerIds.includes(this.$page.props.auth.user.id) || headerObject.projectWriteIds.includes(this.$page.props.auth.user.id)">
                                <MenuItem
                                    v-if="$role('artwork admin') || headerObject.projectWriteIds.includes(this.$page.props.auth.user.id) || headerObject.projectManagerIds.includes(this.$page.props.auth.user.id) || $can('write projects')"
                                    v-slot="{ active }">
                                    <a @click="openEditProjectModal"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                        <IconEdit stroke-width="1.5"
                                                  class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                  aria-hidden="true"/>
                                        {{ $t('Edit basic data') }}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="duplicateProject(this.project)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                        <IconCopy stroke-width="1.5"
                                                  class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                  aria-hidden="true"/>
                                        {{ $t('Duplicate') }}
                                    </a>
                                </MenuItem>
                                <MenuItem
                                    v-if="headerObject.projectDeleteIds.includes(this.$page.props.auth.user.id) ||$role('artwork admin')"
                                    v-slot="{ active }">
                                    <a @click="openDeleteProjectModal(this.project)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                        <IconTrash stroke-width="1.5"
                                                   class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                   aria-hidden="true"/>
                                        {{ $t('Put in the trash') }}
                                    </a>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </div>

                    <div class="my-3" v-if="headerObject.projectsOfGroup.length > 0">
                        <div class="text-secondary xsDark">
                            {{ $t('Projects in this group') }}:
                        </div>
                        <div class="mt-2 inline-flex gap-2">
                            <div v-for="(groupProject, index) in headerObject.projectsOfGroup" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                                <div class="flex items-center">
                                    <a :href="route('projects.tab', {project: groupProject?.id, projectTab: first_project_tab_id})">
                                        <img class="inline-block size-9 rounded-full object-cover" :src="groupProject?.key_visual_path ? '/storage/keyVisual/' + groupProject?.key_visual_path : '/storage/logo/artwork_logo_small.svg'" alt="" />
                                    </a>
                                    <a :href="route('projects.tab', {project: groupProject?.id, projectTab: first_project_tab_id})" class="mx-2">
                                        <p class="xsDark group-hover:text-gray-900">{{ groupProject.name}}</p>
                                    </a>
                                    <div class="flex items-center">
                                        <button type="button" @click="deleteProjectFromGroup(groupProject.id)">
                                            <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300 cursor-pointer hover:bg-gray-200 duration-300 ease-in-out" @click="showAddProjectToGroup = true" v-if="$role('artwork admin') || headerObject.projectWriteIds.includes(this.$page.props.auth.user.id) || headerObject.projectManagerIds.includes(this.$page.props.auth.user.id) || $can('write projects')">
                                <div class="flex items-center">
                                    <div>
                                        <img class="inline-block size-9 rounded-full object-cover" src="/storage/logo/artwork_logo_small.svg" alt="" />
                                    </div>
                                    <div class="mx-2">
                                        <p class="xsDark group-hover:text-gray-900">{{ $t('Add projects to group') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="headerObject.projectsOfGroup.length <= 0 && headerObject.project.is_group" class="my-3">
                        <div class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300 cursor-pointer hover:bg-gray-200 duration-300 ease-in-out" v-if="$role('artwork admin') || headerObject.projectWriteIds.includes(this.$page.props.auth.user.id) || headerObject.projectManagerIds.includes(this.$page.props.auth.user.id) || $can('write projects')" @click="showAddProjectToGroup = true">
                            <div class="flex items-center">
                                <div>
                                    <img class="inline-block size-9 rounded-full object-cover" src="/storage/logo/artwork_logo_small.svg" alt="" />
                                </div>
                                <div class="mx-2">
                                    <p class="xsDark group-hover:text-gray-900">{{ $t('Add projects to group') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full my-1 text-secondary xsDark">
                        <div v-if="headerObject.firstEventInProject && headerObject.lastEventInProject">
                            {{ $t('Time period/opening hours') }}: {{ headerObject.firstEventInProject?.start_time }}
                            <span v-if="headerObject.firstEventInProject?.start_time">{{ $t('Clock') }} -</span>
                            {{ headerObject.lastEventInProject?.end_time }}
                            <span v-if="headerObject.lastEventInProject?.end_time">{{ $t('Clock') }}</span>
                        </div>
                        <div v-if="headerObject.roomsWithAudience.length > 0">
                            {{ $t('Appointments with audience in') }}: <span>{{ locationString() }}</span>
                        </div>
                        <div v-if="headerObject.roomsWithAudience.length <= 0 && !(headerObject.firstEventInProject && headerObject.lastEventInProject)">
                            {{ $t('No appointments within this project yet') }}
                        </div>
                    </div>
                    <div class="mt-2 subpixel-antialiased xxsLight flex items-center" v-if="headerObject.project_history.length">
                        <div>
                            {{ $t('last modified') }}:
                        </div>
                        <UserPopoverTooltip :user="headerObject.project_history[0]?.changer" :id="headerObject.project_history[0]?.changer.id" height="4" width="4" class="ml-2"/>
                        <span class="ml-2 subpixel-antialiased">
                        {{ headerObject.project_history[0]?.created_at }}
                    </span>
                        <button class="ml-4 subpixel-antialiased text-artwork-buttons-create flex items-center cursor-pointer"
                                @click="openProjectHistoryModal()">
                            <IconChevronRight
                                class="-mr-0.5 h-4 w-4  group-hover:text-artwork-buttons-hover"
                                aria-hidden="true"/>
                            {{ $t('View history') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- tabs -->
            <div class="w-full h-full border-b-2 border-dashed pb-5 border-gray-100">
                <div class="font-lexend">
                    <div class="hidden sm:block">
                        <div class="border-gray-200">
                            <nav class="-mb-px text-sm tracking-wide pt-4 flex space-x-12" aria-label="Tabs">
                                <Link v-for="tab in headerObject.tabs" :key="tab?.name"
                                      :href="route('projects.tab', {project: headerObject.project.id, projectTab: tab.id})"
                                      :class="[tab.id === headerObject.currentTabId ? 'border-artwork-buttons-hover text-artwork-buttons-hover' : 'border-transparent hover:text-gray-600 hover:border-gray-300 text-artwork-context-dark', 'whitespace-nowrap py-2 px-1 border-b-2 font-black duration-300 ease-in-out']"
                                      :aria-current="tab.id === headerObject.currentTabId ? 'page' : undefined">
                                    {{ tab.name }}
                                </Link>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tab Content -->
            <div class="">
                <slot />
            </div>

            <project-create-modal
                v-if="editingProject"
                :show="editingProject"
                :categories="headerObject.categories"
                :genres="headerObject.genres"
                :sectors="headerObject.sectors"
                :project-groups="headerObject.projectGroups"
                :states="headerObject.states"
                @close-create-project-modal="closeEditProjectModal"
                @open-project-state-change-modal="openProjectStateChangeModal"
                :create-settings="createSettings"
                :project="project"
            />

            <!--<project-data-edit-modal
                :show="editingProject"
                :project-state="headerObject.projectState"
                @closed="closeEditProjectModal"
                :project="this.headerObject.project"
                :group-projects="this.headerObject.groupProjects"
                :current-group="this.headerObject.currentGroup"
                :states="headerObject.states"
            />-->

            <project-history-component
                @closed="closeProjectHistoryModal"
                v-if="showProjectHistory"
                :project_history="headerObject.project_history"
                :access_budget="headerObject.project.access_budget"
            />
            <BaseModal @closed="closeDeleteProjectModal" v-if="deletingProject" modal-image="/Svgs/Overlays/illu_warning.svg">
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        {{ $t('Delete project') }}
                    </div>
                    <div class="text-error subpixel-antialiased">
                        {{ $t('Are you sure you want to delete the project?', [projectToDelete.name]) }}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-full focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-white"
                                @click="deleteProject">
                            {{ $t('Delete') }}
                        </button>
                        <div class="flex my-auto">
                        <span @click="closeDeleteProjectModal()"
                              class="xsLight cursor-pointer">
                            {{ $t('No, not really') }}
                        </span>
                        </div>
                    </div>
                </div>
            </BaseModal>

            <ProjectGroupAddProject
                :project="headerObject.project"
                v-if="showAddProjectToGroup"
                @close="showAddProjectToGroup = false"
                :projects-in-group="headerObject.projectsOfGroup"
            />

            <PrintLayoutSelectorModal
                :print-layouts="printLayouts" :project="project"
                v-if="showPrintLayoutSelectorModal"
                @close="showPrintLayoutSelectorModal = false"
            />
            <ProjectStateChangeModal :project-id="project.id"
                                     v-if="showProjectStateChangeModal"
                                     @close="showProjectStateChangeModal = false"
            />
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
