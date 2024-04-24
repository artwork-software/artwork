<template>
    <app-layout>
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
                                                :class="[enabled ? 'bg-green-400' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
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
                        <div class="grid grid-cols-1 sm:grid-cols-8 lg:grid-cols-10 grid-rows-1 gap-4 w-full py-4 bg-artwork-project-background rounded-xl px-3 my-2" v-for="(project,index) in sortedProjects" :key="project.id">
                            <div class="col-span-7 flex items-center">
                                <div class="grid grid-cols-10 gap-x-3">
                                    <div class="col-span-1 flex items-center justify-center">
                                        <div class="flex justify-center items-center relative bg-gray-200 rounded-full h-12 w-12">
                                            <img :src="'/storage/keyVisual/' + project.key_visual" alt="" class="rounded-full h-12 w-12" v-if="project.key_visual !== null">
                                            <img src="/Svgs/IconSvgs/placeholder.svg" alt="" class="rounded-full h-5 w-5" v-else>
                                            <div class="absolute flex items-center justify-center w-7 h-7" v-if="project.is_group">
                                                <img src="Svgs/IconSvgs/icon_project_group.svg" alt="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-9 flex items-center">
                                        <div class="flex items-center">
                                            <Link v-if="
                                                $can('view projects') ||
                                                $can('management projects') ||
                                                $can('write projects') ||
                                                $role('artwork admin') ||
                                                $role('budget admin') ||
                                                checkPermission(project, 'edit') ||
                                                checkPermission(project, 'view')"
                                                  :href="getEditHref(project)"
                                                  class="flex w-full my-auto">
                                                <p class="xsDark flex items-center">
                                                    {{ truncate(project.name, 30, '...') }}
                                                </p>
                                            </Link>
                                            <div v-else class="flex w-full my-auto items-center">
                                                <p class="xsDark flex items-center">
                                            <span v-if="project.is_group">
                                                <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-5 w-5 mr-2"
                                                     aria-hidden="true"/>
                                            </span>
                                                    {{ truncate(project.name, 80, '...') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-3 flex items-center justify-end">
                                <div class="grid grid-cols-8">
                                    <div class="col-span-6">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium break-keep" :class="project.state?.color">
                                            {{ project.state?.name }}
                                        </span>
                                    </div>
                                    <div class="col-span-1">
                                        <div v-if="project.pinned_by_users && project.pinned_by_users.includes($page.props.user.id)"
                                             class="flex items-center xxsLight subpixel-antialiased">
                                            <IconPinned class="h-5 w-5 text-primary"/>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <Menu v-if="this.checkPermission(project, 'edit') || checkPermission(project, 'delete') || $role('artwork admin') || $can('delete projects') || $can('write projects')" as="div" class="flex">
                                            <div class="flex p-0.5 rounded-full relative">
                                                <div v-if="this.$page.props.show_hints && index === 0" class="absolute flex items-center w-40 right-1 -bottom-5">
                                                    <div class="flex">
                                                    <span class="mr-2 hind mt-1">
                                                        {{ $t('Edit the projects') }}
                                                    </span>
                                                    </div>
                                                    <div>
                                                        <SvgCollection svgName="arrowUpRight" class="ml-2 rotate-45"/>
                                                    </div>
                                                </div>
                                                <MenuButton class="flex">
                                                    <IconDotsVertical class=" flex-shrink-0 h-6 w-6 my-auto" aria-hidden="true"/>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems class="origin-top-right z-50 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                    <div class="py-1">
                                                        <MenuItem v-slot="{ active }"
                                                                  v-if="$role('artwork admin') || $can('write projects') || this.checkPermission(project, 'edit')">
                                                            <a @click="openEditProjectModal(project)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                                                <IconEdit stroke-width="1.5"
                                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                          aria-hidden="true"/>
                                                                {{ $t('Edit basic data') }}
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem class="cursor-pointer" v-slot="{ active }" v-if="project.pinned_by_users && project.pinned_by_users.includes($page.props.user.id)">
                                                            <a @click="pinProject(project)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <IconPinnedOff stroke-width="1.5"
                                                                               class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                               aria-hidden="true"/>
                                                                {{  $t('Undo pinning') }}
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem class="cursor-pointer" v-slot="{ active }" v-else>
                                                            <a @click="pinProject(project)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <IconPin stroke-width="1.5"
                                                                         class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                         aria-hidden="true"/>
                                                                {{  $t('Pin') }}
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem v-slot="{ active }"
                                                                  v-if="$role('artwork admin') || $can('write projects') || $can('management projects') || this.checkPermission(project, 'edit')">
                                                            <a href="#" @click="duplicateProject(project)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <IconCopy stroke-width="1.5"
                                                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                          aria-hidden="true"/>
                                                                {{ $t('Duplicate') }}
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem v-slot="{ active }"
                                                                  v-if="$role('artwork admin') || $can('delete projects') || this.checkPermission(project, 'delete')">
                                                            <a href="#" @click="openDeleteProjectModal(project)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <IconTrash stroke-width="1.5"
                                                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                           aria-hidden="true"/>
                                                                {{ $t('Put in the trash') }}
                                                            </a>
                                                        </MenuItem>
                                                    </div>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
            @close-create-project-modal="closeCreateProjectModal"
        />
        <jet-dialog-modal v-if="deletingProject" :show="deletingProject" @close="closeDeleteProjectModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        {{ $t('Delete project') }}
                    </div>
                    <XIcon @click="closeDeleteProjectModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        {{ $t('Are you sure you want to delete the project?', [projectToDelete.name]) }}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-buttonBlue hover:bg-buttonHover rounded-full focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
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
            </template>
        </jet-dialog-modal>
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
import Button from "@/Jetstream/Button";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import Checkbox from "@/Layouts/Components/Checkbox";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Inertia} from "@inertiajs/inertia";
import {Link} from "@inertiajs/inertia-vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import projects from "@/Pages/Trash/Projects";
import InputComponent from "@/Layouts/Components/InputComponent";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import Dropdown from "@/Jetstream/Dropdown.vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import Permissions from "@/mixins/Permissions.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import ProjectExportBudgetsByBudgetDeadlineModal from "@/Layouts/Components/ProjectExportBudgetsByBudgetDeadlineModal.vue";
import {IconPin} from "@tabler/icons-vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import IconLib from "@/mixins/IconLib.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";

export default defineComponent({
    components: {
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
        Dropdown,
        Switch,
        ProjectHistoryComponent,
        NewUserToolTip,
        TagComponent,
        TeamIconCollection,
        SvgCollection,
        Button,
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
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        InformationCircleIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
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
        'users',
        'categories',
        'genres',
        'sectors',
        'can',
        'projectGroups'
    ],
    mixins: [Permissions, IconLib],
    data() {
        return {
            project_search: '',
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
            showProjectExportBudgetsByBudgetDeadlineModal: false
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
            return this.projects.filter(project => {
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
        sortedProjects() {
            return this.filteredProjects.sort((a, b) => {
                if (a.pinned_by_users && a.pinned_by_users.includes(this.$page.props.user.id)) {
                    return -1;
                }
                if (b.pinned_by_users && b.pinned_by_users.includes(this.$page.props.user.id)) {
                    return 1;
                }
                return 0;
            });
        },
        groupPerProject() {
            let groupPerProject = [];
            this.projectGroups.forEach((projectGroup) => {
                projectGroup.groups?.forEach((groupProject) => {
                    groupPerProject[groupProject.id] = projectGroup;
                })
            })
            return groupPerProject;
        }
    },
    methods: {
        pinProject(project) {
            Inertia.post(route('project.pin', {project: project.id}));
        },
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
        getEditHref(project) {
            return route('projects.tab', {project: project.id, projectTab: project.first_tab_id});
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
        deleteProject() {
            this.nameOfDeletedProject = this.projectToDelete.name;
            Inertia.delete(`/projects/${this.projectToDelete.id}`);
            this.closeDeleteProjectModal();
            this.openSuccessModal();
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
        checkPermission(project, type) {
            const writeAuth = [];
            const managerAuth = [];
            const deleteAuth = [];
            const viewAuth = [];

            project.users.forEach((user) => {
                viewAuth.push(user.id);
            });

            project.project_managers.forEach((user) => {
                managerAuth.push(user.id);
            })

            project.write_auth.forEach((user) => {
                writeAuth.push(user.id);
            });

            project.delete_permission_users.forEach((user) => {
                deleteAuth.push(user.id);
            });

            if(viewAuth.includes(this.$page.props.user.id) && type === 'view') {
                return true;
            }

            if (writeAuth.includes(this.$page.props.user.id) && type === 'edit') {
                return true;
            }
            if (managerAuth.includes(this.$page.props.user.id) || deleteAuth.includes(this.$page.props.user.id) && type === 'delete') {
                return true;
            }
            return false;
        },
        truncate(text, length, clamp) {
            clamp = clamp || '...';
            const node = document.createElement('div');
            node.innerHTML = text;
            const content = node.textContent;
            return content.length > length ? content.slice(0, length) + clamp : content;
        },
        openProjectExportBudgetsByBudgetDeadlineModal() {
            this.showProjectExportBudgetsByBudgetDeadlineModal = true;
        },
        closeProjectExportBudgetsByBudgetDeadlineModal() {
            this.showProjectExportBudgetsByBudgetDeadlineModal = false;
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
