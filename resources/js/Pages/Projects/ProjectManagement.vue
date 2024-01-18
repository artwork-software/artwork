<template>
    <app-layout>
        <div class="">
            <div class="max-w-screen mb-40 flex flex-row ml-14 mr-14">
                <div class="flex flex-1 flex-wrap">
                    <div>
                        <p class="items-center flex mr-2 headline1 mb-11">Projekte</p>
                    </div>
                    <div class="w-full flex items-center justify-between">
                        <div class="w-full flex items-center">
                            <div class="w-48">
                                <BaseFilter :left="true">
                                    <div class="w-full">
                                        <div class="flex justify-end mb-3">
                                            <span class="xxsLight cursor-pointer text-right w-full" @click="removeFilter">Zurücksetzen</span>
                                        </div>
                                        <SwitchGroup as="div" class="flex items-center">
                                            <Switch v-model="enabled"
                                                    :class="[enabled ? 'bg-green-400' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                                <span class="sr-only">Use setting</span>
                                                <span aria-hidden="true"
                                                      :class="[enabled ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                                            </Switch>
                                            <SwitchLabel as="span" class="ml-3 xxsLight">
                                                Nur meine Projekte anzeigen
                                            </SwitchLabel>
                                        </SwitchGroup>
                                        <div class="flex max-h-8 mb-3 mt-3">
                                            <input v-model="showProjectGroups"
                                                   type="checkbox"
                                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">Projektgruppen</p>
                                        </div>
                                        <div class="flex max-h-8 mb-3 mt-3">
                                            <input v-model="showProjects"
                                                   type="checkbox"
                                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                            <p class=" ml-4 my-auto text-sm text-secondary">Projekte</p>
                                        </div>
                                        <div class="flex justify-between xsLight mb-3"
                                             @click="showProjectStateFilter = !showProjectStateFilter">
                                            Projektstatus
                                            <ChevronDownIcon class="h-5 w-5" v-if="!showProjectStateFilter"
                                                             aria-hidden="true"/>
                                            <ChevronUpIcon class="h-5 w-5" v-if="showProjectStateFilter"
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
                            </div>
                            <div class="flex items-center ml-8">
                                <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                     class="cursor-pointer inset-y-0 mr-3">
                                    <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                                </div>
                                <div v-else class="flex items-center w-full w-64 mr-2">
                                    <div>
                                        <input type="text"
                                               placeholder="Suche nach Projekten"
                                               v-model="project_search"
                                               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                                </div>
                            </div>
                            <button @click="openProjectExportBudgetsByBudgetDeadlineModal()"
                                    type="button"
                                    class="flex p-2 px-3 mt-1 items-center border border-transparent rounded-full shadow-sm text-white hover:shadow-blueButton focus:outline-none bg-buttonBlue hover:bg-buttonHover">
                                <DocumentReportIcon class="h-4 w-4 mr-2" aria-hidden="true"/>
                                <p class="text-sm">Excel-Export</p>
                            </button>
                        </div>
                        <div class="flex relative" v-if="$can('create and edit own project') || $role('artwork admin')">
                            <div v-if="this.$page.props.show_hints" class="flex mt-1 absolute w-40 right-32">
                                <span class="hind ml-1 my-auto">Lege neue Projekte an</span>
                                <SvgCollection svgName="smallArrowRight" class="mt-1 ml-2"/>
                            </div>
                            <AddButton @click="openCreateProjectModal" text="Neu" mode="page"/>
                        </div>
                    </div>
                    <div id="selectedFilter" class="mt-3">
                        <span v-if="enabled"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                            Meine Projekte
                            <button type="button" @click="enabled = !enabled">
                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                            </button>
                        </span>
                        <span v-if="showProjectGroups"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    Projektgruppen
                                    <button type="button" @click="showProjectGroups = !showProjectGroups">
                                        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                        <span v-if="showProjects"
                              class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    Projekte
                                    <button type="button" @click="showProjects = !showProjects">
                                        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                        <span v-for="state in states">
                                    <span v-if="state.clicked"
                                          class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                    {{ state.name }}
                                    <button type="button"
                                            @click="this.projectStateFilter.splice(this.projectStateFilter.indexOf(state),1); state.clicked = false">
                                        <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                    </button>
                                </span>
                                </span>
                    </div>

                    <!-- new table  -->
                    <div class="my-10 divide-y-2 divide-gray-200">
                        <div class="grid grid-cols-10 grid-rows-1 gap-4 w-full py-4" v-for="(project,index) in sortedProjects" :key="project.id">
                            <div class="flex items-start justify-center">
                                <div class="flex justify-center items-center relative bg-gray-200 rounded-full h-12 w-12">
                                    <img :src="'/storage/keyVisual/' + project.key_visual" alt="" class="rounded-full h-12 w-12" v-if="project.key_visual !== null">
                                    <img src="/Svgs/IconSvgs/placeholder.svg" alt="" class="rounded-full h-5 w-5" v-else>
                                    <div class="absolute flex items-center justify-center w-7 h-7" v-if="project.is_group">
                                        <img src="Svgs/IconSvgs/icon_project_group.svg" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-6">
                                <div>
                                    <Link
                                        v-if="
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
                                    <div v-else class="flex w-full my-auto">
                                        <p class="xsDark flex items-center">
                                            <span v-if="project.is_group">
                                                <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-5 w-5 mr-2"
                                                     aria-hidden="true"/>
                                            </span>
                                            {{ truncate(project.name, 80, '...') }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="$role('artwork admin') || $can('write projects') || checkPermission(project, 'edit') || $can('view projects')" class="text-secondary flex flex-nowrap items-center ">
                                    <div v-if="project.project_history.length" class="flex items-center">
                                        <span class=" xxsLight">
                                              zuletzt geändert:
                                        </span>
                                        <UserPopoverTooltip v-if="project.project_history[0].changes[0].changed_by"
                                                            :user="project.project_history[0].changes[0].changed_by"
                                                            :id="index" height="4" width="4" class="ml-2"/>
                                        <span class="ml-2 xxsLight subpixel-antialiased">
                                                {{ project.project_history[0].created_at }}
                                            </span>
                                        <button
                                            class="ml-4 xxsLight subpixel-antialiased text-buttonBlue flex items-center cursor-pointer"
                                            @click="openProjectHistoryModal(project)">
                                            <ChevronRightIcon
                                                class="-mr-0.5 h-4 w-4 group-hover:text-white"
                                                aria-hidden="true"/>
                                            Verlauf ansehen
                                        </button>
                                    </div>
                                    <div v-else class="xxsLight">
                                        Noch kein Verlauf verfügbar
                                    </div>
                                </div>
                                <div class="xxsLight w-11/12">
                                    {{ truncate(project.description, 300, '...') }}
                                </div>

                            </div>
                            <div class="col-start-8 flex items-center">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium break-keep"
                                    :class="project.state?.color">{{ project.state?.name }}</span>
                            </div>
                            <div class="col-start-9 flex items-center">
                                <div class="flex items-top shrink-0 px-4">
                                    <div class="-mr-3 " v-for="(user) in project.project_managers">
                                        <UserPopoverTooltip :user="user" :id="user.id" height="8" width="8"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-start-10 flex items-center justify-end">
                                <div class="flex items-start justify-between">
                                    <div v-if="project.pinned_by_users && project.pinned_by_users.includes($page.props.user.id)"
                                         class="flex items-center xxsLight subpixel-antialiased mt-1">
                                        <IconPin class="h-5 w-5 mr-4 text-primary"/>
                                    </div>
                                    <Menu
                                        v-if="this.checkPermission(project, 'edit') || checkPermission(project, 'delete') || $role('artwork admin') || $can('delete projects') || $can('write projects')"
                                        as="div" class="flex">
                                        <div class="flex bg-tagBg p-0.5 rounded-full relative">
                                            <div v-if="this.$page.props.show_hints && index === 0"
                                                 class="absolute flex items-center w-40 right-1 -bottom-5">
                                                <div class="flex">
                                                    <span class="mr-2 hind mt-1">Bearbeite die Projekte</span>
                                                </div>
                                                <div>
                                                    <SvgCollection svgName="arrowUpRight" class="ml-2 rotate-45"/>
                                                </div>
                                            </div>
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
                                                class="origin-top-right z-50 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                <div class="py-1">
                                                    <MenuItem v-slot="{ active }"
                                                              v-if="$role('artwork admin') || $can('write projects') || this.checkPermission(project, 'edit')">
                                                        <a @click="openEditProjectModal(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                                            <PencilAltIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Basisdaten bearbeiten
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem class="cursor-pointer" v-slot="{ active }">
                                                        <a @click="pinProject(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <IconPin
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            {{project.pinned_by_users && project.pinned_by_users.includes($page.props.user.id) ? 'Anpinnung aufheben' : 'Anpinnen'}}
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }"
                                                              v-if="$role('artwork admin') || $can('write projects') || $can('management projects') || this.checkPermission(project, 'edit')">
                                                        <a href="#" @click="duplicateProject(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <DuplicateIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Duplizieren
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }"
                                                              v-if="$role('artwork admin') || $can('delete projects') || this.checkPermission(project, 'delete')">
                                                        <a href="#" @click="openDeleteProjectModal(project)"
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Projekt erstellen Modal-->
        <project-create-modal
            v-if="createProject"
            :show="createProject"
            @close-create-project-modal="closeCreateProjectModal"
            :categories="categories"
            :genres="genres"
            :sectors="sectors"
            :project-groups="this.projectGroups"
        />
        <!-- Delete Project Modal -->
        <jet-dialog-modal :show="deletingProject" @close="closeDeleteProjectModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Projekt löschen
                    </div>
                    <XIcon @click="closeDeleteProjectModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du das Projekt {{ projectToDelete.name }} löschen willst?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-buttonBlue hover:bg-buttonHover rounded-full focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteProject">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteProjectModal()"
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Success Modal - Delete project -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black text-primary font-lexend text-3xl my-2">
                        Projekt gelöscht
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        Das Projekt {{ nameOfDeletedProject }} wurde gelöscht.
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-24 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal">
                            <CheckIcon class="h-6 w-12 text-secondaryHover"/>
                        </button>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal2" @close="closeSuccessModal2">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black text-primary font-lexend text-3xl my-2">
                        Projekt erstellt
                    </div>
                    <XIcon @click="closeSuccessModal2"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        Das Projekt wurde erfolgreich angelegt.
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-24 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal2">
                            <CheckIcon class="h-6 w-12 text-secondaryHover"/>
                        </button>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>
        <project-data-edit-modal
            v-if="editingProject"
            :show="editingProject"
            @closed="closeEditProjectModal"
            :project="this.projectToEdit"
            :group-projects="this.projectGroups"
            :current-group="this.groupPerProject[this.projectToEdit?.id]"
            :states="states"
        />

        <!-- Project History Modal -->
        <project-history-component
            @closed="closeProjectHistoryModal"
            v-if="showProjectHistory"
            :project_history="projectHistoryToDisplay"
            :access_budget="projectBudgetAccess"
        ></project-history-component>

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
import {ChevronUpIcon, PlusSmIcon, CheckIcon, SelectorIcon, XCircleIcon, ChevronRightIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";
import {
    Disclosure, DisclosureButton, DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems, Switch, SwitchGroup, SwitchLabel
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
import CategoryIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {Inertia} from "@inertiajs/inertia";
import {Link} from "@inertiajs/inertia-vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import AddButton from "@/Layouts/Components/AddButton";
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

export default defineComponent({
    components: {
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
        AddButton,
        CategoryIconCollection,
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
    props: ['projects', 'states', 'users', 'categories', 'genres', 'sectors', 'can', 'projectGroups'],
    mixins: [Permissions],

    data() {
        return {
            project_search: '',
            showProjectHistoryTab: true,
            showBudgetHistoryTab: false,
            projectBudgetAccess: {},
            projectFilters: [{'name': 'Alle Projekte'}, {'name': 'Meine Projekte'}],
            projectFilter: {'name': 'Alle Projekte'},
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
                {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
                {name: 'Budget', href: '#', current: this.showBudgetHistoryTab},
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
            if (selectedTab.name === 'Projekt') {
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
            return route('projects.show.info', {project: project.id});
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
