<template>
    <AppLayout title="Projektübersicht">
        <div class="container mx-auto pt-6 relative">
            <!-- Headbar (neu) -->
            <ToolbarHeader
                :icon="IconGeometry"
                title="Projects"
                :description="`${projects.data.length} ${$t('Projects')}`"
                icon-bg-class="bg-blue-600/10 text-blue-700"
                v-model="project_search"
                :search-enabled="true"
                :search-label="$t('Search for projects')"
                :search-tooltip="$t('Search')"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="toggleMyProjects()"
                        :aria-pressed="!!showOnlyMyProjects"
                        class="ui-button flex items-center gap-2"
                        :class="showOnlyMyProjects ? 'bg-blue-600/10 text-blue-700 border-blue-200/60': 'text-zinc-700 dark:text-zinc-200'">
                        <span class="size-2 rounded-full" :class="showOnlyMyProjects ? 'bg-blue-500' : 'bg-zinc-300 dark:bg-zinc-700'"></span>
                        {{ $t('Only mine') }}
                    </button>

                    <!-- Filter -->
                    <BaseFilter :only-icon="true" :left="false" white-background classes="ui-button">
                        <div class="w-full px-2 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $t('Filters') }}</div>
                                <button type="button" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition" @click="resetFilter">
                                    {{ $t('Deselect all') }}
                                </button>
                            </div>

                            <div class="space-y-3">
                                <!-- Toggles -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                                        <input v-model="showProjectGroups" type="checkbox" class="size-4 accent-emerald-600" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $t('Project groups') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                                        <input v-model="showProjects" type="checkbox" class="size-4 accent-emerald-600" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $t('Projects') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                                        <input v-model="showExpiredProjects" type="checkbox" class="size-4 accent-emerald-600" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $t('Show expired projects') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                                        <input v-model="showFutureProjects" type="checkbox" class="size-4 accent-emerald-600" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $t('Show future projects') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                                        <input v-model="showProjectsWithoutEvents" type="checkbox" class="size-4 accent-emerald-600" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $t('Show projects without events') }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                                        <input v-model="showOnlyProjectsWithoutGroup" type="checkbox" class="size-4 accent-emerald-600" />
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $t('Show only projects without group') }}</span>
                                    </label>
                                </div>

                                <!-- States -->
                                <div class="border-t border-zinc-200 dark:border-zinc-800 pt-2">
                                    <button
                                        type="button"
                                        class="w-full flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-200"
                                        @click="showProjectStateFilter = !showProjectStateFilter"
                                    >
                                        <span>{{ $t('Project status') }}</span>
                                        <IconChevronDown v-if="!showProjectStateFilter" class="h-5 w-5 text-zinc-500" />
                                        <IconChevronUp v-else class="h-5 w-5 text-zinc-500" />
                                    </button>
                                    <div v-if="showProjectStateFilter" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <label v-for="state in computedStates" :key="state.id" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 px-3 py-2">
                                            <input v-model="state.clicked" type="checkbox" class="size-4 accent-emerald-600" />
                                            <span class="text-sm text-zinc-700 dark:text-zinc-200 truncate">{{ state.name }}</span>
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

                    <!-- Sort -->
                    <BaseMenu show-sort-icon dots-size="size-6" menu-width="w-72" classes="ui-button">
                        <div class="flex items-center justify-between py-1 px-1">
                            <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $t('Sort by') }}</div>
                            <button type="button" class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition" @click="resetSort()">
                                {{ $t('Reset') }}
                            </button>
                        </div>
                        <MenuItem v-for="projectSortEnumName in projectSortEnumNames" :key="projectSortEnumName" v-slot="{ active }">
                            <div
                                @click="sortBy = projectSortEnumName; applyFiltersAndSort()"
                                :class="[
                                        active ? 'bg-blue-50 text-artwork-buttons-create ' : 'text-zinc-600 ',
                                        'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm rounded-md'
                                      ]"
                            >
                                {{ getSortEnumTranslation(projectSortEnumName) }}
                                <IconCheck v-if="userProjectManagementSetting.sort_by === projectSortEnumName" class="w-5 h-5" />
                            </div>
                        </MenuItem>
                    </BaseMenu>

                    <!-- Export -->
                    <button type="button" @click="openExportModal" class="ui-button">
                        <ToolTipComponent :icon="IconFileExport" icon-size="h-6 w-6" :tooltip-text="$t('Export project list')" direction="bottom" />
                    </button>

                    <button class="ui-button" @click="openCreateProjectModal"  v-if="can('create and edit own project') || role('artwork admin')">
                        <component :is="IconPlus" class="size-6" stroke-width="1"/>
                        {{ $t('New project') }}
                    </button>
                </template>
            </ToolbarHeader>

            <!-- Last visited -->
            <div class="my-5 flex items-center justify-between">
                <div class="flex items-center gap-2 pb-2" v-if="lastProject?.id">
                    <div class="text-sm text-zinc-600 dark:text-zinc-300">{{ $t('Last visited project') }}:</div>
                    <a
                        class="text-artwork-buttons-create text-sm font-semibold inline-flex items-center gap-1"
                        :href="route('projects.tab', { project: lastProject.id, projectTab: first_project_tab_id })"
                    >
                        <component :is="IconGeometry" class="size-4" />
                        <span class="truncate max-w-56">{{ lastProject.name }}</span>
                    </a>
                </div>
            </div>


            <BaseTable
                :rows="tableRows"
                :columns="tableCols"
                row-key="id"
                :total="projects?.total ?? tableRows.length"
                :page-size="perPage"
                v-model:page="page"
                @page-change="onPageChange"
                :empty-title="$t('No projects')"
                :empty-message="$t('There are currently no projects.')"
            >
                <!-- Dynamische Zellen: für jede sichtbare Component eine Cell-Renderer-Template -->
                <template
                    v-for="col in renderableCols"
                    :key="'slot-'+col.key"
                    v-slot:[`cell-${col.key}`]="{ row }"
                >
                    <!-- Inhalt: wrap + word-break + optional line-clamp -->
                    <div class="min-w-0">
                        <div class="max-w-full whitespace-normal break-words [overflow-wrap:anywhere] leading-tight line-clamp-2">
                            <component
                                :is="componentMapping['Builder' + col.type]"
                                :project="row"
                                :component="col.component"
                            />
                        </div>
                    </div>
                </template>


                <!-- Aktionen rechts (entspricht deinem alten ActionsComponent) -->
                <template #row-actions="{ row }">
                    <BaseMenu>
                        <MenuItem v-slot="{ active }">
                            <Link
                                :href="route('projects.tab', { project: row.id, projectTab: row.firstTabId })"
                                :class="[active ? 'bg-gray-50 text-indigo-700' : 'text-gray-700','group flex items-center px-4 py-2 text-sm']"
                            >
                                <IconFolderOpen class="mr-3 h-5 w-5 text-gray-500 group-hover:text-indigo-700" />
                                {{ $t('Open') }}
                            </Link>
                        </MenuItem>

                        <MenuItem v-if="can('write projects') || role('artwork admin') || checkPermissionByRow(row,'edit')" v-slot="{ active }">
                            <button
                                type="button"
                                @click="openEditRow(row)"
                                :class="[active ? 'bg-gray-50 text-indigo-700' : 'text-gray-700','group flex items-center w-full px-4 py-2 text-sm']"
                            >
                                <IconEdit class="mr-3 h-5 w-5 text-gray-500 group-hover:text-indigo-700" />
                                {{ $t('Edit basic data') }}
                            </button>
                        </MenuItem>

                        <MenuItem v-slot="{ active }">
                            <button
                                type="button"
                                @click="duplicateRow(row)"
                                :class="[active ? 'bg-gray-50 text-indigo-700' : 'text-gray-700','group flex items-center w-full px-4 py-2 text-sm']"
                                v-if="role('artwork admin') || can('write projects') || can('management projects') || checkPermissionByRow(row,'edit')"
                            >
                                <IconCopy class="mr-3 h-5 w-5 text-gray-500 group-hover:text-indigo-700" />
                                {{ $t('Duplicate') }}
                            </button>
                        </MenuItem>

                        <MenuItem v-if="role('artwork admin') || can('delete projects') || checkPermissionByRow(row,'delete')" v-slot="{ active }">
                            <button
                                type="button"
                                @click="openDeleteRow(row)"
                                :class="[active ? 'bg-gray-50 text-indigo-700' : 'text-gray-700','group flex items-center w-full px-4 py-2 text-sm']"
                            >
                                <IconTrash class="mr-3 h-5 w-5 text-gray-500 group-hover:text-indigo-700" />
                                {{ $t('Put in the trash') }}
                            </button>
                        </MenuItem>
                    </BaseMenu>
                </template>
            </BaseTable>

            <!-- Header row -->
            <div class="overflow-x-auto hidden">
                <div class="min-w-fit">
                    <div class="mb-4">
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-900/50 backdrop-blur px-3 py-2">
                            <div
                                class="grid items-center text-zinc-600 dark:text-zinc-300 text-xs font-semibold tracking-wide"
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
                            class="rounded-xl border border-zinc-200 bg-white/70 backdrop-blur overflow-hidden"
                        >
                            <div class="grid items-center" :style="`grid-template-columns: ${gridTemplateColumns}`">
                                <div v-for="i in skeletonCols" :key="i" class="px-3 py-3">
                                    <div class="h-4 w-[70%] rounded bg-zinc-200/70 animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pinned -->
                    <div v-else-if="pinnedProjects?.length" class="mt-3 space-y-4">
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
                            v-memo="[project.id, project.updated_at]"
                        />
                    </div>

                    <!-- List -->
                    <div v-if="!isLoading" class="mt-2 space-y-2">
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
                            v-memo="[project.id, project.updated_at]"
                        />
                    </div>
                </div>
            </div>

            <!-- Paginator -->
            <div class="px-1 sm:px-0 pb-8 pt-6 hidden">
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
            :project_history="projectHistoryToDisplay"
            :access_budget="projectBudgetAccess"
            @closed="closeProjectHistoryModal"
        />

        <export-modal
            v-if="showExportModal"
            @close="showExportModal = false"
            :enums="[
        exportTabEnums.EXCEL_EVENT_LIST_EXPORT,
        exportTabEnums.EXCEL_CALENDAR_EXPORT,
        exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT
      ]"
            :configuration="getExportModalConfiguration()"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {
    IconCheck,
    IconChevronDown,
    IconChevronUp, IconCirclePlus, IconEdit,
    IconFileExport,
    IconGeometry,
    IconPlus,
    IconSearch, IconTrash, IconUsersGroup,
    IconX
} from "@tabler/icons-vue";
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
import BaseTable, { type TableColumn } from '@/Artwork/Table/BaseTable.vue'
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import { IconCopy, IconEdit as IconEdit2, IconFolderOpen, IconTrash as IconTrash2 } from "@tabler/icons-vue";

