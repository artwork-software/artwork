<template>
    <app-layout :title="$t('Projects')">
        <div class="">
            <div class="max-w-screen my-10 flex flex-row ml-14 mr-14">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex items-center justify-end">
                        <div class="w-full flex items-center">
                            <p class="items-center flex mr-2 headline1">
                                {{ $t('Projects') }}
                            </p>
                        </div>
                        <div class="flex relative items-center gap-x-3.5">
                            <div class="flex items-center">
                                <div v-if="!showSearchbar" @click="openSearchbar"
                                     class="cursor-pointer inset-y-0">
                                    <ToolTipComponent :icon="IconSearch" icon-size="h-7 w-7" :tooltip-text="$t('Search')"
                                                      direction="bottom"/>
                                </div>
                                <div v-else class="flex items-center w-60">
                                    <div>
                                        <input type="text" ref="searchBarInput" :placeholder="$t('Search for projects')" v-model="project_search" class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
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
                                            <p class=" ml-4 my-auto text-sm text-secondary">{{
                                                    state.name
                                                }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end py-1">
                                        <div class="text-xs cursor-pointer hover:text-gray-200 transition-all duration-150 ease-in-out" @click="this.applyFiltersAndSort()">
                                            {{ $t('Apply') }}
                                        </div>
                                    </div>
                                </div>
                            </BaseFilter>
                            <BaseMenu show-sort-icon dots-size="h-7 w-7" menu-width="w-72">
                                <div class="flex items-center justify-end py-1">
                                    <span class="pr-4 pt-0.5 xxsLight cursor-pointer text-right w-full" @click="this.resetSort()">
                                        {{ $t('Reset') }}
                                    </span>
                                </div>
                                <MenuItem v-for="projectSortEnumName in projectSortEnumNames"
                                          v-slot="{ active }">
                                    <div @click="this.sortBy = projectSortEnumName; this.applyFiltersAndSort()"
                                         :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                                        {{ getSortEnumTranslation(projectSortEnumName) }}
                                        <IconCheck v-if="this.getUserSortBySetting() === projectSortEnumName" class="w-5 h-5"/>
                                    </div>
                                </MenuItem>
                            </BaseMenu>
                            <ToolTipComponent :icon="IconFileExport"
                                              icon-size="h-7 w-7"
                                              :tooltip-text="$t('Export project list')"
                                              direction="bottom"
                                              @click="openExportModal"/>
                            <div v-if="this.$page.props.show_hints" class="flex mt-1 absolute w-40 right-20">
                                <span class="hind ml-1 my-auto">{{ $t('Create new projects') }}</span>
                                <SvgCollection svgName="smallArrowRight" class="mt-1 ml-2"/>
                            </div>
                            <PlusButton v-if="$can('create and edit own project') || $role('artwork admin')" :button-text="$t('New project')"
                                        @click="openCreateProjectModal"/>
                        </div>
                    </div>
                    <!-- Tags are not wanted right now
                    <div id="selectedFilter" class="mt-3">
                        <span v-if="getUserSortBySetting()"
                            class="rounded-full items-center font-medium text-tagTextGreen border bg-tagBgGreen border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                {{ getSortEnumTranslation(this.getUserSortBySetting()) }}
                            <button type="button" @click="this.resetSort();">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="getUserProjectFilterSetting('showOnlyMyProjects')"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                            {{ $t('My projects') }}
                            <button type="button" @click="this.disableUserProjectFilterSetting('showOnlyMyProjects');">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="getUserProjectFilterSetting('showProjectGroups')"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                            {{ $t('Project groups') }}
                            <button type="button" @click="this.disableUserProjectFilterSetting('showProjectGroups');">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="getUserProjectFilterSetting('showProjects')"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                {{ $t('Projects') }}
                            <button type="button" @click="this.disableUserProjectFilterSetting('showProjects');">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="getUserProjectFilterSetting('showExpiredProjects')"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                {{ $t('Show expired projects') }}
                            <button type="button" @click="this.disableUserProjectFilterSetting('showExpiredProjects');">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="getUserProjectFilterSetting('showFutureProjects')"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                {{ $t('Show future projects') }}
                            <button type="button" @click="this.disableUserProjectFilterSetting('showFutureProjects');">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="getUserProjectFilterSetting('hideProjectsWithoutEvents')"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                {{ $t('Hide projects without events') }}
                            <button type="button" @click="this.disableUserProjectFilterSetting('hideProjectsWithoutEvents');">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>

                        <template v-for="state in computedStateTags">
                            <span v-if="state.clicked"
                                  class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                {{ state.name }}
                                <button type="button"
                                        @click="state.clicked = false; this.applyFiltersAndSort();">
                                    <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                                </button>
                            </span>
                        </template>
                    </div>
                    -->
                    <div class="my-3 w-full">
                        <div class="grid grid-cols-1 sm:grid-cols-8 lg:grid-cols-10 grid-rows-1 gap-4 w-full py-4 bg-artwork-project-background rounded-xl px-3 my-2" v-for="(project) in pinnedProjects" :key="project.id">
                            <SingleProject :categories="categories" :genres="genres" :sectors="sectors" :create-settings="createSettings" :states="states" :project-groups="projectGroups" :project="project" :first_project_tab_id="first_project_tab_id" />
                        </div>
                    </div>
                    <div class="my-3 w-full">
                        <div class="grid grid-cols-1 sm:grid-cols-8 lg:grid-cols-10 grid-rows-1 gap-4 w-full py-4 bg-artwork-project-background rounded-xl px-3 my-2" v-for="(project) in this.projects.data" :key="project.id">
                            <SingleProject :categories="categories" :genres="genres" :sectors="sectors" :create-settings="createSettings" :states="states" :project-groups="projectGroups" :project="project" :first_project_tab_id="first_project_tab_id" />
                        </div>
                    </div>

                    <BasePaginator :entities="projects"
                                   property-name="projects"
                                   :emit-update-entities-per-page="true"
                                   @update-page="this.updatePage"
                                   @update-entities-per-page="this.changeEntitiesPerPage"/>
                </div>
            </div>
        </div>
        <SideNotification v-if="this.dropFeedbackShown"
                          type="project_create_success"/>
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

        <!-- Success Modal - Delete project -->
        <SuccessModal
            v-if="showSuccessModal"
            :show="showSuccessModal"
            @closed="closeSuccessModal"
            :title="$t('Project deleted')"
            :description="$t('The project {0} has been deleted.', [projectToDelete.name])"
            :button="$t('Close')"
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
            :project="this.projectToEdit"
            :group-projects="this.projectGroups"
            :current-group="this.groupPerProject[this.projectToEdit?.id]"
            :states="states"
            @closed="closeEditProjectModal"
        />
        <project-history-component
            v-if="showProjectHistory"
            :project_id="selectedProjectId"
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
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ChevronDownIcon,
    DocumentReportIcon,
    DotsVerticalIcon,
    DuplicateIcon,
    InformationCircleIcon,
    PencilAltIcon,
    SearchIcon,
    TrashIcon,
    XIcon
} from '@heroicons/vue/outline'
import {CheckIcon, ChevronRightIcon, ChevronUpIcon, PlusSmIcon, SelectorIcon, XCircleIcon} from '@heroicons/vue/solid'
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    MenuItem,
    Switch,
    SwitchGroup,
    SwitchLabel
} from '@headlessui/vue'
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import SingleProject from "@/Pages/Projects/Components/SingleProject.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import {IconCheck, IconFileExport, IconPin, IconSearch} from "@tabler/icons-vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamTooltip from "@/Layouts/Components/TeamTooltip.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import IconLib from "@/Mixins/IconLib.vue";
import Input from "@/Jetstream/Input.vue";
import Permissions from "@/Mixins/Permissions.vue";
import projects from "@/Pages/Trash/Projects.vue";
import AddBulkEventsModal from "@/Pages/Projects/Components/AddBulkEventsModal.vue";
import debounce from 'lodash.debounce'
import SideNotification from "@/Layouts/Components/General/SideNotification.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {useSortEnumTranslation} from "@/Composeables/SortEnumTranslation.js";
import ExportModal from "@/Layouts/Components/Export/Modals/ExportModal.vue";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";

