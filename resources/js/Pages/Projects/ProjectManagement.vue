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
                        <div class="flex relative items-center gap-x-3.5" v-if="$can('create and edit own project') || $role('artwork admin')">
                            <div class="flex items-center">
                                <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                     class="cursor-pointer inset-y-0">
                                    <IconSearch class="h-7 w-7 text-artwork-buttons-context" aria-hidden="true"/>
                                </div>
                                <div v-else class="flex items-center w-60">
                                    <div>
                                        <input type="text" :placeholder="$t('Search for projects')" v-model="project_search" class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <IconX class="ml-2 cursor-pointer h-7 w-7 text-artwork-buttons-context" @click="closeSearchbar()"/>
                                </div>
                            </div>

                            <BaseFilter only-icon="true" :left="false">
                                <div class="w-full">
                                    <div class="flex justify-end mb-3">
                                            <span class="xxsLight cursor-pointer text-right w-full" @click="removeFilter">
                                                {{ $t('Reset') }}
                                            </span>
                                    </div>
                                    <SwitchGroup as="div" class="flex items-center">
                                        <Switch v-model="enabled"
                                                :class="[enabled ? 'bg-green-400' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                                            <span class="sr-only">Use setting</span>
                                            <span aria-hidden="true"
                                                  :class="[enabled ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                                        </Switch>
                                        <SwitchLabel as="span" class="ml-3 xxsLight">
                                            {{ $t('Show only my projects') }}
                                        </SwitchLabel>
                                    </SwitchGroup>
                                    <div class="flex max-h-8 mb-3 mt-3">
                                        <input v-model="showProjectGroups"
                                               type="checkbox"
                                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                        <p class=" ml-4 my-auto text-sm text-secondary">
                                            {{ $t('Project groups') }}
                                        </p>
                                    </div>
                                    <div class="flex max-h-8 mb-3 mt-3">
                                        <input v-model="showProjects"
                                               type="checkbox"
                                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                        <p class=" ml-4 my-auto text-sm text-secondary">
                                            {{ $t('Projects') }}
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
                                        <div class="flex mb-3" v-for="state in states">
                                            <input v-model="state.clicked" @change="addStateToFilter(state)"
                                                   type="checkbox"
                                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">{{
                                                    state.name
                                                }}</p>
                                        </div>
                                    </div>
                                </div>
                            </BaseFilter>
                            <IconFileExport class="h-7 w-7 cursor-pointer text-artwork-buttons-context" aria-hidden="true"
                                            @click="openProjectExportBudgetsByBudgetDeadlineModal"/>
                            <div v-if="this.$page.props.show_hints" class="flex mt-1 absolute w-40 right-20">
                                <span class="hind ml-1 my-auto">{{ $t('Create new projects') }}</span>
                                <SvgCollection svgName="smallArrowRight" class="mt-1 ml-2"/>
                            </div>
                            <PlusButton @click="openCreateProjectModal" />
                        </div>
                    </div>
                    <div id="selectedFilter" class="mt-3">
                        <span v-if="enabled"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                            {{ $t('My projects') }}
                            <button type="button" @click="enabled = !enabled">
                                <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="showProjectGroups"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    {{ $t('Project groups') }}
                                    <button type="button" @click="showProjectGroups = !showProjectGroups">
                                        <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                        <span v-if="showProjects"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    {{ $t('Projects') }}
                                    <button type="button" @click="showProjects = !showProjects">
                                        <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                        <span v-for="state in states">
                                <span v-if="state.clicked"
                                      class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                {{ state.name }}
                                <button type="button"
                                        @click="this.projectStateFilter.splice(this.projectStateFilter.indexOf(state),1); state.clicked = false">
                                    <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                                </button>
                            </span>
                        </span>
                    </div>

                    <div class="my-3 w-full">
                        <div class="grid grid-cols-1 sm:grid-cols-8 lg:grid-cols-10 grid-rows-1 gap-4 w-full py-4 bg-artwork-project-background rounded-xl px-3 my-2" v-for="(project,index) in pinnedProjects" :key="project.id">
                            <SingleProject :states="states" :project-groups="projectGroups" :project="project" :first_project_tab_id="first_project_tab_id" />
                        </div>
                    </div>
                    <div class="my-3 w-full">
                        <div class="grid grid-cols-1 sm:grid-cols-8 lg:grid-cols-10 grid-rows-1 gap-4 w-full py-4 bg-artwork-project-background rounded-xl px-3 my-2" v-for="(project,index) in filteredProjects" :key="project.id">
                            <SingleProject :states="states" :project-groups="projectGroups" :project="project" :first_project_tab_id="first_project_tab_id" />
                        </div>
                    </div>

                    <BasePaginator :entities="projects" property-name="projects" />

                </div>
            </div>
        </div>

        <project-create-modal
            v-if="createProject"
            :show="createProject"
            :categories="categories"
            :genres="genres"
            :sectors="sectors"
            :project-groups="this.projectGroups"
            :states="states"
            @close-create-project-modal="closeCreateProjectModal"
            :create-settings="createSettings"
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
            :project_history="projectHistoryToDisplay"
            :access_budget="projectBudgetAccess"
            @closed="closeProjectHistoryModal"
        />
        <project-export-budgets-by-budget-deadline-modal
            v-if="showProjectExportBudgetsByBudgetDeadlineModal"
            :show="showProjectExportBudgetsByBudgetDeadlineModal"
            @closeProjectExportBudgetsByBudgetDeadlineModal="closeProjectExportBudgetsByBudgetDeadlineModal"
        />
    </app-layout>
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DotsVerticalIcon,
    ChevronDownIcon,
    InformationCircleIcon,
    XIcon,
    PencilAltIcon,
    TrashIcon,
    DuplicateIcon,
    DocumentReportIcon
} from '@heroicons/vue/outline'
import {
    ChevronUpIcon,
    PlusSmIcon,
    CheckIcon,
    SelectorIcon,
    XCircleIcon,
    ChevronRightIcon
} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
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
import {IconPin} from "@tabler/icons-vue";
import ProjectExportBudgetsByBudgetDeadlineModal
    from "@/Layouts/Components/ProjectExportBudgetsByBudgetDeadlineModal.vue";
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

