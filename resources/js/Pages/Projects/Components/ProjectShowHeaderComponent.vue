<template>
    <div class="ml-14 pr-14">
        <div class="flex flex-col">
            <!-- if in group -->
            <div v-if="currentGroup" class="bg-secondaryHover -mb-6 z-20 w-fit pr-6 pb-0.5">
                <div class="flex items-center">
                        <span v-if="!project.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-4 w-4 mr-2" aria-hidden="true"/>
                        </span>
                    {{ $t('Belongs to') }} <a :href="'/projects/' + currentGroup.id" class="text-buttonBlue ml-1">
                    {{ currentGroup?.name }}</a>
                </div>
            </div>
            <div v-if="openTab === 'info'">
                <div class="flex z-10" v-if="this.project.key_visual_path !== null">
                    <img :src="'/storage/keyVisual/' + this.project.key_visual_path"
                         :alt="$t('Current key visual')"
                         class="rounded-md mx-auto h-[200px]">
                </div>
                <div v-else class="w-full h-40 bg-gray-200 flex justify-center items-center">
                    <img src="/images/place.png" :alt="$t('Current key visual')"
                         class="rounded-md h-[200px]">
                </div>
            </div>
            <div class="flex justify-between items-center mt-4">
                <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl items-center">
                        <span v-if="project.is_group">
                            <img src="/Svgs/IconSvgs/icon_group_black.svg" class="h-6 w-6 mr-2" aria-hidden="true"/>
                        </span>
                    {{ project?.name }}
                    <span class="rounded-full items-center font-medium px-3 py-1 my-2 text-sm ml-2 mb-1 inline-flex"
                          :class="project?.state?.color">
                            {{ project?.state?.name }}
                        </span>
                </h2>
                <Menu as="div" class="my-auto mt-3 relative"
                      v-if="$can('write projects') || $role('artwork admin') || projectManagerIds.includes(this.$page.props.user.id) || projectWriteIds.includes(this.$page.props.user.id)">
                    <div class="flex items-center -mt-1">
                        <MenuButton
                            class="flex bg-tagBg p-0.5 rounded-full">
                            <IconDotsVertical stroke-width="1.5"
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
                            class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem
                                    v-if="$role('artwork admin') || projectWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || $can('write projects')"
                                    v-slot="{ active }">
                                    <a @click="openEditProjectModal"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                        <IconEdit stroke-width="1.5"
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        {{ $t('Edit basic data') }}
                                    </a>
                                </MenuItem>
                                <MenuItem
                                    v-if="$can('write projects') || $role('artwork admin') || $can('admin projects') || projectWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || project.isMemberOfADepartment"
                                    v-slot="{ active }">
                                    <a @click="this.openEditKeyVisualModal"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                        <IconUpload stroke-width="1.5"
                                                  class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                  aria-hidden="true"/>
                                        {{ $t('Edit Key Visual') }}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="duplicateProject(this.project)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                        <IconCopy stroke-width="1.5"
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        {{ $t('Duplicate') }}
                                    </a>
                                </MenuItem>
                                <MenuItem
                                    v-if="projectDeleteIds.includes(this.$page.props.user.id) ||$role('artwork admin')"
                                    v-slot="{ active }">
                                    <a @click="openDeleteProjectModal(this.project)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
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
            <div class="mt-3" v-if="projectGroups.length > 0">
                <TagComponent v-for="projectGroup in projectGroups" :method="deleteProjectFromGroup"
                              :displayed-text="projectGroup.name" :property="projectGroup"></TagComponent>
            </div>
            <div class="w-full mt-1 text-secondary subpixel-antialiased">
                <div v-if="firstEventInProject && lastEventInProject">
                    {{ $t('Time period/opening hours') }}: {{ firstEventInProject?.start_time }}
                    <span v-if="firstEventInProject?.start_time">{{ $t('Clock') }} -</span>
                    {{ lastEventInProject?.end_time }}
                    <span v-if="lastEventInProject?.end_time">{{ $t('Clock') }}</span>
                </div>
                <div v-if="roomsWithAudience">
                    {{ $t('Appointments with audience in') }}: <span>{{ locationString() }}</span>
                </div>
                <div v-if="!roomsWithAudience && !(firstEventInProject && lastEventInProject)">
                    {{ $t('No appointments within this project yet') }}
                </div>
            </div>
            <div class="mt-2 subpixel-antialiased text-secondary text-xs flex items-center"
                 v-if="project.project_history.length">
                <div>
                    {{ $t('last modified') }}:
                </div>
                <UserPopoverTooltip :user="project.project_history[0]?.changes[0]?.changed_by"
                                    :id="project.project_history[0]?.changes[0]?.changed_by.id" height="4" width="4"
                                    class="ml-2"/>
                <span class="ml-2 subpixel-antialiased">
                    {{ project.project_history[0]?.created_at }}
                </span>
                <button class="ml-4 subpixel-antialiased text-buttonBlue flex items-center cursor-pointer"
                        @click="openProjectHistoryModal()">
                    <IconChevronRight
                        class="-mr-0.5 h-4 w-4  group-hover:text-white"
                        aria-hidden="true"/>
                    {{ $t('View history') }}
                </button>
            </div>
        </div>
    </div>
    <pre>
        {{ projectTabs }}
    </pre>

    <div class="w-full h-full mb-48">
        <div class="ml-14">
            <div class="hidden sm:block">
                <div class="border-gray-200">
                    <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                        <Link v-for="tab in projectTabs" :key="tab?.name"
                              href="#"
                              :class="[tab?.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                              :aria-current="tab?.current ? 'page' : undefined">
                            {{ tab.name }}
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full h-full mb-48">
        <div class="ml-14">
            <div class="hidden sm:block">
                <div class="border-gray-200">
                    <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                        <Link v-for="tab in tabs" :key="tab?.name"
                           :href="tab.href"
                           :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                           :aria-current="tab.current ? 'page' : undefined" v-show="tab.show">
                            {{ tab.name }}
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
        <slot></slot>
    </div>
    <project-data-edit-modal
        :show="editingProject"
        :project-state="projectState"
        @closed="closeEditProjectModal"
        :project="this.project"
        :group-projects="this.groupProjects"
        :current-group="this.currentGroup"
        :states="states"
    />
    <project-history-component
        @closed="closeProjectHistoryModal"
        v-if="showProjectHistory"
        :project_history="project.project_history"
        :access_budget="project.access_budget"
    />
    <jet-dialog-modal :show="deletingProject" @close="closeDeleteProjectModal">
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
    <EditKeyVisualModal :show="this.showEditKeyVisualModal"
                        :project="this.project"
                        @closed="this.closeEditKeyVisualModal"
    />
