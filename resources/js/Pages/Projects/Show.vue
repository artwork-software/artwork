<template>
    <app-layout>
        <ProjectShowHeaderComponent :projectManagerIds="projectManagerIds" :project="project" :eventTypes="eventTypes" :currentGroup="currentGroup" :states="states" :project-groups="projectGroups" :first-event-in-project="firstEventInProject" :last-event-in-project="lastEventInProject" :rooms-with-audience="RoomsWithAudience">

        </ProjectShowHeaderComponent>
        <!-- Div with Bg-Color -->
        <div class="w-full h-full mb-48">
            <div class="ml-10">
                <div class="hidden sm:block">
                    <div class="border-gray-200">
                        <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                            <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab?.name"
                               :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                               :aria-current="tab.current ? 'page' : undefined" v-show="tab?.show">
                                {{ tab?.name }}
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="bg-lightBackgroundGray">
                <!-- Checklist Tab -->


                <div v-if="isShiftTab" class="mx-5 mt-6 p-5  bg-lightBackgroundGray">
                    <ShiftTab :eventsWithRelevant="eventsWithRelevant" :crafts="crafts" :drop-users="dropUsers" :users="project.users"/>
                </div>
                <!-- Comment Tab -->
                <div class="mt-6">

                </div>
            </div>
        </div>
        <!-- File Delete Modal -->
        <jet-dialog-modal :show="deletingFile" @close="closeConfirmDeleteModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Datei löschen
                    </div>
                    <XIcon @click="closeConfirmDeleteModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
                        Bist du sicher, dass du "{{ project_file.name }}" aus dem System löschen
                        möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="removeFile(project_file)">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                                            <span @click="closeConfirmDeleteModal"
                                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Projekt bearbeiten Modal-->
        <project-data-edit-modal
            :show="editingProject"
            :project-state="projectState"
            @closed="closeEditProjectModal"
            :project="this.project"
            :group-projects="this.groupProjects"
            :current-group="this.currentGroup"
            :states="states"
        />


        <!-- Project History Modal -->
        <project-history-component
            @closed="closeProjectHistoryModal"
            v-if="showProjectHistory"
            :project_history="project.project_history"
            :access_budget="project.access_budget"
        ></project-history-component>

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
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <ProjectSidenav
                v-if="isBudgetTab"
                :project="project"
                :cost-center="project.cost_center"
                :copyright="project.copyright"
                :project-files="project.project_files"
                :contracts="project.contracts"
                :money-sources="projectMoneySources"
                :budget-access="access_budget"
            />
            <ProjectShiftSidenav
                v-else-if="isShiftTab"
                :project="project"
                :event-types="eventTypes"
            />
            <ProjectSecondSidenav
                v-else
                :project="project"
                :project-members="projectMembers"
                :project-members-write-access="projectCanWriteIds"
                :project-categories="projectCategories"
                :project-genres="projectGenres"
                :project-sectors="projectSectors"
                :project-category-ids="projectCategoryIds"
                :project-genre-ids="projectGenreIds"
                :project-sector-ids="projectSectorIds"
                :categories="categories"
                :sectors="sectors"
                :genres="genres"
                :project-manager-ids="projectManagerIds"
            />
        </BaseSidenav>
    </app-layout>
</template>

<script>

