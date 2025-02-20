<template>
    <AppLayout title="Projektübersicht">
        <div class="">
            <div class="max-w-screen my-10 flex flex-col ml-14 mr-14">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex items-center justify-end">
                        <div class="w-full flex items-center">
                            <div class="w-full flex items-center">
                                <h1 class="items-center flex mr-2 headline1">
                                    {{ $t('Projects') }}
                                </h1>
                            </div>
                            <div class="flex relative items-center gap-x-3.5">
                                <div class="flex items-center">
                                    <div v-if="!showSearchbar" @click="openSearchbar"
                                         class="cursor-pointer inset-y-0">
                                        <ToolTipComponent icon="IconSearch" icon-size="h-7 w-7" :tooltip-text="$t('Search')"
                                                          direction="bottom"/>
                                    </div>
                                    <div v-else class="flex items-center w-60">
                                        <div>
                                            <input type="text" ref="searchBarInput" id="searchBarInput" :placeholder="$t('Search for projects')" v-model="project_search" class="h-10 inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-artwork-buttons-create"/>
                                        </div>
                                        <IconX class="ml-2 cursor-pointer h-7 w-7 text-artwork-buttons-context" @click="closeSearchbar()"/>
                                    </div>
                                </div>
                                <BaseFilter only-icon="true" :left="false">
                                    <div class="w-full">
                                        <div class="flex justify-end mb-3">
                                        <span class="xxsLight cursor-pointer text-right w-full" @click="resetFilter">
                                            {{ $t('Reset') }}
                                        </span>
                                        </div>
                                        <SwitchGroup as="div" class="flex items-center">
                                            <Switch v-model="showOnlyMyProjects"
                                                    :class="[showOnlyMyProjects ? 'bg-green-400' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                                                <span class="sr-only">Use setting</span>
                                                <span aria-hidden="true"
                                                      :class="[showOnlyMyProjects ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                                            </Switch>
                                            <SwitchLabel as="span" class="ml-3 xxsLight">
                                                {{ $t('Show only my projects') }}
                                            </SwitchLabel>
                                        </SwitchGroup>
                                        <div class="flex max-h-8 mb-3 mt-3">
                                            <input v-model="showProjectGroups"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">
                                                {{ $t('Project groups') }}
                                            </p>
                                        </div>
                                        <div class="flex max-h-8 mb-3 mt-3">
                                            <input v-model="showProjects"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">
                                                {{ $t('Projects') }}
                                            </p>
                                        </div>
                                        <div class="flex max-h-8 mb-3 mt-3">
                                            <input v-model="showExpiredProjects"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">
                                                {{ $t('Show expired projects') }}
                                            </p>
                                        </div>
                                        <div class="flex max-h-8 mb-3 mt-3">
                                            <input v-model="showFutureProjects"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">
                                                {{ $t('Show future projects') }}
                                            </p>
                                        </div>
                                        <div class="flex max-h-8 mb-3 mt-3">
                                            <input v-model="showProjectsWithoutEvents"
                                                   type="checkbox"
                                                   class="input-checklist-dark"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">
                                                {{ $t('Show projects without events') }}
                                            </p>
                                        </div>
                                        <div class="flex justify-between xsLight mb-3"
                                             @click="showProjectStateFilter = !showProjectStateFilter">
                                            {{ $t('Project status') }}
                                            <IconChevronDown stroke-width="1.5" class="h-5 w-5"
                                                             v-if="!showProjectStateFilter"
                                                             aria-hidden="true"/>
                                            <IconChevronUp stroke-width="1.5" class="h-5 w-5"
                                                           v-if="showProjectStateFilter"
                                                           aria-hidden="true"/>
                                        </div>
                                        <div v-if="showProjectStateFilter">
                                            <div class="flex mb-3" v-for="state in computedStates">
                                                <input v-model="state.clicked"
                                                       type="checkbox"
                                                       class="input-checklist-dark"/>
                                                <p class=" ml-4 my-auto text-sm text-secondary">{{state.name }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-end py-1">
                                            <div class="text-xs cursor-pointer hover:text-gray-200 transition-all duration-150 ease-in-out" @click="applyFiltersAndSort()">
                                                {{ $t('Apply') }}
                                            </div>
                                        </div>
                                    </div>
                                </BaseFilter>
                                <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
                                    <div class="flex items-center justify-end py-1">
                                    <span class="pr-4 pt-0.5 xxsLight cursor-pointer text-right w-full" @click="resetSort()">
                                        {{ $t('Reset') }}
                                    </span>
                                    </div>
                                    <MenuItem v-for="projectSortEnumName in projectSortEnumNames" v-slot="{ active }">
                                        <div @click="sortBy = projectSortEnumName; applyFiltersAndSort()"
                                             :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                            {{ getSortEnumTranslation(projectSortEnumName) }}
                                            <IconCheck v-if="userProjectManagementSetting.sort_by === projectSortEnumName" class="w-5 h-5"/>
                                        </div>
                                    </MenuItem>
                                </BaseMenu>
                                <ToolTipComponent icon="IconFileExport"
                                                  icon-size="h-7 w-7"
                                                  :tooltip-text="$t('Export project list')"
                                                  direction="bottom"
                                                  @click="openExportModal"/>
                                <div v-if="$page.props.show_hints" class="flex mt-1 absolute w-40 right-20">
                                    <span class="hind ml-1 my-auto">{{ $t('Create new projects') }}</span>
                                    <SvgCollection svgName="smallArrowRight" class="mt-1 ml-2"/>
                                </div>
                                <PlusButton v-if="can('create and edit own project') || role('artwork admin')" :button-text="$t('New project')"
                                            @click="openCreateProjectModal"/>
                            </div>
                        </div>


                    </div>
                    <div class="mt-4 gap-x-1 flex items-center" v-if="lastProject?.id">
                        <div class="xsDark">
                            {{ $t('Last visited project') }}:
                        </div>
                        <a class="text-artwork-buttons-create text-sm underline underline-offset-2 font-bold" :href="route('projects.tab', { project: lastProject.id, projectTab: first_project_tab_id })">{{ lastProject.name }}</a>
                    </div>
                </div>
            </div>

            <div class="relative mx-14">
                <!-- Scrollbarer Container -->
                <div class="overflow-x-auto w-full mb-10">
                    <!-- Fixierte Topbar -->
                    <div class="sticky top-0 z-10 bg-gray-100 w-fit mb-4 rounded-lg">
                        <div class="grid px-3 py-3 " :style="`grid-template-columns: ${gridTemplateColumns}`">
                            <div v-for="component in components" :key="component.name"  :class="component.type === 'ActionsComponent' ? 'flex justify-end' : ''" class="px-3 text-left flex items-center" >
                                <h3 v-if="checkIfComponentIsVisible(component)" class="xsDark">{{ $t(component.name) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3 mb-3 w-fit">
                        <SingleProjectInManagement
                            v-for="project in pinnedProjects"
                            :key="project.id"
                            :project="project"
                            :components="components"
                            :categories="categories"
                            :genres="genres"
                            :sectors="sectors"
                            :states="states"
                            :projectGroups="projectGroups"
                            :createSettings="createSettings"
                            :fullProject="pinnedProjectsAll.find((p) => p.id === project.id)"
                        />


                    </div>
                    <!-- Tabelle -->
                    <div class="space-y-3 w-fit">
                        <SingleProjectInManagement
                            v-for="project in projectComponents"
                            :key="project.id"
                            :project="project"
                            :components="components"
                            :categories="categories"
                            :genres="genres"
                            :sectors="sectors"
                            :states="states"
                            :projectGroups="projectGroups"
                            :createSettings="createSettings"
                            :fullProject="projects.data.find((p) => p.id === project.id)"
                        />


                    </div>
                </div>

                <BasePaginator
                    :entities="projects"
                    property-name="projects"
                    :emit-update-entities-per-page="true"
                    @update-page="updatePage"
                    @update-entities-per-page="changeEntitiesPerPage"/>
            </div>
        </div>

        <SideNotification v-if="dropFeedbackShown" type="project_create_success"/>
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
        <export-modal v-if="showExportModal"
                      @close="showExportModal = false"
                      :enums="[
                          exportTabEnums.EXCEL_EVENT_LIST_EXPORT,
                          exportTabEnums.EXCEL_CALENDAR_EXPORT,
                          exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT
                      ]"
                      :configuration="getExportModalConfiguration()"/>

    </AppLayout>
</template>

<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import {IconCheck, IconChevronDown, IconChevronUp, IconX} from "@tabler/icons-vue";
import Input from "@/Jetstream/Input.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import { usePermission } from "@/Composeables/Permission.js";
import {MenuItem, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {computed, nextTick, ref, watch} from "vue";
import {useSortEnumTranslation} from "@/Composeables/SortEnumTranslation.js";
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
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";

const {can, hasAdminRole, role, canSeeComponent, canEditComponent} = usePermission(usePage().props);
const {getSortEnumTranslation} = useSortEnumTranslation();
const exportTabEnums = useExportTabEnums();
const props = defineProps({
    components: {
        type: Object,
        required: true,
    },
    projectComponents: {
        type: Object,
        required: true,
    },
    projects: {
        type: Object,
        required: true,
    },
    projectSortEnumNames: {
        type: Object,
        required: true,
    },
    eventTypes: {
        type: Object,
        required: true,
    },
    rooms: {
        type: Object,
        required: true,
    },
    userProjectManagementSetting: {
        type: Object,
        required: true,
    },
    eventStatuses: {
        type: Object,
        required: true,
    },
    sectors: {
        type: Object,
        required: true,
    },
    genres: {
        type: Object,
        required: true,
    },
    categories: {
        type: Object,
        required: true,
    },
    states: {
        type: Object,
        required: true,
    },
    projectGroups: {
        type: Object,
        required: true,
    },
    createSettings: {
        type: Object,
        required: true,
    },
    first_project_tab_id: {
        type: Number,
        required: true,
    },
    pinnedProjects: {
        type: Object,
        required: true,
    },
    myLastProject: {
        type: Object,
        required: false,
    },
    pinnedProjectsAll: {
        type: Object,
        required: true,
    },
    lastProject: {
        type: Object,
        required: true,
    },
    entitiesPerPage: {
        type: Number,
        required: true,
    },
})



const project_search = ref(route().params.query);
const showProjectHistoryTab = ref(true);
const showBudgetHistoryTab = ref(false);
const projectBudgetAccess = ref({});
const showSearchbar = ref(route().params.query?.length > 0);
const showSuccessModal = ref(false);
const showSuccessModal2 = ref(false);
const nameOfDeletedProject = ref('');
const showProjectHistory = ref(false);
const projectHistoryToDisplay = ref([]);

// Project management settings
const userProjectManagementSetting = props.userProjectManagementSetting; // Assuming it's passed or available in the context
const showOnlyMyProjects = ref(props.userProjectManagementSetting?.project_filters.showOnlyMyProjects);
const showProjectGroups = ref(props.userProjectManagementSetting?.project_filters.showProjectGroups);
const showProjects = ref(props.userProjectManagementSetting?.project_filters.showProjects);
const showExpiredProjects = ref(props.userProjectManagementSetting?.project_filters.showExpiredProjects);
const showFutureProjects = ref(props.userProjectManagementSetting?.project_filters.showFutureProjects ?? false);
const showProjectsWithoutEvents = ref(props.userProjectManagementSetting?.project_filters.showProjectsWithoutEvents);
const sortBy = ref(props.userProjectManagementSetting?.sort_by === null ? undefined : props.userProjectManagementSetting?.sort_by);

const showProjectStateFilter = ref(true);
const editingProject = ref(false);
const projectToEdit = ref(null);
const createProject = ref(false);
const showExportModal = ref(false);
const entitiesPerPage = ref([10, 15, 20, 30, 50, 75, 100]);
const page = ref(route().params.page ?? 1);
const perPage = ref(props.entitiesPerPage ?? 10);
const showAddBulkEventModal = ref(false);
const dropFeedbackShown = ref(null);



const gridTemplateColumns = computed(() =>
    props.components
        .map((component) => {
            if (component.type === 'ProjectTitleComponent') {
                return '20rem'; // w-80
            } else if (component.type === 'ActionsComponent') {
                return '5rem'; // Automatisch anpassen
            } else {
                return '14rem'; // w-56
            }
        })
        .join(' ')
);



const checkIfComponentIsVisible = (component) => {
    if (
        component?.type === 'ProjectTitleComponent' ||
        component?.type === 'ActionsComponent' ||
        component?.type === null
    ) {
        return true;
    } else {
        return canSeeComponent(component.component);
    }
}

const openCreateProjectModal = () => {
    createProject.value = true;
};

const closeCreateProjectModal = (showSuccessModalFlag) => {
    createProject.value = false;
    if (showSuccessModalFlag) {
        showAddBulkEventModal.value = true;
    }
};

const closeEditProjectModal = () => {
    editingProject.value = false;
    projectToEdit.value = null;
};


const closeSearchbar = () => {
    showSearchbar.value = !showSearchbar.value;
    project_search.value = '';
};

const openSearchbar = () => {
    showSearchbar.value = !showSearchbar.value;
    nextTick(() => {
        if (showSearchbar.value) {
            document.querySelector('#searchBarInput')?.focus();
        }
    });
};

const closeSuccessModal = () => {
    showSuccessModal.value = false;
    nameOfDeletedProject.value = "";
    closeSearchbar();
};


const closeSuccessModal2 = () => {
    showSuccessModal2.value = false;
    closeSearchbar();
};


const closeProjectHistoryModal = () => {
    showProjectHistory.value = false;
    projectHistoryToDisplay.value = [];
};

const openExportModal = () => {
    showExportModal.value = true;
};

const applyFiltersAndSort = (resetPage = true) => {
    router.post(route('projects.filter'), {
        project_state_ids: props.states.filter((state) => state.clicked).map((state) => state.id),
        project_filters: {
            showOnlyMyProjects: getTruthyOrUndefined(showOnlyMyProjects.value),
            showProjectGroups: getTruthyOrUndefined(showProjectGroups.value),
            showProjects: getTruthyOrUndefined(showProjects.value),
            showExpiredProjects: getTruthyOrUndefined(showExpiredProjects.value),
            showFutureProjects: getTruthyOrUndefined(showFutureProjects.value),
            showProjectsWithoutEvents: getTruthyOrUndefined(showProjectsWithoutEvents.value),
        },
        sort: sortBy.value,
    }, {
        preserveState: false,
        onSuccess: () => {
            reloadProjects(resetPage);
        },
    });
};

const resetFilter = () => {
    router.post(route('projects.filter'), {
        page: 1,
        entitiesPerPage: perPage.value,
        query: route().params.query,
        project_states: undefined,
        project_filters: undefined,
        sort: sortBy.value,
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

const reloadProjects = (resetPage = true) => {
    router.reload({
        only: ['projects', 'pinnedProjects', 'projectComponents', 'components', 'pinnedProjectsAll'],
        data: {
            page: resetPage ? 1 : page.value,
            entitiesPerPage: perPage.value,
            query: project_search.value,
        },
    });
};

const reloadProjectsDebounced = debounce(reloadProjects, 1000);

const showDropFeedback = () => {
    dropFeedbackShown.value = true;
    setTimeout(() => {
        dropFeedbackShown.value = false;
    }, 3000);
};

const getExportModalConfiguration = () => ({
    [exportTabEnums.EXCEL_EVENT_LIST_EXPORT]: {
        show_artists: props.createSettings.show_artists,
    },
});

const computedStates = computed(() => {
    if (props.userProjectManagementSetting) {
        props.states.forEach((state) => {
            state.clicked = props.userProjectManagementSetting.project_state_ids.includes(state.id);
        });
    }
    return props.states;
});

const computedStateTags = computed(() => {
    return computedStates.value.filter((state) =>
        props.userProjectManagementSetting.project_state_ids.includes(state.id)
    );
});

const historyTabs = computed(() => [
    {
        name: 'Project',
        href: '#',
        current: showProjectHistoryTab.value,
    },
    {
        name: 'Budget',
        href: '#',
        current: showBudgetHistoryTab.value,
    },
]);

// watch on project_search
watch(project_search, (value) => {
    reloadProjectsDebounced();
});
</script>

<style scoped>

</style>