</template>

<script>
import {ChevronRightIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {DuplicateIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Permissions from "@/mixins/Permissions.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import ProjectHistoryComponent from "@/Layouts/Components/ProjectHistoryComponent.vue";
import {Inertia} from "@inertiajs/inertia";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {Link} from "@inertiajs/inertia-vue3";
import IconLib from "@/mixins/IconLib.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import EditKeyVisualModal from "@/Pages/Projects/Components/EditKeyVisualModal.vue";

export default {
    components: {
        EditKeyVisualModal,
        JetInputError,
        UserPopoverTooltip,
        ProjectHistoryComponent,
        JetDialogModal,
        XIcon,
        ProjectDataEditModal,
        TrashIcon,
        DuplicateIcon,
        UserTooltip,
        TagComponent,
        PencilAltIcon,
        ChevronRightIcon,
        DotsVerticalIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Link
    },
    mixins: [Permissions, IconLib],
    props: [
        'project',
        'currentGroup',
        'states',
        'projectState',
        'projectGroups',
        'firstEventInProject',
        'lastEventInProject',
        'roomsWithAudience',
        'groupProjects',
        'openTab',
        'projectManagerIds',
        'projectWriteIds',
        'projectDeleteIds',
        'access_budget',
        'projectTabs'
    ],
    data() {
        return {
            showProjectHistory: false,
            editingProject: false,
            deletingProject: false,
            projectToDelete: null,
            showEditKeyVisualModal: false
        }
    },
    computed: {
        tabs() {
            return [
                {
                    name: this.$t('Project information'),
                    href: route('projects.show.info', {project: this.project.id}),
                    current: this.openTab === 'info',
                    show: true
                },
                {
                    name: this.$t('Schedule'),
                    href: route('projects.show.calendar', {project: this.project.id}),
                    current: this.openTab === 'calendar',
                    show: true
                },
                {
                    name: this.$t('Checklists'),
                    href: route('projects.show.checklist', {project: this.project.id}),
                    current: this.openTab === 'checklist',
                    show: true
                },
                {
                    name: this.$t('Shifts'),
                    href: route('projects.show.shift', {project: this.project.id}),
                    current: this.openTab === 'shift',
                    show: this.hasAdminRole() || this.$can('can plan shifts')
                },
                {
                    name: this.$t('Budget'),
                    href: route('projects.show.budget', {project: this.project.id}),
                    current: this.openTab === 'budget',
                    show: this.hasAdminRole() || this.hasBudgetAccess() || this.projectManagerIds.includes(this.$page.props.user.id) || this.$canAny(['can manage global project budgets','can manage all project budgets without docs'])
                },
                {
                    name: this.$t('Comments'),
                    href: route('projects.show.comment', {project: this.project.id}),
                    current: this.openTab === 'comment',
                    show: true
                },
            ]
        },
    },
    methods: {
        hasBudgetAccess() {
            return this.access_budget.filter((user) => user.id === this.$page.props.user.id).length > 0;
        },
        openProjectHistoryModal() {
            this.showProjectHistory = true;
        },
        closeProjectHistoryModal() {
            this.showProjectHistory = false;
        },
        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        },
        openEditKeyVisualModal() {
            this.showEditKeyVisualModal = true;
        },
        closeEditKeyVisualModal() {
            this.showEditKeyVisualModal = false;
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
        deleteProject() {
            this.nameOfDeletedProject = this.projectToDelete.name;
            Inertia.delete(`/projects/${this.projectToDelete.id}`);
            this.closeDeleteProjectModal();
        },
        locationString() {
            return Object.values(this.roomsWithAudience).join(", ");
        }
    }
}
</script>
