<template>
    <AppLayout title="Projektübersicht">
        <div class="container mx-auto pt-6 relative">
            <!-- Headbar (neu) -->
            <ToolbarHeader
                :icon="IconGeometry"
                :title="$t('Projects')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :description="projects.total + ' ' + $t('projects in total')"
                v-model="project_search"
                :search-enabled="true"
                :search-label="$t('Search for projects or their artists')"
                search-placeholder="Suche nach Projekten oder deren Künstler*innen"
                :search-tooltip="$t('Search')"
            >
                <template #actions>
                    <!-- Filter -->
                    <BaseFilter :only-icon="true" :left="false" white-background dots-size="size-6" :use-full-button="true" :has-active-filters="hasActiveFilters">
                        <div class="w-full px-2 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-medium text-text-muted ">{{ $t('Filters') }}</div>
                                <button type="button" class="text-xs text-text-subtle hover:text-text-muted transition" @click="resetFilter">
                                    {{ $t('Deselect all') }}
                                </button>
                            </div>

                            <div class="space-y-3">
                                <!-- Filter nach Art -->
                                <div class="text-xs font-semibold text-text-subtle uppercase tracking-wide">{{ $t('Filter by type') }}</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2">
                                        <input v-model="showProjectGroups" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Project groups') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2">
                                        <input v-model="showProjects" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Projects') }}</span>
                                    </label>
                                </div>

                                <!-- Filter nach Zeit -->
                                <div class="text-xs font-semibold text-text-subtle uppercase tracking-wide pt-1">{{ $t('Filter by time') }}</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2">
                                        <input v-model="showFutureProjects" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Show future projects') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2">
                                        <input v-model="showExpiredProjects" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Show expired projects') }}</span>
                                    </label>
                                </div>

                                <!-- Sonstige Filter -->
                                <div class="text-xs font-semibold text-text-subtle uppercase tracking-wide pt-1">{{ $t('Other filters') }}</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2">
                                        <input v-model="hideProjectsWithoutEvents" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Hide projects without events') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2">
                                        <input v-model="showOnlyProjectsWithoutGroup" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Show only projects without group') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2 sm:col-span-2">
                                        <input v-model="showOnlyMyProjects" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Only projects where you are in the project team') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2 sm:col-span-2">
                                        <input v-model="showOnlyWithBiData" type="checkbox" class="size-4 accent-success" />
                                        <span class="text-sm text-text-muted ">{{ $t('Only productions with BI data') }}</span>
                                    </label>
                                </div>

                                <!-- States -->
                                <div class="border-t border-border-subtle pt-2">
                                    <button
                                        type="button"
                                        class="w-full flex items-center justify-between text-sm text-text-muted "
                                        @click="showProjectStateFilter = !showProjectStateFilter"
                                    >
                                        <span>{{ $t('Project status') }}</span>
                                        <IconChevronDown v-if="!showProjectStateFilter" class="h-5 w-5 text-text-subtle" />
                                        <IconChevronUp v-else class="h-5 w-5 text-text-subtle" />
                                    </button>
                                    <div v-if="showProjectStateFilter" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <label v-for="state in computedStates" :key="state.id" class="flex items-center gap-3 rounded-xl border border-border-subtle px-3 py-2">
                                            <input v-model="state.clicked" type="checkbox" class="size-4 accent-success" />
                                            <span class="text-sm text-text-muted truncate">{{ state.name }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end">
                                    <button
                                        class="text-xs font-semibold text-artwork-buttons-create hover:text-artwork-buttons-hover cursor-pointer transition"
                                        @click="applyFiltersAndSort()"
                                        type="button"
                                    >
                                        {{ $t('Apply') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </BaseFilter>

                    <!-- Sort --><div class="flex items-center mr-6">
                    <BaseMenu show-sort-icon dots-size="size-6" menu-width="w-72" classes-button="ui-button">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-text-muted ">{{ $t('Sort by') }}</div>
                            <button type="button" class="text-xs text-text-subtle hover:text-text-muted transition" @click="resetSort()">
                                {{ $t('Reset') }}
                            </button>
                        </div>
                        <MenuItem v-for="projectSortEnumName in projectSortEnumNames" :key="projectSortEnumName" v-slot="{ active }">
                            <div
                                @click="sortBy = projectSortEnumName; applyFiltersAndSort()"
                                :class="[ active ? 'bg-accent-50 text-artwork-buttons-create ' : 'text-text-muted ',
                                        'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm rounded-md'
                                      ]"
                            >
                                {{ getSortEnumTranslation(projectSortEnumName) }}
                                <IconCheck v-if="userProjectManagementSetting.sort_by === projectSortEnumName" class="w-5 h-5" />
                            </div>
                        </MenuItem>
                    </BaseMenu>
                </div>
                    <!-- Export -->
                    <button type="button" @click="openExportModal">
                        <ToolTipComponent :icon="IconFileExport" icon-size="size-6" :tooltip-text="$t('Export project list')" direction="bottom" classes-button="ui-button" />
                    </button>

                    <!-- Bulk selection mode toggle -->
                    <button type="button" @click="toggleSelectionMode" v-if="role('artwork admin') || can('delete projects')">
                        <ToolTipComponent
                            :icon="IconChecklist"
                            icon-size="size-6"
                            :tooltip-text="selectionMode ? $t('Exit selection mode') : $t('Select multiple projects')"
                            direction="bottom"
                            :classes-button="selectionMode ? 'ui-button text-artwork-buttons-create' : 'ui-button'"
                        />
                    </button>

                    <BaseUIButton label="New project" use-translation is-add-button @click="openCreateProjectModal"  v-if="can('create and edit own project') || role('artwork admin')" />
                </template>
            </ToolbarHeader>

            <!-- Last visited -->
            <div class="my-5 flex items-center justify-between">
                <div class="flex items-center gap-2 pb-2" v-if="lastProject?.id">
                    <div class="text-sm text-text-muted ">{{ $t('Last visited project') }}:</div>
                    <a
                        class="text-artwork-buttons-create text-sm font-semibold inline-flex items-center gap-1"
                        :href="route('projects.tab', { project: lastProject.id, projectTab: first_project_tab_id })"
                    >
                        <component :is="IconGeometry" class="size-4" />
                        <span class="truncate max-w-56">{{ lastProject.name }}</span>
                    </a>
                </div>
            </div>

            <!-- Header row -->
            <div class="overflow-x-auto">
                <div class="min-w-fit">
                    <div class="">
                        <div class="border-b border-border-subtle backdrop-blur px-3 py-2">
                            <div
                                class="grid items-center text-sm font-semibold text-text tracking-wide"
                                :style="`grid-template-columns: ${gridTemplateColumns}`"
                            >
                                <div
                                    v-for="component in components"
                                    :key="component.name"
                                    :class="['px-3 py-2', component.type === 'ActionsComponent' ? 'text-right' : 'text-left']"
                                >
                                    <span v-if="checkIfComponentIsVisible(component)" class="truncate block">{{ $t(component.name) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SKELETONS -->
                    <div v-if="isLoading" class="mt-3 space-y-2">
                        <div
                            v-for="n in skeletonCount"
                            :key="'sk-'+n"
                            class="rounded-xl border border-border-subtle bg-white/70 backdrop-blur overflow-hidden"
                        >
                            <div class="grid items-center" :style="`grid-template-columns: ${gridTemplateColumns}`">
                                <div v-for="i in skeletonCols" :key="i" class="px-3 py-3">
                                    <div class="h-4 w-[70%] rounded bg-border-subtle animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pinned -->
                    <div v-else-if="pinnedProjects?.length" class="divide-y divide-border-subtle">
                        <SingleProjectInManagement
                            v-for="project in pinnedProjects"
                            :key="project.id"
                            :project="project"
                            :components="components"
                            :categories="categories"
                            :genres="genres"
                            :sectors="sectors"
                            :states="states"
                            :project-groups="projectGroups"
                            :create-settings="createSettings"
                            :full-project="pinnedProjectsAll.find((p) => p.id === project.id)"
                            :grid-template-columns="gridTemplateColumns"
                            :selection-mode="selectionMode"
                            :selected="selectedProjectIds.includes(project.id)"
                            @toggle-selection="toggleProjectSelection"
                            v-memo="[project.id, project.updated_at, project.title, selectionMode, selectedProjectIds.includes(project.id)]"
                        />
                    </div>

                    <!-- List -->
                    <div v-if="!isLoading" class="divide-y divide-border-subtle">
                        <SingleProjectInManagement
                            v-for="project in projectComponents"
                            :key="project.id"
                            :project="project"
                            :components="components"
                            :categories="categories"
                            :genres="genres"
                            :sectors="sectors"
                            :states="states"
                            :project-groups="projectGroups"
                            :create-settings="createSettings"
                            :full-project="projects.data.find((p) => p.id === project.id)"
                            :grid-template-columns="gridTemplateColumns"
                            :selection-mode="selectionMode"
                            :selected="selectedProjectIds.includes(project.id)"
                            @toggle-selection="toggleProjectSelection"
                            v-memo="[project.id, project.updated_at, project.title, selectionMode, selectedProjectIds.includes(project.id)]"
                        />
                    </div>
                </div>
            </div>

            <!-- Paginator -->
            <div class="px-1 sm:px-0 pb-8 pt-6">
                <BasePaginator
                    :entities="projects"
                    property-name="projects"
                    :emit-update-entities-per-page="true"
                    @update-page="updatePage"
                    @update-entities-per-page="changeEntitiesPerPage"
                />
            </div>
        </div>

        <!-- Notifications & Modals -->
        <SideNotification v-if="dropFeedbackShown" type="project_create_success" />

        <project-create-modal
            v-if="createProject"
            :show="createProject"
            :categories="categories"
            :genres="genres"
            :sectors="sectors"
            :project-groups="projectGroups"
            :states="states"
            :create-settings="createSettings"
            :project="null"
            @close-create-project-modal="closeCreateProjectModal"
            @drop-feedback="showDropFeedback"
        />

        <AddBulkEventsModal
            v-if="showAddBulkEventModal"
            @closed="showAddBulkEventModal = false"
            :project="myLastProject"
            :event_types="eventTypes"
            :rooms="rooms"
            :eventStatuses="eventStatuses"
        />

        <SuccessModal
            :show="showSuccessModal2"
            @closed="closeSuccessModal2"
            :title="$t('Project created')"
            :description="$t('The project was successfully created.')"
            :button="$t('Close')"
        />

        <project-data-edit-modal
            v-if="editingProject"
            :show="editingProject"
            :project="projectToEdit"
            :group-projects="projectGroups"
            :current-group="groupPerProject[projectToEdit?.id]"
            :states="states"
            @closed="closeEditProjectModal"
        />

        <project-history-component
            v-if="showProjectHistory"
            :project_id="selectedProjectId"
            @closed="closeProjectHistoryModal"
        />

        <export-modal
            v-if="showExportModal"
            @close="showExportModal = false"
            :enums="exportTabs"
            :configuration="getExportModalConfiguration()"
        />

        <!-- Selection-mode action bar (move selected projects to trash) -->
        <div v-if="selectionMode"
             class="fixed inset-x-0 bottom-0 z-30 border-t border-border-subtle bg-white/95 backdrop-blur py-3 pr-6 pl-20 shadow-[0_-2px_10px_rgba(0,0,0,0.06)] print:hidden">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm text-text-muted">
                    <input
                        type="checkbox"
                        :checked="allOnPageSelected"
                        @change="toggleSelectAllOnPage"
                        class="h-4 w-4 rounded border-border text-artwork-buttons-hover focus:ring-artwork-buttons-hover cursor-pointer"
                    />
                    {{ $t('Select all on this page') }}
                    <span class="ml-2 text-text-subtle">· {{ $t('{0} selected', [selectedProjectIds.length]) }}</span>
                </label>
                <div class="flex items-center gap-x-4">
                    <button type="button" class="text-sm text-text-muted hover:text-text" @click="toggleSelectionMode">
                        {{ $t('Cancel') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-x-1.5 rounded-full px-5 py-2 text-sm font-bold text-white"
                        :class="selectedProjectIds.length === 0 ? 'bg-border cursor-not-allowed' : 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover'"
                        :disabled="selectedProjectIds.length === 0"
                        @click="openBulkDeleteModal"
                    >
                        <component :is="IconTrash" class="h-4 w-4" />
                        {{ $t('Put in the trash') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Bulk delete confirmation -->
        <BaseModal @closed="closeBulkDeleteModal" v-if="bulkDeleting">
            <div class="mx-4">
                <div class="text-2xl sm:text-3xl font-black text-text my-2">
                    {{ $t('Delete selected projects') }}
                </div>
                <div class="text-sm text-danger">
                    {{ $t('Are you sure you want to move the {0} selected projects to the trash?', [selectedProjectIds.length]) }}
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-between mt-6">
                    <button
                        class="bg-success hover:bg-success text-white rounded-full focus:outline-none inline-flex items-center px-8 py-3 text-sm font-semibold uppercase shadow-sm"
                        @click="bulkDeleteProjects"
                    >
                        {{ $t('Delete') }}
                    </button>
                    <button
                        type="button"
                        @click="closeBulkDeleteModal()"
                        class="text-sm text-text-muted hover:underline underline-offset-4"
                    >
                        {{ $t('No, not really') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router, usePage } from "@inertiajs/vue3";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {
    IconCheck,
    IconChecklist,
    IconChevronDown,
    IconChevronUp, IconCirclePlus,
    IconFileExport,
    IconGeometry,
    IconSearch,
    IconTrash,
    IconX
} from "@tabler/icons-vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import { usePermission } from "@/Composeables/Permission.js";
import { MenuItem, Switch, SwitchGroup } from "@headlessui/vue";
import { computed, nextTick, ref, watch } from "vue";
import { useSortEnumTranslation } from "@/Composeables/SortEnumTranslation.js";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";
import AddBulkEventsModal from "@/Pages/Projects/Components/AddBulkEventsModal.vue";
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import ExportModal from "@/Layouts/Components/Export/Modals/ExportModal.vue";
import debounce from "lodash.debounce";
import SingleProjectInManagement from "@/Pages/Projects/ProjectManagmentComponents/SingleProjectInManagement.vue";
import { useExportTabEnums } from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const { can, role, canSeeComponent } = usePermission(usePage().props);
const { getSortEnumTranslation } = useSortEnumTranslation();
const exportTabEnums = useExportTabEnums();

const props = defineProps({
    components: { type: Object, required: true },
    projectComponents: { type: Object, required: true },
    projects: { type: Object, required: true },
    projectSortEnumNames: { type: Object, required: true },
    eventTypes: { type: Object, required: true },
    rooms: { type: Object, required: true },
    userProjectManagementSetting: { type: Object, required: true },
    eventStatuses: { type: Object, required: true },
    sectors: { type: Object, required: true },
    genres: { type: Object, required: true },
    categories: { type: Object, required: true },
    states: { type: Object, required: true },
    projectGroups: { type: Object, required: true },
    createSettings: { type: Object, required: true },
    first_project_tab_id: { type: Number, required: true },
    pinnedProjects: { type: Object, required: true },
    myLastProject: { type: Object, required: false },
    pinnedProjectsAll: { type: Object, required: true },
    lastProject: { type: Object, required: true },
    entitiesPerPage: { type: Number, required: true },
});

const project_search = ref(route().params.query);
const showProjectHistoryTab = ref(true);
const showSearchbar = ref(route().params.query?.length > 0);
const showSuccessModal2 = ref(false);
const showProjectHistory = ref(false);
const selectedProjectId = ref(null);
const editingProject = ref(false);
const projectToEdit = ref(null);
const createProject = ref(false);
const showExportModal = ref(false);
const page = ref(route().params.page ?? 1);
const perPage = ref(props.entitiesPerPage ?? 10);
const showAddBulkEventModal = ref(false);
const dropFeedbackShown = ref(null);

// Bulk selection (move multiple projects to trash)
const selectionMode = ref(false);
const selectedProjectIds = ref([]);
const bulkDeleting = ref(false);
const pageProjectIds = computed(() => (props.projects?.data ?? []).map((p) => p.id));
const allOnPageSelected = computed(
    () => pageProjectIds.value.length > 0 && pageProjectIds.value.every((id) => selectedProjectIds.value.includes(id))
);
const toggleSelectionMode = () => {
    selectionMode.value = !selectionMode.value;
    if (!selectionMode.value) selectedProjectIds.value = [];
};
const toggleProjectSelection = (projectId) => {
    const i = selectedProjectIds.value.indexOf(projectId);
    if (i === -1) selectedProjectIds.value.push(projectId);
    else selectedProjectIds.value.splice(i, 1);
};
const toggleSelectAllOnPage = () => {
    if (allOnPageSelected.value) {
        selectedProjectIds.value = selectedProjectIds.value.filter((id) => !pageProjectIds.value.includes(id));
    } else {
        selectedProjectIds.value = [...new Set([...selectedProjectIds.value, ...pageProjectIds.value])];
    }
};
const openBulkDeleteModal = () => {
    if (selectedProjectIds.value.length) bulkDeleting.value = true;
};
const closeBulkDeleteModal = () => (bulkDeleting.value = false);
const bulkDeleteProjects = () => {
    router.delete(route("projects.bulk-destroy"), {
        data: {
            project_ids: selectedProjectIds.value,
            page: page.value,
            query: project_search.value,
        },
        preserveScroll: true,
        onSuccess: () => {
            selectedProjectIds.value = [];
            bulkDeleting.value = false;
            selectionMode.value = false;
        },
    });
};

// Loading-State für Skeletons
const isLoading = ref(false);
const skeletonCols = computed(() => props.components?.length ?? 6);
const skeletonCount = computed(() => Math.min(perPage.value || 10, 10)); // max. 10 Skeletons

// Settings
const userProjectManagementSetting = props.userProjectManagementSetting;
const showOnlyMyProjects = ref(userProjectManagementSetting?.project_filters.showOnlyMyProjects);
const showProjectGroups = ref(userProjectManagementSetting?.project_filters.showProjectGroups);
const showProjects = ref(userProjectManagementSetting?.project_filters.showProjects);
const showExpiredProjects = ref(userProjectManagementSetting?.project_filters.showExpiredProjects);
const showFutureProjects = ref(userProjectManagementSetting?.project_filters.showFutureProjects ?? false);
const hideProjectsWithoutEvents = ref(userProjectManagementSetting?.project_filters.hideProjectsWithoutEvents);
const showOnlyProjectsWithoutGroup = ref(userProjectManagementSetting?.project_filters.showOnlyProjectsWithoutGroup);
const showOnlyWithBiData = ref(userProjectManagementSetting?.project_filters.showOnlyWithBiData);
const sortBy = ref(userProjectManagementSetting?.sort_by === null ? undefined : userProjectManagementSetting?.sort_by);

const showProjectStateFilter = ref(true);

const hasActiveFilters = computed(() => {
    return !!showOnlyMyProjects.value
        || !!showProjectGroups.value
        || !!showProjects.value
        || !!showExpiredProjects.value
        || !!showFutureProjects.value
        || !!hideProjectsWithoutEvents.value
        || !!showOnlyProjectsWithoutGroup.value
        || !!showOnlyWithBiData.value
        || props.states.some((s) => s.clicked);
});

// Grid wird 1x berechnet und an Zeilen weitergegeben → weniger Recalcs
const gridTemplateColumns = computed(() =>
    props.components
        .map((component) => {
            if (component.type === "ProjectTitleComponent") return "20rem";
            if (component.type === "ActionsComponent") return "8rem";
            return "14rem";
        })
        .join(" ")
);

const checkIfComponentIsVisible = (component) => {
    if (component?.type === "ProjectTitleComponent" || component?.type === "ActionsComponent" || component?.type === null) return true;
    return canSeeComponent(component.component);
};

// Headbar Aktionen
const openCreateProjectModal = () => (createProject.value = true);
const closeCreateProjectModal = (showSuccessModalFlag) => {
    createProject.value = false;
    if (showSuccessModalFlag) showAddBulkEventModal.value = true;
};
const closeEditProjectModal = () => {
    editingProject.value = false;
    projectToEdit.value = null;
};
const closeSearchbar = () => {
    showSearchbar.value = !showSearchbar.value;
    project_search.value = "";
};
const openSearchbar = () => {
    showSearchbar.value = !showSearchbar.value;
    nextTick(() => {
        if (showSearchbar.value) document.querySelector("#searchBarInput")?.focus();
    });
};
const closeSuccessModal2 = () => {
    showSuccessModal2.value = false;
    if (showSearchbar.value) closeSearchbar();
};
const closeProjectHistoryModal = () => {
    showProjectHistory.value = false;
    selectedProjectId.value = null;
};
const openExportModal = () => (showExportModal.value = true);

// Filter/Sort -> laden mit Skeleton
const applyFiltersAndSort = (resetPage = true) => {
    isLoading.value = true;
    router.post(
        route("projects.filter"),
        {
            project_state_ids: props.states.filter((s) => s.clicked).map((s) => s.id),
            project_filters: {
                showOnlyMyProjects: getTruthyOrUndefined(showOnlyMyProjects.value),
                showProjectGroups: getTruthyOrUndefined(showProjectGroups.value),
                showProjects: getTruthyOrUndefined(showProjects.value),
                showExpiredProjects: getTruthyOrUndefined(showExpiredProjects.value),
                showFutureProjects: getTruthyOrUndefined(showFutureProjects.value),
                hideProjectsWithoutEvents: getTruthyOrUndefined(hideProjectsWithoutEvents.value),
                showOnlyProjectsWithoutGroup: getTruthyOrUndefined(showOnlyProjectsWithoutGroup.value),
                showOnlyWithBiData: getTruthyOrUndefined(showOnlyWithBiData.value),
            },
            sort: sortBy.value,
        },
        {
            preserveState: false,
            onStart: () => (isLoading.value = true),
            onFinish: () => (isLoading.value = false),
            onSuccess: () => reloadProjects(resetPage),
        }
    );
};

const resetFilter = () => {
    showOnlyMyProjects.value = false;
    showProjectGroups.value = false;
    showProjects.value = false;
    showExpiredProjects.value = false;
    showFutureProjects.value = false;
    hideProjectsWithoutEvents.value = false;
    showOnlyProjectsWithoutGroup.value = false;
    showOnlyWithBiData.value = false;

    props.states.forEach((s) => (s.clicked = false));

    isLoading.value = true;
    router.post(route("projects.filter"), {
        page: 1,
        entitiesPerPage: perPage.value,
        query: route().params.query,
        project_states: undefined,
        project_filters: undefined,
        sort: sortBy.value,
    }, {
        onStart: () => (isLoading.value = true),
        onFinish: () => (isLoading.value = false),
    });
};

const resetSort = () => {
    sortBy.value = undefined;
    applyFiltersAndSort();
};

const updatePage = (newPage, entitiesPerPage) => {
    page.value = newPage;
    perPage.value = entitiesPerPage;
    applyFiltersAndSort(false);
};

const changeEntitiesPerPage = (entitiesPerPage) => {
    perPage.value = entitiesPerPage;
    applyFiltersAndSort();
};

const getTruthyOrUndefined = (value) => (value ? 1 : undefined);

// Reload (wird von applyFiltersAndSort etc. getriggert)
const reloadProjects = (resetPage = true) => {
    router.reload({
        only: ["projects", "pinnedProjects", "projectComponents", "components", "pinnedProjectsAll"],
        data: {
            page: resetPage ? 1 : page.value,
            entitiesPerPage: perPage.value,
            query: project_search.value,
        },
        onStart: () => (isLoading.value = true),
        onFinish: () => (isLoading.value = false),
    });
};

// Debounced search
const reloadProjectsDebounced = debounce(reloadProjects, 800);
watch(project_search, () => reloadProjectsDebounced());

// Export-Konfig
const exportTabs = computed(() => {
    const tabs = [
        exportTabEnums.EXCEL_EVENT_LIST_EXPORT,
        exportTabEnums.EXCEL_CALENDAR_EXPORT,
    ];
    if (props.createSettings.budget_deadline) {
        tabs.push(exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT);
    }
    if (can('view projects') || role('artwork admin')) {
        tabs.push(exportTabEnums.EXCEL_PROJECT_ROLE_MATRIX_EXPORT);
    }
    return tabs;
});

const getExportModalConfiguration = () => ({
    [exportTabEnums.EXCEL_EVENT_LIST_EXPORT]: { show_artists: props.createSettings.show_artists },
});

// States (unverändert)
const computedStates = computed(() => {
    if (props.userProjectManagementSetting) {
        props.states.forEach((state) => {
            state.clicked = props.userProjectManagementSetting.project_state_ids.includes(state.id);
        });
    }
    return props.states;
});
</script>
