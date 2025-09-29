<template>
    <AppLayout :title="project?.name + ' (' + currentTab.name + ')'">

        <transition name="fade" appear>
            <div class="pointer-events-none fixed z-50 inset-x-0 top-5 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8" v-show="showCopyUrl">
                <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                    <component :is="IconClipboard" class="size-5 text-blue-400" aria-hidden="true" />
                    <p class="text-sm/6 text-white">
                        {{ $t('Project URL has been copied') }}
                    </p>
                    <button type="button" class="-m-1.5 flex-none p-1.5">
                        <span class="sr-only">Dismiss</span>
                        <component :is="IconX" class="size-5 text-white" aria-hidden="true" @click="showCopyUrl = false" />
                    </button>
                </div>
            </div>
        </transition>

        <!-- HEADER-CARD -->
        <div class="relative h-full min-h-screen">
            <div class="mt-10 artwork-container !pb-0">
                <div class="relative">

                    <div class="absolute inset-0 -top-6 bg-gradient-to-b from-artwork-navigation-color/10 to-white/0 pointer-events-none"></div>

                    <div class="p-5 sm:p-6">
                        <!-- Zugehörigkeit zu Gruppen -->
                        <div v-if="project?.groups?.length > 0" class="mb-3">
                            <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50/70 px-3 py-1 text-xs text-zinc-700 ring-1 ring-white/40">
                                <img v-if="!project?.is_group" alt="" src="/Svgs/IconSvgs/icon_group_black.svg" class="h-3.5 w-3.5 opacity-70" />
                                <span class="font-medium">{{ $t('Belongs to') }}</span>
                                <div class="flex gap-1.5 overflow-x-auto no-scrollbar">
                                    <a
                                        v-for="group in project.groups"
                                        :key="group?.id"
                                        :href="route('projects.tab', { project: group?.id, projectTab: first_project_tab_id })"
                                        class="inline-flex items-center gap-1 rounded-full border border-artwork-navigation-color/30 bg-gradient-to-br from-artwork-navigation-color/10 to-transparent px-2 py-0.5 text-[11px] font-medium text-artwork-buttons-hover ring-1 ring-inset ring-white/40 hover:ring-artwork-navigation-color/40 transition"
                                    >
                                        {{ group?.name }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Kopfzeile -->
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-4">
                                <!-- Key Visual mit Status-Halo -->
                                <div class="relative shrink-0">
                                    <img
                                        v-if="project?.key_visual_path !== null"
                                        :src="'/storage/keyVisual/' + project?.key_visual_path"
                                        :alt="$t('Current key visual')"
                                        @error="(e) => e.target.src = usePage().props.big_logo"
                                        class="size-24 rounded-full object-cover ring-2 ring-white transition-transform duration-300 hover:scale-110"
                                    />
                                    <div v-else class="size-12 rounded-full bg-gradient-to-br from-zinc-200 to-zinc-100 ring-2 ring-white shadow-inner"></div>
                                </div>

                                <!-- Titel + Status -->
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h1 class="font-lexend font-black tracking-wide text-2xl sm:text-3xl text-primary truncate">
                                            {{ project?.name }}
                                        </h1>
                                        <img v-if="project?.is_group" alt="" src="/Svgs/IconSvgs/icon_group_black.svg" class="h-5 w-5 opacity-70" />
                                        <div
                                            v-if="projectState"
                                            class="ml-1 inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
                                            :style="{
                                          backgroundColor: backgroundColorWithOpacity(projectState.color, 10),
                                          color: getTextColorBasedOnBackground(projectState.color),
                                          borderColor: getTextColorBasedOnBackground(projectState.color)
                                        }"
                                        >
                                            {{ projectState.name }}
                                        </div>
                                    </div>

                                    <!-- Info-Chips -->
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                                        <!-- Zeitraum -->
                                        <span
                                            v-if="headerObject.firstEventInProject && headerObject.lastEventInProject"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white/70 px-2.5 py-1 text-zinc-700 ring-1 ring-white/40"
                                        >
                    <span class="font-medium">{{ $t('Time period/opening hours') }}:</span>
                    <span class="tabular-nums">{{ headerObject.firstEventInProject?.start_time }}</span>
                    <span v-if="headerObject.firstEventInProject?.start_time">{{ $t('Clock') }} –</span>
                    <span class="tabular-nums">{{ headerObject.lastEventInProject?.end_time }}</span>
                    <span v-if="headerObject.lastEventInProject?.end_time">{{ $t('Clock') }}</span>
                  </span>

                                        <!-- Orte -->
                                        <span
                                            v-if="headerObject.roomsWithAudience.length > 0"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white/70 px-2.5 py-1 text-zinc-700 shadow-sm ring-1 ring-white/40"
                                        >
                    <span class="font-medium">{{ $t('Appointments with audience in') }}:</span>
                    <span>{{ locationString() }}</span>
                  </span>

                                        <span
                                            v-if="headerObject.roomsWithAudience.length <= 0 && !(headerObject.firstEventInProject && headerObject.lastEventInProject)"
                                            class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white/70 px-2.5 py-1 text-zinc-700 shadow-sm ring-1 ring-white/40"
                                        >
                    {{ $t('No appointments within this project yet') }}
                  </span>
                                    </div>

                                    <!-- History -->
                                    <div class="mt-2 flex items-center text-[13px] text-zinc-600" v-if="headerObject.project_history.length">
                                        <span>{{ $t('last modified') }}:</span>
                                        <UserPopoverTooltip
                                            :user="headerObject.project_history[0]?.changer"
                                            :id="headerObject.project_history[0]?.changer.id"
                                            height="4"
                                            width="4"
                                            class="ml-2"
                                        />
                                        <span class="ml-2 tabular-nums">{{ headerObject.project_history[0]?.created_at }}</span>
                                        <button
                                            class="ml-4 inline-flex items-center gap-1 text-artwork-buttons-create hover:text-artwork-buttons-hover transition"
                                            @click="openProjectHistoryModal()"
                                        >
                                            <component :is="IconChevronRight" class="-mr-0.5 h-4 w-4" aria-hidden="true" />
                                            {{ $t('View history') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3">
                                <ToolTipComponent
                                    :tooltip-text="$t('Select print layout')"
                                    :icon="IconPrinter"
                                    :direction="'bottom'"
                                    @click="showPrintLayoutSelectorModal = true"
                                    stroke="2"
                                />
                                <!-- Copy Link -->
                                <ToolTipComponent
                                    :tooltip-text="$t('Copy link')"
                                    :icon="IconLink"
                                    :direction="'bottom'"
                                    @click="copyProjectUrlToClipboard"
                                    stroke="2"
                                />

                                <BaseMenu
                                    menu-width="!w-fit"
                                    white-menu-background
                                    v-if="can('write projects') || is('artwork admin') || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || headerObject.projectWriteIds.includes(usePage().props.auth.user.id)"
                                >
                                    <BaseMenuItem
                                        white-menu-background
                                        v-if="is('artwork admin') || headerObject.projectWriteIds.includes(usePage().props.auth.user.id) || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || can('write projects')"
                                        @click="openEditProjectModal"
                                        title="Edit basic data"
                                        :icon="IconEdit"
                                    />
                                    <div v-if="project.is_group">
                                        <BaseMenuItem
                                            white-menu-background
                                            @click="showAddProjectToGroup = true"
                                            v-if="is('artwork admin') || headerObject.projectWriteIds.includes(usePage().props.auth.user.id) || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || can('write projects')"
                                            :icon="IconCirclePlus"
                                            title="Add projects to group"
                                        />
                                        <BaseMenuItem
                                            white-menu-background
                                            @click="openCreateNewProjectInGroupModal"
                                            v-if="is('artwork admin') || headerObject.projectWriteIds.includes(usePage().props.auth.user.id) || headerObject.projectManagerIds.includes(usePage().props.auth.user.id) || can('write projects')"
                                            :icon="IconCirclePlus"
                                            title="Create project in this group"
                                        />
                                    </div>
                                    <BaseMenuItem white-menu-background @click="duplicateProject(this.project)" :icon="IconCopy" title="Duplicate" />
                                    <BaseMenuItem
                                        white-menu-background
                                        v-if="headerObject.projectDeleteIds.includes(usePage().props.auth.user.id) || is('artwork admin')"
                                        @click="openDeleteProjectModal(project)"
                                        :icon="IconTrash"
                                        title="Put in the trash"
                                    />
                                </BaseMenu>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projekte in der Gruppe -->
                <div class="my-4" v-if="headerObject.projectsOfGroup.length > 0">
                    <div class="text-secondary xsDark mb-2">{{ $t('Projects in this group') }}:</div>
                    <div class="flex gap-2 overflow-x-auto">
                        <div
                            v-for="(groupProject, index) in headerObject.projectsOfGroup"
                            :key="groupProject.id"
                            class="group inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50/70 pr-2 pl-1 py-1 transition hover:border-zinc-300"
                        >
                            <a :href="route('projects.tab', { project: groupProject?.id, projectTab: first_project_tab_id })" class="shrink-0">
                                <img
                                    class="size-8 rounded-full object-cover ring-1 ring-white"
                                    :src="groupProject?.key_visual_path ? '/storage/keyVisual/' + groupProject?.key_visual_path : '/storage/logo/artwork_logo_small.svg'"
                                    alt=""
                                />
                            </a>
                            <a
                                :href="route('projects.tab', { project: groupProject?.id, projectTab: first_project_tab_id })"
                                class="xsDark max-w-[16rem] truncate"
                            >
                                {{ groupProject.name }}
                            </a>
                            <button type="button" @click="deleteProjectFromGroup(groupProject.id)" class="rounded-full p-1 hover:bg-zinc-100" aria-label="Remove from group">
                                <XIcon class="h-4 w-4 text-zinc-400 hover:text-error" />
                            </button>
                        </div>
                    </div>
                </div>


            </div>




            <!-- STICKY TABS -->
            <div class="artwork-container !pb-0 sticky top-0 z-40 mt-3 bg-white/80 backdrop-blur supports-[backdrop-filter]:backdrop-blur w-full mx-auto scroll-shadow-sm">

                <BaseTabs :tabs="tabsForBaseTabComponent" />

                <nav class="relative flex gap-6 px-1 pt-3 pb-2 text-sm tracking-wide hidden" aria-label="Tabs">

                    <Link
                        v-for="tab in headerObject.tabs"
                        :key="tab?.id"
                        :href="route('projects.tab', { project: headerObject.project.id, projectTab: tab.id })"
                        :aria-current="tab.id === headerObject.currentTabId ? 'page' : undefined"
                        class="relative whitespace-nowrap px-1 py-2 font-semibold cursor-pointer transition-colors snap-start font-lexend group"
                        :class="tab.id === headerObject.currentTabId ? 'text-artwork-buttons-hover' : 'text-artwork-context-dark hover:text-zinc-700'"
                    >
                        <span class="px-0.5">{{ tab.name }}</span>
                        <span class="tab-ink absolute left-0 right-0 -bottom-[9px] h-1.5 rounded-full transition-all duration-300"
                            :class="tab.id === headerObject.currentTabId ? 'bg-gradient-to-r from-blue-400 to-blue-600 scale-x-100' : ' group-hover:bg-gradient-to-r group-hover:from-blue-400 group-hover:to-blue-600'"
                        ></span>
                    </Link>
                </nav>
            </div>

            <!-- TAB-INHALT -->
            <div class="mt-4">
                <slot />
            </div>
        </div>

        <!-- MODALS -->
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
                <div class="mt-6 flex justify-between">
                    <button
                        class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-full focus:outline-none inline-flex items-center px-20 py-3 border border-transparent text-base font-bold uppercase shadow-sm text-white"
                        @click="deleteProject"
                    >
                        {{ $t('Delete') }}
                    </button>
                    <div class="my-auto">
            <span @click="closeDeleteProjectModal()" class="xsLight cursor-pointer">
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
            :print-layouts="printLayouts"
            :project="project"
            v-if="showPrintLayoutSelectorModal"
            @close="showPrintLayoutSelectorModal = false"
        />

        <ProjectStateChangeModal :project-id="project.id" v-if="showProjectStateChangeModal" @close="showProjectStateChangeModal = false" />
        <ProjectPlanningStateChangeModal :project-id="project.id" v-if="showProjectPlanningStateChangeModal" @close="closePlanningStateChangeModal()" />
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
import {
    IconAlertSquareRounded,
    IconChevronRight,
    IconCirclePlus,
    IconClipboard, IconCopy, IconEdit, IconFolder, IconFolderOpen,
    IconLink,
    IconPrinter, IconTrash,
    IconX
} from "@tabler/icons-vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import tabs from "@/Pages/Areas/Components/Tabs.vue";

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
const showCopyUrl = ref(null);
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

const copyProjectUrlToClipboard = () => {
    const projectUrl = window.location.href;
    navigator.clipboard.writeText(projectUrl).then(() => {
        // Optional: Feedback an den Nutzer, dass der Link kopiert wurde
        showCopyUrl.value = true

        setTimeout(() => {
            showCopyUrl.value = false
        }, 3000); // Nachricht nach 3 Sekunden ausblenden
    }).catch(err => {
        console.error('Could not copy text: ', err);
    });
}

const closeProjectStateChangeModal = () => {
    showProjectStateChangeModal.value = false;
    nextTick(() => {
        router.reload()
    });
}

const tabsForBaseTabComponent = computed(() => {
    return props.headerObject.tabs.map(tab => ({
        id: tab.id,
        name: tab.name,
        href: route('projects.tab', { project: props.project.id, projectTab: tab.id }),
        current: tab.id === props.headerObject.currentTabId,
        //icon: tab.id === props.headerObject.currentTabId ? 'IconFolderOpen' : 'IconFolder',
        permission: true
    }));
});
</script>

<style scoped>
/* Zahlenspalte ruhig & stabil */
.tabular-nums { font-variant-numeric: tabular-nums; }

/* Keine Scrollbar optisch */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

/* Ink-Bar Animation */
.tab-ink { transform-origin: left center; }

/* Optional: Wenn du lieber per CSS-Variable arbeiten willst */
:root { --app-header-offset: 64px; }        /* Headerhöhe anpassen */
.sticky-tabs { top: var(--app-header-offset); } /* Dann 'top-14 md:top-16' entfernen */

/* Ink-Bar Animation */
.tab-ink { transform-origin: left center; }

/* Optional: sehr dezenter Hover-Glow auf Links */
:deep(a:hover) { text-decoration-thickness: 2px; }

/* Kleiner Accessibility-Boost für Focus (dezent, aber sichtbar) */
:deep(button:focus-visible),
:deep(a:focus-visible) {
    outline: 2px solid rgb(59 130 246 / 0.6);
    outline-offset: 2px;
    border-radius: 0.5rem;
}
</style>