const {getSortEnumTranslation} = useSortEnumTranslation();
const exportTabEnums = useExportTabEnums();
export default defineComponent({
    components: {
        ExportModal,
        ToolTipComponent,
        SideNotification,
        IconCheck,
        MenuItem,
        AddBulkEventsModal,
        BasePaginator,
        SingleProject,
        BaseModal,
        BaseMenu,
        PlusButton,
        AddButtonSmall,
        BaseButton,
        SuccessModal,
        IconPin,
        DocumentReportIcon,
        ProjectCreateModal,
        ProjectDataEditModal,
        UserPopoverTooltip,
        Input,
        BaseFilter,
        Switch,
        ProjectHistoryComponent,
        NewUserToolTip,
        TagComponent,
        TeamIconCollection,
        SvgCollection,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        CheckIcon,
        SelectorIcon,
        InformationCircleIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        DuplicateIcon,
        ChevronRightIcon,
        Link,
        UserTooltip,
        TeamTooltip,
        InputComponent,
        Disclosure,
        DisclosurePanel,
        DisclosureButton,
        SwitchLabel,
        SwitchGroup,
    },
    props: [
        'projects',
        'states',
        'categories',
        'genres',
        'sectors',
        'can',
        'projectGroups',
        'first_project_tab_id',
        'pinnedProjects',
        'createSettings',
        'myLastProject',
        'eventTypes',
        'rooms',
        'projectSortEnumNames',
        'userProjectManagementSetting',
        'eventStatuses'
    ],
    mixins: [Permissions, IconLib],
    data() {
        return {
            project_search: route().params.query,
            showProjectHistoryTab: true,
            showBudgetHistoryTab: false,
            projectFilters: [{'name': this.$t('All projects')}, {'name': this.$t('My projects')}],
            projectFilter: {'name': this.$t('All projects')},
            isSingleTab: true,
            isGroupTab: false,
            showSearchbar: route().params.query?.length > 0,
            project_query: '',
            project_search_results: [],
            addingProject: false,
            deletingProject: false,
            projectToDelete: null,
            showSuccessModal: false,
            showSuccessModal2: false,
            nameOfDeletedProject: "",
            showProjectHistory: false,
            selectedProjectId: null,
            hasGroup: false,
            selectedGroup: null,
            showOnlyMyProjects: this.userProjectManagementSetting?.project_filters.showOnlyMyProjects,
            showProjectGroups: this.userProjectManagementSetting?.project_filters.showProjectGroups,
            showProjects: this.userProjectManagementSetting?.project_filters.showProjects,
            showExpiredProjects: this.userProjectManagementSetting?.project_filters.showExpiredProjects,
            showFutureProjects: this.userProjectManagementSetting?.project_filters.showFutureProjects,
            hideProjectsWithoutEvents: this.userProjectManagementSetting?.project_filters.hideProjectsWithoutEvents,
            sortBy: this.userProjectManagementSetting?.sort_by === null ? undefined : this.userProjectManagementSetting?.sort_by,
            showProjectStateFilter: true,
            openedMenu: false,
            editingProject: false,
            projectToEdit: null,
            createProject: false,
            showExportModal: false,
            entitiesPerPage: [10, 15, 20, 30, 50, 75, 100],
            page: route().params.page ?? 1,
            perPage: route().params.entitiesPerPage ?? 10,
            showAddBulkEventModal: false,
            dropFeedbackShown: null,
            exportTabEnums: exportTabEnums
        }
    },
    computed: {
        computedStates() {
            if (this.userProjectManagementSetting) {
                this.states.forEach((state) => {
                    state.clicked = this.userProjectManagementSetting.project_state_ids.includes(state.id);
                });
            }

            return this.states;
        },
        computedStateTags() {
            let states = [];

            this.computedStates.forEach((state) => {
                if (this.userProjectManagementSetting.project_state_ids.includes(state.id)) {
                    states.push(state);
                }
            });

            return states;
        },
        historyTabs() {
            return [
                {
                    name: this.$t('Project'),
                    href: '#',
                    current: this.showProjectHistoryTab
                },
                {
                    name: this.$t('Budget'),
                    href: '#',
                    current: this.showBudgetHistoryTab
                },
            ]
        }
    },
    methods: {
        IconFileExport,
        IconSearch,
        usePage,
        getSortEnumTranslation,
        openCreateProjectModal() {
            this.createProject = true;
        },
        closeCreateProjectModal(showSuccessModal) {
            this.createProject = false;
            if (showSuccessModal) {
                this.showAddBulkEventModal = true;
            }
        },
        openEditProjectModal(project) {
            this.projectToEdit = project;
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
            this.projectToEdit = null;
        },
        changeHistoryTabs(selectedTab) {
            this.showProjectHistoryTab = false;
            this.showBudgetHistoryTab = false;
            if (selectedTab.name === this.$t('Project')) {
                this.showProjectHistoryTab = true;
            } else {
                this.showBudgetHistoryTab = true;
            }
        },
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.project_search = '';
        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.nameOfDeletedProject = "";
            this.closeSearchbar()
        },
        openSuccessModal2() {
            this.showSuccessModal2 = true;
            setTimeout(() => this.closeSuccessModal2(), 2000)
        },
        closeSuccessModal2() {
            this.showSuccessModal2 = false;
            this.closeSearchbar()
        },
        openProjectHistoryModal(project) {
            this.selectedProjectId = project?.id ?? null;
            this.showProjectHistory = !!this.selectedProjectId;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
            this.selectedProjectId = null;
        },
        openExportModal() {
            this.showExportModal = true;
        },
        openSearchbar(){
            this.showSearchbar = !this.showSearchbar;
            this.$nextTick(() => {
                if (this.showSearchbar) {
                    this.$refs.searchBarInput.focus();
                }
            });
        },
        applyFiltersAndSort(resetPage = true) {
            router.get(
                route().current(),
                {
                    page: resetPage ? 1 : this.page,
                    entitiesPerPage: this.perPage,
                    query: route().params.query,
                    project_state_ids: this.states.filter((state) => state.clicked).map((state) => state.id),
                    project_filters: {
                        showOnlyMyProjects: this.getTruthyOrUndefined(this.showOnlyMyProjects),
                        showProjectGroups: this.getTruthyOrUndefined(this.showProjectGroups),
                        showProjects: this.getTruthyOrUndefined(this.showProjects),
                        showExpiredProjects: this.getTruthyOrUndefined(this.showExpiredProjects),
                        showFutureProjects: this.getTruthyOrUndefined(this.showFutureProjects),
                        hideProjectsWithoutEvents: this.getTruthyOrUndefined(this.hideProjectsWithoutEvents)
                    },
                    sort: this.sortBy,
                    saveFilterAndSort: 1
                }
            );
        },
        resetFilter() {
            router.get(
                route().current(),
                {
                    page: 1,
                    entitiesPerPage: this.perPage,
                    query: route().params.query,
                    project_states: undefined,
                    project_filters: undefined,
                    sort: this.sortBy,
                    saveFilterAndSort: 1
                }
            );
        },
        resetSort() {
            this.sortBy = undefined;
            this.applyFiltersAndSort();
        },
        updatePage(page, entitiesPerPage) {
            this.page = page;
            this.perPage = entitiesPerPage;
            this.applyFiltersAndSort(false);
        },
        changeEntitiesPerPage(entitiesPerPage) {
            this.perPage = entitiesPerPage;
            this.applyFiltersAndSort();
        },
        getTruthyOrUndefined(value) {
            return value ? 1 : undefined;
        },
        getUserSortBySetting() {
            return this.getUserProjectManagementSetting()?.sort_by;
        },
        getUserProjectFilterSetting(setting) {
            return this.getUserProjectManagementSetting()?.project_filters[setting];
        },
        disableUserProjectFilterSetting(setting) {
            this[setting] = false;
            this.applyFiltersAndSort();
        },
        getUserProjectManagementSetting() {
            return this.$page.props.auth.userProjectManagementSetting;
        },
        reloadProjects() {
            router.reload({
                only: ['projects'],
                data: {
                    query: this.project_search,
                    page: 1,
                    entitiesPerPage: this.projects.per_page
                }
            });
        },
        reloadProjectsDebounced: debounce(function() {
            this.reloadProjects();
        }, 1000),
        showDropFeedback() {
            this.dropFeedbackShown = true;
            setTimeout(() => {
                this.dropFeedbackShown = false;
            }, 3000)
        },
        getExportModalConfiguration() {
            const cfg = {};

            cfg[exportTabEnums.EXCEL_EVENT_LIST_EXPORT] = {
                show_artists: this.createSettings.show_artists,
            };

            return cfg;
        }
    },
    watch: {
        project_search: {
            handler() {
                this.reloadProjectsDebounced();
            }
        }
    }
});
</script>

<style scoped>
.whiteColumn {
    background-color: #FCFCFBFF;
}

.greenColumn {
    background-color: #50908E;
    border: 2px solid #1FC687;
}

.yellowColumn {
    background-color: #F0B54C;
}

.redColumn {
    background-color: #D84387;
}

.lightGreenColumn {
    background-color: #35A965;
}
</style>
