<script>
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {Link} from "@inertiajs/vue3";
import {router} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ColorHelper from "@/Mixins/ColorHelper.vue";
import ProjectDataEditModal from "@/Layouts/Components/ProjectDataEditModal.vue";

export default {
    name: "SingleProject",
    components: {
        ProjectDataEditModal,
        BaseModal, Link, BaseMenu, Menu,
        MenuButton,
        MenuItem,
        MenuItems,},
    mixins: [IconLib, Permissions, ColorHelper],
    props: {
        project: {
            type: Object,
            required: true
        },
        first_project_tab_id: {
            type: Number,
            required: true
        },
        projectGroups: {
            type: Object,
            required: true
        },
        states: {
            type: Object,
            required: true
        }
    },
    data(){
        return {
            deletingProject: false,
            editingProject: false,
        }
    },
    computed: {
        groupPerProject(){
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
        duplicateProject() {
            this.$inertia.post(`/projects/${this.project.id}/duplicate`);
        },
        openDeleteProjectModal() {
            this.deletingProject = true;
        },
        closeDeleteProjectModal() {
            this.deletingProject = false;
            this.projectToDelete = null;
        },
        deleteProject() {
            this.nameOfDeletedProject = this.project.name;
            router.delete(`/projects/${this.project.id}`);
            this.closeDeleteProjectModal();
            this.openSuccessModal();
        },
        getEditHref() {
            return route('projects.tab', {project: this.project?.id, projectTab: this.first_project_tab_id});
        },
        checkPermission(project, type) {
            const writeAuth = [];
            const managerAuth = [];
            const deleteAuth = [];
            const viewAuth = [];

            project?.users?.forEach((user) => {
                viewAuth.push(user.id);
            });

            project?.project_managers?.forEach((user) => {
                managerAuth.push(user.id);
            })

            project?.write_auth?.forEach((user) => {
                writeAuth.push(user.id);
            });

            project?.delete_permission_users?.forEach((user) => {
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
        pinProject() {
            router.post(route('project.pin', {project: this.project.id}), {}, {
                preserveScroll: true,
                preserveState: true,
            });
        },
        truncate(text, length, clamp) {
            clamp = clamp || '...';
            const node = document.createElement('div');
            node.innerHTML = text;
            const content = node.textContent;
            return content.length > length ? content.slice(0, length) + clamp : content;
        },

        openEditProjectModal() {
            this.editingProject = true;
        },
        closeEditProjectModal() {
            this.editingProject = false;
        },

    }
}
</script>

<template>
    <div class="col-span-6 flex items-center">
        <div class="grid grid-cols-10 gap-x-3">
            <div class="col-span-1 flex items-center justify-center">
                <div class="flex justify-center items-center relative bg-gray-200 rounded-full h-12 w-12">
                    <img :src="'/storage/keyVisual/' + project.key_visual_path" alt="" class="rounded-full h-12 w-12" v-if="project.key_visual_path">
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
                                     aria-hidden="true" alt=""/>
                            </span>
                            {{ truncate(project.name, 80, '...') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-span-4 flex items-center justify-end">
        <div class="flex items-center gap-x-4">
            <div class="col-span-6">
                <span v-if="project.state" class="inline-flex items-center border rounded-full px-2.5 py-1 text-xs font-medium break-keep w-fit" :style="{backgroundColor: backgroundColorWithOpacity(project.state?.color), color: TextColorWithDarken(project.state?.color), borderColor: TextColorWithDarken(project.state?.color)}">
                    {{ project.state?.name }}
                </span>
            </div>
            <div class="col-span-1">
                <div v-if="project.pinned_by_users && project.pinned_by_users?.includes($page.props.user.id)"
                     class="flex items-center xxsLight subpixel-antialiased">
                    <IconPinned class="h-5 w-5 text-primary"/>
                </div>
            </div>
            <div class="flex justify-end">
                <BaseMenu v-if="this.checkPermission(project, 'edit') || checkPermission(project, 'delete') || $role('artwork admin') || $can('delete projects') || $can('write projects')">
                    <MenuItem v-slot="{ active }"
                              v-if="$role('artwork admin') || $can('write projects') || this.checkPermission(project, 'edit')">
                        <a @click="openEditProjectModal()"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                            <IconEdit stroke-width="1.5"
                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                      aria-hidden="true"/>
                            {{ $t('Edit basic data') }}
                        </a>
                    </MenuItem>
                    <MenuItem class="cursor-pointer" v-slot="{ active }" v-if="project.pinned_by_users && project.pinned_by_users.includes($page.props.user.id)">
                        <a @click="pinProject()"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconPinnedOff stroke-width="1.5"
                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                           aria-hidden="true"/>
                            {{  $t('Undo pinning') }}
                        </a>
                    </MenuItem>
                    <MenuItem class="cursor-pointer" v-slot="{ active }" v-else>
                        <a @click="pinProject()"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconPin stroke-width="1.5"
                                     class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                     aria-hidden="true"/>
                            {{  $t('Pin') }}
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }"
                              v-if="$role('artwork admin') || $can('write projects') || $can('management projects') || this.checkPermission(project, 'edit')">
                        <a href="#" @click="duplicateProject()"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconCopy stroke-width="1.5"
                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                      aria-hidden="true"/>
                            {{ $t('Duplicate') }}
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }"
                              v-if="$role('artwork admin') || $can('delete projects') || this.checkPermission(project, 'delete')">
                        <a href="#" @click="openDeleteProjectModal()"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconTrash stroke-width="1.5"
                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                       aria-hidden="true"/>
                            {{ $t('Put in the trash') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
            </div>
        </div>
    </div>

    <BaseModal @closed="closeDeleteProjectModal" v-if="deletingProject" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="font-black font-lexend text-primary text-3xl my-2">
                {{ $t('Delete project') }}
            </div>
            <div class="text-error subpixel-antialiased">
                {{ $t('Are you sure you want to delete the project?', [project.name]) }}
            </div>
            <div class="flex justify-between mt-6">
                <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-full focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-white"
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
    </BaseModal>

    <project-data-edit-modal
        v-if="editingProject"
        :show="editingProject"
        :project="project"
        :group-projects="this.projectGroups"
        :current-group="this.groupPerProject[project?.id]"
        :states="states"
        @closed="closeEditProjectModal"
    />
</template>

<style scoped>

</style>
