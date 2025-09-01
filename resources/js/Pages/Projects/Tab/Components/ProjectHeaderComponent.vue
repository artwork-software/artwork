<template>
    <AppLayout :title="project?.name + ' (' + currentTab.name + ')'">
        <!-- Project Header -->
        <div class="">
            <div class="mt-10 artwork-container !pb-0">
                <div class="">
                    <!-- if in group -->
                    <div v-if="project?.groups?.length > 0" class="bg-secondaryHover text-sm shadow-sm border border-gray-200 px-3 py-1 rounded-lg -mb-2 z-20 w-fit pr-6 pb-0.5">
                        <div class="flex items-center">
                            <span v-if="!project?.is_group">
                                <img alt="default_image" src="/Svgs/IconSvgs/icon_group_black.svg" @error="(e) => e.target.src = usePage().props.big_logo" class="h-4 w-4 mr-2" aria-hidden="true"/>
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
                            <img :src="'/storage/keyVisual/' + project?.key_visual_path" @error="(e) => e.target.src = usePage().props.big_logo" :alt="$t('Current key visual')" class="rounded-md mx-auto h-[200px]">
                        </div>
                        <div v-else class="w-full h-40 bg-gray-200 flex justify-center items-center">
                            <img src="/images/place.png" @error="(e) => e.target.src = usePage().props.big_logo" :alt="$t('Current key visual')" class="rounded-md h-[200px]">
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <div class="flex items-center font-black font-lexend text-primary tracking-wide text-3xl gap-x-5">
                            <div class="flex items-center">
                                <div v-if="project?.is_group">
                                    <img alt="default_image" src="/Svgs/IconSvgs/icon_group_black.svg" class="h-6 w-6 mr-2" aria-hidden="true"/>
                                </div>
                                <div v-if="project?.key_visual_path !== null">
                                    <img :src="'/storage/keyVisual/' + project?.key_visual_path" @error="(e) => e.target.src = usePage().props.big_logo" :alt="$t('Current key visual')" class="mx-auto size-10 rounded-full object-cover mr-2 hover:size-24 duration-300 ease-in-out">
                                </div>
                                {{ project?.name }}
                            </div>

                            <div v-if="projectState" class="rounded-full items-center font-medium px-3 py-1 text-sm ml-2 inline-flex border" :style="{backgroundColor: backgroundColorWithOpacity(projectState.color, 10), color: getTextColorBasedOnBackground(projectState.color), borderColor: getTextColorBasedOnBackground(projectState.color)}">
                                {{ projectState.name }}
                            </div>
                        </div>
                        <div class="flex items-center justify-center gap-x-4">
                            <ToolTipComponent :tooltip-text="$t('Select print layout')" icon="IconPrinter" :direction="'bottom'" @click="showPrintLayoutSelectorModal = true" stroke="2" />

                            <BaseMenu menu-width="!w-fit" white-menu-background v-if="can('write projects') || is('artwork admin') || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || headerObject.projectWriteIds.includes(usePage().props.auth.user.id)">
                                <BaseMenuItem
                                    white-menu-background
                                    v-if="is('artwork admin') || headerObject.projectWriteIds.includes(usePage().props.auth.user.id) || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || can('write projects')"
                                    @click="openEditProjectModal"
                                    title="Edit basic data"
                                    />
                                <div v-if="project.is_group">
                                    <BaseMenuItem
                                        white-menu-background
                                        @click="showAddProjectToGroup = true"
                                        v-if="is('artwork admin') || headerObject.projectWriteIds.includes(usePage().props.auth.user.id) || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || can('write projects')"
                                        icon="IconCirclePlus"
                                        title="Add projects to group"
                                    />
                                    <BaseMenuItem
                                        white-menu-background
                                        @click="openCreateNewProjectInGroupModal" v-if="is('artwork admin') || headerObject.projectWriteIds.includes(usePage().props.auth.user.id) || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || can('write projects')"
                                        icon="IconCirclePlus"
                                        title="Create project in this group"
                                    />
                                </div>
                                <BaseMenuItem
                                    white-menu-background
                                    @click="duplicateProject(this.project)"
                                    icon="IconCopy"
                                    title="Duplicate"
                                />
                                <BaseMenuItem
                                    white-menu-background
                                    v-if="headerObject.projectDeleteIds.includes(usePage().props.auth.user.id) || is('artwork admin')"
                                    @click="openEditProjectModal"
                                    icon="IconTrash"
                                    title="Put in the trash"
                                />
                            </BaseMenu>
                        </div>
                    </div>

                    <div class="my-3" v-if="headerObject.projectsOfGroup.length > 0">
                        <div class="text-secondary xsDark">
                            {{ $t('Projects in this group') }}:
                        </div>
                        <div class="flex flex-wrap">
                            <div v-for="(groupProject, index) in headerObject.projectsOfGroup" :key="groupProject.id" class="group flex items-center max-w-1/2 w-fit pr-3 m-1 rounded-full border border-gray-300 bg-gray-50">
                                <a :href="route('projects.tab', { project: groupProject?.id, projectTab: first_project_tab_id })">
                                    <img class="inline-block size-9 rounded-full object-cover" :src="groupProject?.key_visual_path ? '/storage/keyVisual/' + groupProject?.key_visual_path : '/storage/logo/artwork_logo_small.svg'" alt=""/>
                                </a>
                                <a :href="route('projects.tab', { project: groupProject?.id, projectTab: first_project_tab_id })" class="mx-2">
                                    <p class="xsDark group-hover:text-gray-900">{{ groupProject.name }}</p>
                                </a>
                                <button type="button" @click="deleteProjectFromGroup(groupProject.id)">
                                    <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="w-full my-2 text-secondary xsDark">
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
                        <button class="ml-4 subpixel-antialiased text-artwork-buttons-create flex items-center cursor-pointer" @click="openProjectHistoryModal()">
                            <component is="IconChevronRight" class="-mr-0.5 h-4 w-4  group-hover:text-artwork-buttons-hover" aria-hidden="true"/>
                            {{ $t('View history') }}
                        </button>
                    </div>
                </div>

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
            </div>

            <!-- tabs -->

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
                @open-project-planning-state-change-modal="openProjectPlanningStateChangeModal"
                :create-settings="createSettings"
                :project="projectForCreateModal"
                :selected-group="selectedGroup"
            />

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
                        <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-full focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent text-base font-bold uppercase shadow-sm text-white" @click="deleteProject">
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
            <ProjectStateChangeModal
                :project-id="project.id"
                v-if="showProjectStateChangeModal"
                @close="showProjectStateChangeModal = false"
            />

            <ProjectPlanningStateChangeModal
                :project-id="project.id"
                v-if="showProjectPlanningStateChangeModal"
                @close="closePlanningStateChangeModal()"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import {ref, computed, nextTick} from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import { XIcon } from "@heroicons/vue/outline";