export default defineComponent({
    components: {
        BasePaginator,
        SingleProject,
        BaseModal,
        BaseMenu,
        PlusButton,
        AddButtonSmall,
        BaseButton,
        SuccessModal,
        IconPin,
        ProjectExportBudgetsByBudgetDeadlineModal,
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
        'createSettings'
    ],
    mixins: [Permissions, IconLib],
    data() {
        return {
            project_search: this.$page.props.urlParameters.search ?? '',
            showProjectHistoryTab: true,
            showBudgetHistoryTab: false,
            projectBudgetAccess: {},
            projectFilters: [{'name': this.$t('All projects')}, {'name': this.$t('My projects')}],
            projectFilter: {'name': this.$t('All projects')},
            isSingleTab: true,
            isGroupTab: false,
            showSearchbar: false,
            project_query: '',
            project_search_results: [],
            addingProject: false,
            deletingProject: false,
            projectToDelete: null,
            showSuccessModal: false,
            showSuccessModal2: false,
            nameOfDeletedProject: "",
            showProjectHistory: false,
            projectHistoryToDisplay: [],
            hasGroup: false,
            selectedGroup: null,
            enabled: false,
            showProjectGroups: false,
            showProjects: false,
            showProjectStateFilter: false,
            projectStateFilter: [],
            openedMenu: false,
            editingProject: false,
            projectToEdit: null,
            createProject: false,
            showProjectExportBudgetsByBudgetDeadlineModal: false,
            entitiesPerPage: [10, 15, 20, 30, 50, 75, 100],
        }
    },
    computed: {
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
        },
        filteredProjects() {
            return this.projects?.data?.filter(project => {
                // Check if the project should be included based on user-related status
                if (this.enabled && !project.curr_user_is_related) {
                    return false;
                }

                // Check if the project should be included based on project type
                if (this.showProjectGroups && !project.is_group) {
                    return false;
                }

                // Check if the project should be included based on state filter
                if (this.projectStateFilter.length > 0 && !this.projectStateFilter.includes(project?.state?.id)) {
                    return false;
                }

                // Check if the project name contains the search term
                return project.name.toLowerCase().includes(this.project_search.toLowerCase());
            });
        },
        // sort Projects by pinned_by_users array. if user id in array, project is pinned and in sort function it will be first

    },
    methods: {
        usePage,

        openCreateProjectModal() {
            this.createProject = true;
        },
        closeCreateProjectModal(showSuccessModal) {
            this.createProject = false;
            if (showSuccessModal) {
                this.openSuccessModal2();
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
        addStateToFilter(state) {
            if (!state.clicked) {
                this.projectStateFilter.splice(this.projectStateFilter.indexOf(state), 1);
            } else {
                this.projectStateFilter.push(state.id)
            }
        },
        removeFilter() {
            this.enabled = false;
            this.showProjectGroups = false;
            this.showProjects = false;
            this.projectStateFilter = []
            this.states.forEach((state) => {
                state.clicked = false
            })
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
            this.projectHistoryToDisplay = project.project_history;
            this.projectBudgetAccess = project.access_budget;
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
            this.projectHistoryToDisplay = [];
        },

        openProjectExportBudgetsByBudgetDeadlineModal() {
            this.showProjectExportBudgetsByBudgetDeadlineModal = true;
        },
        closeProjectExportBudgetsByBudgetDeadlineModal() {
            this.showProjectExportBudgetsByBudgetDeadlineModal = false;
        }
    },
    watch: {
        project_search: {
            handler() {
                router.reload({
                    only: ['projects'],
                    data: {
                        search: this.project_search,
                        page: 1
                    }
                })
            }
        }
    }
})
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