import {Link, useForm} from "@inertiajs/inertia-vue3";
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Disclosure, DisclosureButton, DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch, SwitchGroup, SwitchLabel
} from "@headlessui/vue";
import {
    PencilAltIcon,
    TrashIcon,
    XIcon,
    DuplicateIcon,
    DocumentTextIcon,
    EyeIcon,
    ExclamationIcon
} from "@heroicons/vue/outline";
import {
    CheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    DotsVerticalIcon,
    XCircleIcon,
    PlusSmIcon, ChevronRightIcon, PlusIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import Checkbox from "@/Jetstream/Checkbox";
import CategoryIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import draggable from "vuedraggable";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import {Inertia} from "@inertiajs/inertia";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import AddButton from "@/Layouts/Components/AddButton";
import CalendarComponent from "@/Layouts/Components/CalendarComponent";
import ChecklistTeamComponent from "@/Layouts/Components/ChecklistTeamComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav";
import ProjectSidenav from "@/Layouts/Components/ProjectSidenav";
import Dropdown from "@/Jetstream/Dropdown.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";
import ProjectSecondSidenav from "@/Layouts/Components/ProjectSecondSidenav.vue";
import {nextTick} from "vue";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import Permissions from "@/mixins/Permissions.vue";
import ShiftTab from "@/Pages/Projects/Components/TabComponents/ShiftTab.vue";
import ProjectShiftSidenav from "@/Layouts/Components/ProjectShiftSidenav.vue";
import ProjectShowHeaderComponent from "@/Pages/Projects/Components/ProjectShowHeaderComponent.vue";

export default {
    name: "ProjectShow",
    props: [
        'events',
        'projectMoneySources',
        'RoomsWithAudience',
        'firstEventInProject',
        'lastEventInProject',
        'eventTypes',
        'opened_checklists',
        'project_users',
        'project',
        'openTab',
        'categories',
        'projectCategoryIds',
        'projectGenreIds',
        'projectSectorIds',
        'projectCategories',
        'projectGenres',
        'projectSectors',
        'genres',
        'sectors',
        'checklist_templates',
        'isMemberOfADepartment',
        'budget',
        'moneySources',
        'projectGroups',
        'currentGroup',
        'groupProjects',
        'states',
        'projectState',
        'eventsAtAGlance',
        'calendar',
        'days',
        'rooms',
        'dateValue',
        'selectedDate',
        'calendarType',
        'eventsWithoutRoom',
        'filterOptions',
        'personalFilters',
        'eventsWithRelevant',
        'crafts'
    ],
    components: {
        ProjectShowHeaderComponent,
        ProjectShiftSidenav,
        ShiftTab,
        ProjectSecondSidenav,
        ChecklistComponent,
        ProjectHistoryComponent,
        NewUserToolTip,
        Dropdown,
        BudgetComponent,
        ProjectSidenav,
        BaseSidenav,
        TagComponent,
        AddButton,
        TeamTooltip,
        CategoryIconCollection,
        Checkbox,
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        XCircleIcon,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        CheckIcon,
        ChevronDownIcon,
        DuplicateIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        PlusSmIcon,
        Switch,
        ChevronUpIcon,
        draggable,
        DocumentTextIcon,
        ChevronRightIcon,
        UserTooltip,
        EyeIcon,
        ExclamationIcon,
        Link,
        CalendarComponent,
        ChecklistTeamComponent,
        Disclosure,
        DisclosurePanel,
        DisclosureButton,
        ProjectDataEditModal,
        PlusIcon, SwitchGroup, SwitchLabel,
        IndividualCalendarAtGlanceComponent,
        IndividualCalendarComponent,
    },
    mixins: [Permissions],
    computed: {
        dropUsers(){
            const users = [];
            this.project.users.forEach((user) => {
                users.push({
                    element: user,
                    type: 0
                })
            })
            this.project.freelancers?.forEach((freelancer) => {
                users.push({
                    element: freelancer,
                    type: 1
                })
            })
            this.project.serviceProviders?.forEach((provider) => {
                users.push({
                    element: provider,
                    type: 2
                })
            })

            return users;
        },
        write() {
            return write
        },
        errors() {
            return this.$page.props.errors;
        },
        tabs() {
            return [
                {name: 'Projektinformationen', href: '#', current: this.isInfoTab, show: true},
                {name: 'Ablaufplan', href: '#', current: this.isScheduleTab, show: true},
                {name: 'Checklisten', href: '#', current: this.isChecklistTab, show: true},
                {name: 'Schichten', href: '#', current: this.isShiftTab, show: true},
                {name: 'Budget', href: '#', current: this.isBudgetTab, show: this.$page.props.is_admin || this.access_budget?.includes(this.$page.props.user.id) || this.projectManagerIds?.includes(this.$page.props.user.id)},
                {name: 'Kommentare', href: '#', current: this.isCommentTab, show: true},
            ]
        },
        historyTabs() {
            if (this.$page.props.is_admin || this.access_budget?.includes(this.$page.props.user.id)) {
                return [
                    {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
                    {name: 'Budget', href: '#', current: this.showBudgetHistoryTab},
                ]
            } else {
                return [
                    {name: 'Projekt', href: '#', current: this.showProjectHistoryTab},
                ]
            }
        },
        projectMembers() {
            let projectMembers = [];
            this.project.users.forEach((user) => {
                if (this.project.project_managers.findIndex((projectManager) => projectManager.id === user.id) !== -1) {
                    user.is_manager = true;
                } else {
                    projectMembers.push(user);
                }
                if (this.project.access_budget.findIndex((access_budget) => access_budget.id === user.id) !== -1) {
                    user.access_budget = true;
                }
                if (this.project.write_auth.findIndex((writeAuth) => writeAuth.id === user.id) !== -1) {
                    user.can_write = true;
                }
                if (this.project.delete_permission_users.findIndex((delete_permission) => delete_permission.id === user.id) !== -1) {
                    user.delete_permission = true;
                }
            })
            return projectMembers;
        },
        sortedComments: function () {
            let commentCopy = this.project.comments.slice();

            function compare(a, b) {
                if (b.created_at === null) {
                    return -1;
                }
                if (a.created_at === null) {
                    return 1;
                }
                if (a.created_at < b.created_at)
                    return 1;
                if (a.created_at > b.created_at)
                    return -1;
                return 0;
            }

            return commentCopy.sort(compare);
        },
        access_budget: function () {
            let access_budget = [];
            this.project.access_budget.forEach(admin => {
                    access_budget.push(admin.id)
                }
            )
            return access_budget;
        },
        projectManagerIds: function () {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray;
        },
        projectCanWriteIds: function () {
            let canWriteArray = [];
            this.project.write_auth.forEach(write => {
                    canWriteArray.push(write.id)
                }
            )
            return canWriteArray;
        },
        projectDeletePermissionUsers(){
            let canDeleteArray = [];
            this.project.delete_permission_users.forEach(deletePermission => {
                    canDeleteArray.push(deletePermission.id)
                }
            )
            return canDeleteArray;
        },
        locationString() {
            return Object.values(this.RoomsWithAudience).join(", ");
        }
    },
    data() {
        return {
            show: false,
            deletingFile: false,
            project_file: null,
            uploadDocumentFeedback: "",
            editingProject: false,
            isScheduleTab: this.openTab ? this.openTab === 'calendar' : false,
            isChecklistTab: this.openTab ? this.openTab === 'checklist' : false,
            isShiftTab: this.openTab ? this.openTab === 'shift' : false,
            isInfoTab: this.openTab ? this.openTab === 'info' : false,
            isBudgetTab: this.openTab ? this.openTab === 'budget' : false,
            isCommentTab: this.openTab ? this.openTab === 'comment' : false,
            showProjectHistory: false,
            commentHovered: null,
            projectToDelete: {},
            deletingProject: false,
            descriptionClicked: false,
            keyVisualForm: useForm({
                keyVisual: null,
            }),
            commentForm: useForm({
                text: "",
                user_id: this.$page.props.user.id,
                project_id: this.project.id
            }),
            documentForm: useForm({
                file: null
            }),
            selectedState: this.projectState ? this.states.find(state => state.id === this.projectState) : null,
            atAGlance: this.eventsAtAGlance.length > 0,
        }
    },

    methods: {
        downloadKeyVisual() {
            let link = document.createElement('a');
            link.href = route('project.download.keyVisual', this.project.id);
            link.target = '_blank';
            link.click();
        },
        deleteKeyVisual() {
            this.$inertia.delete(route('project.delete.keyVisual', this.project.id))
        },
        async handleDescriptionClick() {

            this.descriptionClicked = true;

            await nextTick()

            this.$refs[`description-${this.project.id}`].select();
        },
        async handleTextClick(headline) {

            headline.clicked = !headline.clicked

            if (headline.clicked) {
                await nextTick()

                this.$refs[`text-${headline.id}`][0].select();
            }
        },
        changeHeadlineText(headline) {
            this.$inertia.patch(route('project_headlines.update.text', {
                project_headline: headline.id,
                project: this.project.id
            }), {text: headline.text})
        },
        updateDescription() {
            this.$inertia.patch(route('projects.update_description', this.project.id), {
                description: this.project.description
            }, {
                preserveScroll: true,
                preserveState: true
            });
            this.descriptionClicked = false;
        },
        selectNewKeyVisual() {
            this.$refs.keyVisual.click();
        },
        updateKeyVisual() {
            this.validateTypeAndUploadKeyVisual(this.$refs.keyVisual.files[0], 'keyVisual');
        },
        uploadDraggedKeyVisual(event) {
            this.validateTypeAndUploadKeyVisual(event.dataTransfer.files[0], 'keyVisual');
        },
        validateTypeAndUploadKeyVisual(file, type) {
            this.uploadDocumentFeedback = "";
            const allowedTypes = [
                "image/jpeg",
                "image/svg+xml",
                "image/png",
                "image/gif"
            ]

            if (allowedTypes.includes(file.type)) {
                this.keyVisualForm.keyVisual = file
                this.keyVisualForm.post(route('projects_key_visual.update', {project: this.project.id}));
            } else {
                this.uploadDocumentFeedback = "Es werden ausschließlich Logos und Illustrationen vom Typ .jpeg, .svg, .png und .gif akzeptiert."
            }
        },
        deleteProjectFromGroup(projectGroupId) {
            axios.delete(route('projects.group.delete'), {
                params: {
                    projectIdToDelete: projectGroupId.id,
                    groupId: this.project.id
                }
            }).finally(() => {
                this.projectGroups.splice(this.projectGroups.findIndex(index => index.id === projectGroupId.id), 1)
            })
        },
        selectNewFiles() {
            this.$refs.project_files.click();
        },
        openConfirmDeleteModal(project_file) {
            this.deletingFile = true;
            this.project_file = project_file
        },
        closeConfirmDeleteModal() {
            this.deletingFile = false;
            this.project_file = null
        },
        removeFile(project_file) {
            this.$inertia.delete(`/project_files/${project_file.id}`, {
                preserveState: true,
            })
            this.closeConfirmDeleteModal();
        },
        downloadFile(project_file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: project_file});
            link.target = '_blank';
            link.click();
        },
        validateTypeAndUpload(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
                } else {
                    const fileSize = file.size;
                    if(fileSize > 2097152){
                        this.uploadDocumentFeedback = "Dateien, welche größer als 2MB sind, können nicht hochgeladen werden."
                    }else{
                        this.uploadDocumentToProject(file)
                    }

                }
            }
        },
        uploadChosenDocuments(event) {
            this.validateTypeAndUpload([...event.target.files])
        },
        uploadDraggedDocuments(event) {
            this.validateTypeAndUpload([...event.dataTransfer.files])
        },
        uploadDocumentToProject(file) {
            this.documentForm.file = file

            this.documentForm.post(`/projects/${this.project.id}/files`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.documentForm.file = null
                }
            })
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
        },
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        },
        changeTab(selectedTab) {
            this.isScheduleTab = false;
            this.isChecklistTab = false;
            this.isInfoTab = false;
            this.isBudgetTab = false;
            this.isCommentTab = false;
            this.isShiftTab = false;
            if (selectedTab.name === 'Ablaufplan') {
                this.isScheduleTab = true;
            } else if (selectedTab.name === 'Checklisten') {
                this.isChecklistTab = true;
            } else if (selectedTab.name === 'Projektinformationen') {
                this.isInfoTab = true;
            } else if (selectedTab.name === 'Kommentare') {
                this.isCommentTab = true;
            } else if (selectedTab.name === 'Schichten') {
                this.isShiftTab = true;
            } else {
                this.isBudgetTab = true;
            }
        },
        duplicateProject(project) {
            this.$inertia.post(`/projects/${project.id}/duplicate`);
        },
        addCommentToProject() {
            this.commentForm.post(route('comments.store'), {preserveState: true, preserveScroll: true});
            this.commentForm.text = "";
        },
        deleteCommentFromProject(comment) {
            this.$inertia.delete(`/comments/${comment.id}`, {preserveState: true, preserveScroll: true});
        },
        openProjectHistoryModal() {
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
        },
        checkUserAuth(user) {
            if (this.projectManagerIds.includes(this.$page.props.user.id)) {
                return true;
            }
            if (this.$page.props.user.id === user.id && user.project_management) {
                return true;
            }
            if (this.$page.props.is_admin) {
                return true;
            }
            return false;
        },
        changeAtAGlance() {
            this.atAGlance = !this.atAGlance;
            if(this.atAGlance){
                Inertia.reload({
                    data: {
                        atAGlance: this.atAGlance,
                    },
                    only: ['calendar']
                })
            }
        }
    },
}

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