import Button from "@/Jetstream/Button.vue";
import ProjectGroupAddProject from "@/Pages/Projects/Components/Modals/ProjectGroupAddProject.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import PrintLayoutSelectorModal from "@/Pages/Projects/Components/PrintLayoutSelectorModal.vue";
import ProjectStateChangeModal from "@/Layouts/Components/ProjectStateChangeModal.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {can, is} from "laravel-permission-to-vuejs";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import ProjectPlanningStateChangeModal from "@/Layouts/Components/ProjectPlanningStateChangeModal.vue";

const props = defineProps({
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
});

const showProjectHistory = ref(false);
const editingProject = ref(false);
const deletingProject = ref(false);
const projectToDelete = ref(null);
const showAddProjectToGroup = ref(false);
const showPrintLayoutSelectorModal = ref(false);
const showProjectStateChangeModal = ref(false);
const showProjectPlanningStateChangeModal = ref(false);
const projectForCreateModal = ref(props.project);
const selectedGroup = ref(null);
const {
    backgroundColorWithOpacity,
    getTextColorBasedOnBackground,
} = useColorHelper();


const projectState = computed(() => {
    return props.headerObject.states.find(state => state.id === props.project?.state);
});

function openCreateNewProjectInGroupModal() {
    projectForCreateModal.value = null;
    selectedGroup.value = props.project;
    editingProject.value = true;
}

function hasBudgetAccess() {
    return props.headerObject.project.access_budget.filter((user) => user.id === usePage().props.auth.user?.id).length > 0;
}

function openProjectHistoryModal() {
    showProjectHistory.value = true;
}

function closeProjectHistoryModal() {
    showProjectHistory.value = false;
}

function openEditProjectModal() {
    projectForCreateModal.value = props.project;
    editingProject.value = true;
}

function closeEditProjectModal() {
    editingProject.value = false;
}

function duplicateProject(project) {
    router.post(route('projects.duplicate', project.id));
}

function openDeleteProjectModal(project) {
    projectToDelete.value = project;
    deletingProject.value = true;
}

function closeDeleteProjectModal() {
    deletingProject.value = false;
    projectToDelete.value = null;
}

function deleteProjectFromGroup(projectGroupId) {
    router.delete(route('projects.group.delete', {
        project: props.project.id,
        projectGroup: projectGroupId
    }), {
        onSuccess: () => {
            props.headerObject.projectGroups.splice(
                props.headerObject.projectGroups.findIndex(index => index.id === projectGroupId.id), 1
            );
        }
    });
}

function deleteProject() {
    router.delete(route('projects.destroy', projectToDelete.value.id));
    closeDeleteProjectModal();
}

function locationString() {
    return Object.values(props.headerObject.roomsWithAudience).join(", ");
}

function openProjectStateChangeModal() {
    showProjectStateChangeModal.value = true;
}

const openProjectPlanningStateChangeModal = () => {
    showProjectPlanningStateChangeModal.value = true;
}

const closePlanningStateChangeModal = () => {
    showProjectPlanningStateChangeModal.value = false;
    nextTick(() => {
        router.reload()
    });
}

const closeProjectStateChangeModal = () => {
    showProjectStateChangeModal.value = false;
    nextTick(() => {
        router.reload()
    });
}
</script>

<style scoped>

</style>
