<template>
    <div>
        <div class="px-2 sm:px-3">
            <div
                :key="project.id"
                @mousedown.middle="openProjectInNewTab(project)"
                @mousedown="openProjectInNewTabWithCmdOrSTRG($event, project)"
                class="group relative rounded-xl border border-zinc-200 bg-white/80 backdrop-blur hover:shadow-md transition-shadow cursor-pointer"
                @contextmenu.prevent="openMenu(project.id, $event)"
            >
                <!-- Pinned -->
                <div
                    class="absolute left-2 -top-2"
                    v-if="fullProject?.pinned_by_users && fullProject?.pinned_by_users.includes($page.props.auth.user.id)"
                >
                    <div class="rounded-full p-0.5 bg-white border border-zinc-200 shadow-sm">
                        <component :is="IconPinned" class="h-5 w-5 text-artwork-buttons-create" />
                    </div>
                </div>

                <!-- Row grid -->
                <div class="grid items-center" :style="`grid-template-columns: ${gridTemplateColumns}`">
                    <div
                        v-for="component in components"
                        :key="component.name"
                        class="px-3 py-6 min-h-11 flex items-center"
                        :class="component.type === 'ActionsComponent' ? 'justify-end' : 'justify-start'"
                        @click="openProject(component, project)"
                    >
                        <!-- Visible content -->
                        <component
                            v-if="checkIfComponentIsVisible(component)"
                            :is="componentMapping['Builder' + component.type]"
                            :project="project"
                            :component="component"
                            :menu-visible="menuVisible"
                            :menu-position="menuPosition"
                        />

                        <!-- Actions hover menu -->
                        <BaseMenu
                            v-show="showActionComponent && component.type === 'ActionsComponent'"
                            v-if="checkPermission(project, 'edit') || checkPermission(project, 'delete') || role('artwork admin') || can('delete projects') || can('write projects')"
                            has-no-offset
                            white-menu-background
                        >
                            <BaseMenuItem white-menu-background as-link :link="route('projects.tab', { project: project.id, projectTab: project?.firstTabId })" title="Open" :icon="IconFolderOpen" />
                            <BaseMenuItem white-menu-background title="Edit basic data" @click="openEditProjectModal()" v-if="role('artwork admin') || can('write projects') || checkPermission(project, 'edit')" />
                            <BaseMenuItem white-menu-background title="Undo pinning" :icon="IconPinnedOff" v-if="fullProject.pinned_by_users && fullProject.pinned_by_users.includes($page.props.auth.user.id)" @click="pinProject()" />
                            <BaseMenuItem white-menu-background title="Pin" :icon="IconPin" v-else @click="pinProject()" />
                            <BaseMenuItem white-menu-background title="Duplicate" :icon="IconCopy" @click="duplicateProject()" v-if="role('artwork admin') || can('write projects') || can('management projects') || checkPermission(project, 'edit')" />
                            <BaseMenuItem white-menu-background title="Put in the trash" :icon="IconTrash" @click="openDeleteProjectModal()" v-if="role('artwork admin') || can('delete projects') || checkPermission(project, 'delete')" />
                        </BaseMenu>
                    </div>
                </div>

                <!-- Context menu (right click) -->
                <div
                    v-if="menuVisible === project.id"
                    :style="{ top: `${menuPosition.y}px`, left: `${menuPosition.x}px` }"
                    class="absolute z-50"
                >
                    <BaseMenu white-menu-background has-no-offset :button-id="'project-invisible-menu-' + project.id" :show-icon="false" v-if="checkPermission(project, 'edit') || checkPermission(project, 'delete') || role('artwork admin') || can('delete projects') || can('write projects')">
                        <BaseMenuItem white-menu-background as-link :link="route('projects.tab', { project: project.id, projectTab: project?.firstTabId })" title="Open" :icon="IconFolderOpen" />
                        <BaseMenuItem white-menu-background title="Edit basic data" @click="openEditProjectModal()" v-if="role('artwork admin') || can('write projects') || checkPermission(project, 'edit')" />
                        <BaseMenuItem white-menu-background title="Undo pinning" :icon="IconPinnedOff" v-if="fullProject.pinned_by_users && fullProject.pinned_by_users.includes($page.props.auth.user.id)" @click="pinProject()" />
                        <BaseMenuItem white-menu-background title="Pin" :icon="IconPin" v-else @click="pinProject()" />
                        <BaseMenuItem white-menu-background title="Duplicate" :icon="IconCopy" @click="duplicateProject()" v-if="role('artwork admin') || can('write projects') || can('management projects') || checkPermission(project, 'edit')" />
                        <BaseMenuItem white-menu-background title="Put in the trash" :icon="IconTrash" @click="openDeleteProjectModal()" v-if="role('artwork admin') || can('delete projects') || checkPermission(project, 'delete')" />
                    </BaseMenu>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <project-create-modal
            v-if="editingProject"
            :show="editingProject"
            :categories="categories"
            :genres="genres"
            :sectors="sectors"
            :project-groups="projectGroups"
            :states="states"
            @close-create-project-modal="closeEditProjectModal"
            :create-settings="createSettings"
            :project="fullProject"
        />

        <!-- Delete Modal -->
        <BaseModal @closed="closeDeleteProjectModal" v-if="deletingProject">
            <div class="mx-4">
                <div class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-zinc-50 my-2">
                    {{ $t('Delete project') }}
                </div>
                <div class="text-sm text-rose-600 dark:text-rose-400">
                    {{ $t('Are you sure you want to delete the project?', [project.name]) }}
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-between mt-6">
                    <button
                        class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full focus:outline-none inline-flex items-center px-8 py-3 text-sm font-semibold uppercase shadow-sm"
                        @click="deleteProject"
                    >
                        {{ $t('Delete') }}
                    </button>
                    <button
                        type="button"
                        @click="closeDeleteProjectModal()"
                        class="text-sm text-zinc-600 dark:text-zinc-300 hover:underline underline-offset-4"
                    >
                        {{ $t('No, not really') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </div>
</template>

<script setup>
import { usePermission } from "@/Composeables/Permission.js";
const { can, role, canSeeComponent } = usePermission(usePage().props);

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
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
import { computed, nextTick, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import BuilderTextField from "@/Pages/Projects/BuilderComponents/BuilderTextField.vue";
import BuilderCheckbox from "@/Pages/Projects/BuilderComponents/BuilderCheckbox.vue";
import BuilderDropDown from "@/Pages/Projects/BuilderComponents/BuilderDropDown.vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import { IconCopy, IconFolderOpen, IconPin, IconPinned, IconPinnedOff, IconTrash } from "@tabler/icons-vue";

const props = defineProps({
    project: { type: Object, required: true },
    components: { type: Object, required: true },
    categories: { type: Object, required: true },
    genres: { type: Object, required: true },
    sectors: { type: Object, required: true },
    projectGroups: { type: Object, required: true },
    states: { type: Object, required: true },
    createSettings: { type: Object, required: true },
    fullProject: { type: Object, required: true },
    gridTemplateColumns: { type: String, required: true },
});

const menuVisible = ref(false);
const menuPosition = ref({ x: 0, y: 0 });
const editingProject = ref(false);
const showActionComponent = ref(false);
const deletingProject = ref(false);

const componentMapping = {
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
};

const page = ref(route().params.page ?? 1);
const perPage = ref(route().params.entitiesPerPage ?? 10);
const query = ref(route().params.query ?? "");

const openMenu = (projectId, event) => {
    menuVisible.value = projectId;
    const rect = event.currentTarget.getBoundingClientRect();
    const offset = 10;
    menuPosition.value = {
        x: event.clientX - rect.left + offset,
        y: event.clientY - rect.top + offset,
    };
    nextTick(() => {
        document.getElementById("project-invisible-menu-" + projectId)?.click();
    });
};

const closeEditProjectModal = () => (editingProject.value = false);

const openProject = (component, project) => {
    if (component.type !== "ActionsComponent") {
        router.visit(route("projects.tab", { project: project.id, projectTab: project.firstTabId }));
    }
};

const openProjectInNewTab = (project) => {
    window.open(route("projects.tab", { project: project.id, projectTab: project.firstTabId }), "_blank");
};

const openProjectInNewTabWithCmdOrSTRG = (event, project) => {
    if (event.metaKey || event.ctrlKey) openProjectInNewTab(project);
};

const gridTemplateColumns = computed(() => props.gridTemplateColumns);

const checkIfComponentIsVisible = (component) => {
    if (component?.type === "ProjectTitleComponent" || component?.type === null) return true;
    else if (component?.type === "ActionsComponent") {
        showActionComponent.value = true;
        return false;
    }
    return canSeeComponent(component.component);
};

const openEditProjectModal = () => (editingProject.value = true);

const checkPermission = (project, type) => {
    const writeAuth = [];
    const managerAuth = [];
    const deleteAuth = [];
    const viewAuth = [];

    project?.users?.forEach((u) => viewAuth.push(u.id));
    project?.project_managers?.forEach((u) => managerAuth.push(u.id));
    project?.write_auth?.forEach((u) => writeAuth.push(u.id));
    project?.delete_permission_users?.forEach((u) => deleteAuth.push(u.id));

    if (viewAuth.includes(usePage().props.auth.user.id) && type === "view") return true;
    if (writeAuth.includes(usePage().props.auth.user.id) && type === "edit") return true;
    if ((managerAuth.includes(usePage().props.auth.user.id) || deleteAuth.includes(usePage().props.auth.user.id)) && type === "delete") return true;
    return false;
};

const pinProject = () => {
    router.post(
        route("project.pin", { project: props.project.id }),
        { page: page.value, entitiesPerPage: perPage.value, query: query.value },
        { preserveScroll: true, preserveState: true }
    );
};

const duplicateProject = () => {
    router.post(
        route("projects.duplicate", { project: props.project.id }),
        { page: page.value, entitiesPerPage: perPage.value, query: query.value },
        { preserveScroll: true }
    );
};

const openDeleteProjectModal = () => (deletingProject.value = true);

const deleteProject = () => {
    router.delete(route("projects.destroy", { project: props.project.id }), {
        data: { page: page.value, entitiesPerPage: perPage.value, query: query.value },
        preserveScroll: true,
    });
    closeDeleteProjectModal();
};

const closeDeleteProjectModal = () => (deletingProject.value = false);
</script>