const cols = ref<TableColumn[]>([
    { key: 'name',  label: 'Name',  sortable: false },
    { key: 'position', label: 'Position', sortable: false },
    { key: 'type', label: 'Type', sortable: false },
])

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
const projectBudgetAccess = ref({});
const showSearchbar = ref(route().params.query?.length > 0);
const showSuccessModal2 = ref(false);
const showProjectHistory = ref(false);
const projectHistoryToDisplay = ref([]);
const editingProject = ref(false);
const projectToEdit = ref(null);
const createProject = ref(false);
const showExportModal = ref(false);
const page = ref(route().params.page ?? 1);
const perPage = ref(props.entitiesPerPage ?? 10);
const showAddBulkEventModal = ref(false);
const dropFeedbackShown = ref(null);

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
const showProjectsWithoutEvents = ref(userProjectManagementSetting?.project_filters.showProjectsWithoutEvents);
const showOnlyProjectsWithoutGroup = ref(userProjectManagementSetting?.project_filters.showOnlyProjectsWithoutGroup);
const sortBy = ref(userProjectManagementSetting?.sort_by === null ? undefined : userProjectManagementSetting?.sort_by);

const showProjectStateFilter = ref(true);

// Grid wird 1x berechnet und an Zeilen weitergegeben → weniger Recalcs
const gridTemplateColumns = computed(() =>
    props.components
        .map((component) => {
            if (component.type === "ProjectTitleComponent") return "20rem";
            if (component.type === "ActionsComponent") return "5rem";
            return "14rem";
        })
        .join(" ")
);


// Headbar Aktionen
const openCreateProjectModal = () => (createProject.value = true);
const closeCreateProjectModal = (showSuccessModalFlag) => {
    createProject.value = false;
    if (showSuccessModalFlag) showAddBulkEventModal.value = true;
};
const toggleMyProjects = () => {
    showOnlyMyProjects.value = !showOnlyMyProjects.value;
    applyFiltersAndSort();
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
    projectHistoryToDisplay.value = [];
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
                showProjectsWithoutEvents: getTruthyOrUndefined(showProjectsWithoutEvents.value),
                showOnlyProjectsWithoutGroup: getTruthyOrUndefined(showOnlyProjectsWithoutGroup.value),
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
    showProjectsWithoutEvents.value = false;
    showOnlyProjectsWithoutGroup.value = false;

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

import BuilderProjectTitleComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectTitleComponent.vue";
import BuilderProjectTeamComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectTeamComponent.vue";
import BuilderActionsComponent from "@/Pages/Projects/BuilderComponents/BuilderActionsComponent.vue";
import BuilderTextArea from "@/Pages/Projects/BuilderComponents/BuilderTextArea.vue";
import BuilderProjectStateComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectStateComponent.vue";
import BuilderShiftContactPersonsComponent from "@/Pages/Projects/BuilderComponents/BuilderShiftContactPersonsComponent.vue";
import BuilderRelevantDatesForShiftPlanningComponent from "@/Pages/Projects/BuilderComponents/BuilderRelevantDatesForShiftPlanningComponent.vue";
import BuilderGeneralShiftInformationComponent from "@/Pages/Projects/BuilderComponents/BuilderGeneralShiftInformationComponent.vue";
import BuilderProjectBudgetDeadlineComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectBudgetDeadlineComponent.vue";
import BuilderProjectAttributesComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectAttributesComponent.vue";
import BuilderBudgetInformations from "@/Pages/Projects/BuilderComponents/BuilderBudgetInformation.vue";
import BuilderTextField from "@/Pages/Projects/BuilderComponents/BuilderTextField.vue";
import BuilderCheckbox from "@/Pages/Projects/BuilderComponents/BuilderCheckbox.vue";
import BuilderDropDown from "@/Pages/Projects/BuilderComponents/BuilderDropDown.vue";
import { useI18n } from 'vue-i18n'
const { t: $t } = useI18n()

/** Mapping: component.type -> konkrete Builder-Komponente */
const componentMapping: Record<string, any> = {
    BuilderProjectTitleComponent,
    BuilderProjectTeamComponent,
    BuilderActionsComponent,
    BuilderTextArea,
    BuilderProjectStateComponent,
    BuilderShiftContactPersonsComponent,
    BuilderRelevantDatesForShiftPlanningComponent,
    BuilderGeneralShiftInformationComponent,
    BuilderProjectBudgetDeadlineComponent,
    BuilderProjectAttributesComponent,
    BuilderBudgetInformations,
    BuilderTextField,
    BuilderCheckbox,
    BuilderDropDown,
}

/** Key-Helfer: stabile keys aus type+index */
const toColKey = (c: any, idx: number) => {
    const base = (c.key ?? c.type ?? `col_${idx}`).toString().trim().toLowerCase().replace(/\W+/g, '_')
    return base || `col_${idx}`
}

/** Optionale Breitenvorgabe analog deiner Grid-Logik */
const widthByType = (type: string) => {
    if (type === 'ProjectTitleComponent') return '20rem'
    if (type === 'ActionsComponent') return '5rem' // wird als row-actions gerendert
    return '14rem'
}

/** Sichtbarkeit wie bei dir */
const checkIfComponentIsVisible = (component: any) => {
    if (component?.type === 'ProjectTitleComponent' || component?.type === null) return true
    if (component?.type === 'ActionsComponent') return true // wir mappen später auf row-actions
    return canSeeComponent(component.component)
}

/** 1) Spalten aus `components` aufbereiten (ohne ActionsComponent im Head) */
const columnsFromComponents = computed(() => {
    return (props.components ?? [])
        .filter((c: any) => checkIfComponentIsVisible(c))
        .map((c: any, idx: number) => ({
            key: toColKey(c, idx),
            label: $t(c.name),
            align: c.type === 'ActionsComponent' ? 'right' : 'left',
            width: widthByType(c.type),
            sortable: false,
            type: c.type,
            component: c,
        }))
})

/** 2) Tabelle-Columns (Actions raus; die kommt über #row-actions) */
const tableCols = computed<TableColumn[]>(() =>
    columnsFromComponents.value
        .filter(c => c.type !== 'ActionsComponent')
        .map(c => ({
            key: c.key,
            label: c.label,
            align: c.align as 'left' | 'center' | 'right',
            width: c.width,
            sortable: c.sortable,
        }))
)

/** Für die dynamischen Slots benötigen wir alle renderbaren (ohne Actions) */
const renderableCols = computed(() => columnsFromComponents.value.filter(c => c.type !== 'ActionsComponent'))

/** Zeilenquelle: deine bereits aufgebauten Komponentenzeilen */
const tableRows = computed<any[]>(() => props.projectComponents ?? props.projects?.data ?? [])

/** Server-Pagination in der Tabelle steuern */
function onPageChange({ page: newPage, pageSize }: { page: number; pageSize: number }) {
    page.value = newPage
    perPage.value = pageSize
    // nutze deinen vorhandenen Flow; filter/sort bleiben bestehen
    applyFiltersAndSort(false)
}

/** ---- Aktionen (Kontext: row) ---- */
function fullByRow(row: any) {
    return props.projects?.data?.find((p: any) => p.id === row.id) ?? row
}

function checkPermissionByRow(row: any, type: 'view' | 'edit' | 'delete') {
    const project = fullByRow(row)
    const userId = usePage().props.auth.user.id
    const viewAuth: number[] = []
    const managerAuth: number[] = []
    const writeAuth: number[] = []
    const deleteAuth: number[] = []
    project?.users?.forEach((u: any) => viewAuth.push(u.id))
    project?.project_managers?.forEach((u: any) => managerAuth.push(u.id))
    project?.write_auth?.forEach((u: any) => writeAuth.push(u.id))
    project?.delete_permission_users?.forEach((u: any) => deleteAuth.push(u.id))
    if (type === 'view')   return viewAuth.includes(userId)
    if (type === 'edit')   return writeAuth.includes(userId)
    if (type === 'delete') return managerAuth.includes(userId) || deleteAuth.includes(userId)
    return false
}

function openEditRow(row: any) {
    editingProject.value = true
    projectToEdit.value = fullByRow(row)
}

function openDeleteRow(row: any) {
    deletingProject.value = true
    // dein Delete-Modal nutzt `project`/`fullProject` – setze dort entsprechend,
    // oder übernimm hier falls nötig einen eigenen State
    projectToEdit.value = fullByRow(row)
}

function duplicateRow(row: any) {
    router.post(
        route('projects.duplicate', { project: row.id }),
        { page: page.value, entitiesPerPage: perPage.value, query: project_search.value },
        { preserveScroll: true }
    )
}

</script>
