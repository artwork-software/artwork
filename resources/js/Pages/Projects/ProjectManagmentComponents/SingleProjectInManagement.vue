<template>
    <div :key="project.id"
         @mousedown.middle="openProjectInNewTab(project)"
         @mousedown="openProjectInNewTabWithCmdOrSTRG($event, project)"
        class="grid bg-gray-50/50 px-3 py-3 rounded-lg border border-gray-100 hover:bg-gray-100 duration-300 ease-in-out group/project w-fit relative cursor-pointer"
        :style="`grid-template-columns: ${gridTemplateColumns}`"
        @contextmenu.prevent="openMenu(project.id, $event)">
        <div class="absolute -top-3" v-if="fullProject?.pinned_by_users && fullProject?.pinned_by_users.includes($page.props.auth.user.id)">
            <div class="rounded-full p-0.5 bg-white border border-gray-100">
                <component is="IconPinned" class="h-6 w-6" />
            </div>
        </div>
        <div
            v-for="component in components"
            :key="component.name"
            class="px-3 flex items-center"
            :class="component.type === 'ActionsComponent' ? 'flex justify-end' : ''"
            @click="openProject(component, project)"
        >
            <component
                v-if="checkIfComponentIsVisible(component)"
                :is="componentMapping['Builder' + component.type]"
                :project="project"
                :component="component"
                :menu-visible="menuVisible"
                :menu-position="menuPosition"
            />

            <BaseMenu has-no-offset v-show="showActionComponent && component.type === 'ActionsComponent'"  v-if="checkPermission(project, 'edit') || checkPermission(project, 'delete') || role('artwork admin') || can('delete projects') || can('write projects')">
                <BaseMenuItem as-link :link="route('projects.tab', { project: project.id, projectTab: project?.firstTabId })" title="Open" icon="IconFolderOpen"/>
                <BaseMenuItem title="Edit basic data" @click="openEditProjectModal()" v-if="role('artwork admin') || can('write projects') || checkPermission(project, 'edit')"/>
                <BaseMenuItem title="Undo pinning" icon="IconPinnedOff" v-if="fullProject.pinned_by_users && fullProject.pinned_by_users.includes($page.props.auth.user.id)" @click="pinProject()"/>
                <BaseMenuItem title="Pin" icon="IconPin" v-else @click="pinProject()"/>
                <BaseMenuItem title="Duplicate" icon="IconCopy" @click="duplicateProject()" v-if="role('artwork admin') || can('write projects') || can('management projects') || checkPermission(project, 'edit')" />
                <BaseMenuItem title="Put in the trash" icon="IconTrash" @click="openDeleteProjectModal()" v-if="role('artwork admin') || can('delete projects') || checkPermission(project, 'delete')"/>
            </BaseMenu>
        </div>
        <div
            v-if="menuVisible === project.id"
            :style="{ top: `${menuPosition.y}px`, left: `${menuPosition.x}px` }"
            class="absolute z-50"
        >
            <BaseMenu has-no-offset :button-id="'project-invisible-menu-' + project.id" :show-icon="false" v-if="checkPermission(project, 'edit') || checkPermission(project, 'delete') || role('artwork admin') || can('delete projects') || can('write projects')">
                <BaseMenuItem as-link :link="route('projects.tab', { project: project.id, projectTab: project?.firstTabId })" title="Open" icon="IconFolderOpen"/>
                <BaseMenuItem title="Edit basic data" @click="openEditProjectModal()" v-if="role('artwork admin') || can('write projects') || checkPermission(project, 'edit')"/>
                <BaseMenuItem title="Undo pinning" icon="IconPinnedOff" v-if="fullProject.pinned_by_users && fullProject.pinned_by_users.includes($page.props.auth.user.id)" @click="pinProject()"/>
                <BaseMenuItem title="Pin" icon="IconPin" v-else @click="pinProject()"/>
                <BaseMenuItem title="Duplicate" icon="IconCopy" @click="duplicateProject()" v-if="role('artwork admin') || can('write projects') || can('management projects') || checkPermission(project, 'edit')" />
                <BaseMenuItem title="Put in the trash" icon="IconTrash" @click="openDeleteProjectModal()" v-if="role('artwork admin') || can('delete projects') || checkPermission(project, 'delete')"/>
            </BaseMenu>
        </div>
    </div>

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

    <BaseModal @closed="closeDeleteProjectModal" v-if="deletingProject">
        <div class="mx-4">
            <div class="font-black font-lexend text-primary text-3xl my-2">
                {{ $t('Delete project') }}
            </div>
            <div class="text-error subpixel-antialiased">
                {{ $t('Are you sure you want to delete the project?', [project.name]) }}
            </div>
            <div class="flex justify-between mt-6">
                <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover rounded-full focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent text-base font-bold uppercase shadow-sm text-white" @click="deleteProject">
                    {{ $t('Delete') }}
                </button>
                <div class="flex my-auto">
                    <span @click="closeDeleteProjectModal()" class="xsLight cursor-pointer">
                        {{ $t('No, not really') }}
                    </span>
                </div>
            </div>
        </div>
    </BaseModal>


</template>

<script setup>
import {usePermission} from "@/Composeables/Permission.js";

const {can, hasAdminRole, role, canSeeComponent, canEditComponent} = usePermission(usePage().props);

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BuilderProjectTitleComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectTitleComponent.vue";
import BuilderProjectTeamComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectTeamComponent.vue";
import BuilderActionsComponent from "@/Pages/Projects/BuilderComponents/BuilderActionsComponent.vue";
import BuilderTextArea from "@/Pages/Projects/BuilderComponents/BuilderTextArea.vue";
import BuilderProjectStateComponent from "@/Pages/Projects/BuilderComponents/BuilderProjectStateComponent.vue";
import BuilderShiftContactPersonsComponent
    from "@/Pages/Projects/BuilderComponents/BuilderShiftContactPersonsComponent.vue";
import BuilderRelevantDatesForShiftPlanningComponent
    from "@/Pages/Projects/BuilderComponents/BuilderRelevantDatesForShiftPlanningComponent.vue";
import BuilderGeneralShiftInformationComponent
    from "@/Pages/Projects/BuilderComponents/BuilderGeneralShiftInformationComponent.vue";
import BuilderProjectBudgetDeadlineComponent
    from "@/Pages/Projects/BuilderComponents/BuilderProjectBudgetDeadlineComponent.vue";
import BuilderProjectAttributesComponent
    from "@/Pages/Projects/BuilderComponents/BuilderProjectAttributesComponent.vue";
import BuilderBudgetInformations from "@/Pages/Projects/BuilderComponents/BuilderBudgetInformation.vue";
import {computed, nextTick, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import BuilderTextField from "@/Pages/Projects/BuilderComponents/BuilderTextField.vue";
import BuilderCheckbox from "@/Pages/Projects/BuilderComponents/BuilderCheckbox.vue";
import BuilderDropDown from "@/Pages/Projects/BuilderComponents/BuilderDropDown.vue";
import ProjectCreateModal from "@/Layouts/Components/ProjectCreateModal.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    components: {
        type: Object,
        required: true,
    },
    categories: {
        type: Object,
        required: true,
    },
    genres: {
        type: Object,
        required: true,
    },
    sectors: {
        type: Object,
        required: true,
    },
    projectGroups: {
        type: Object,
        required: true,
    },
    states: {
        type: Object,
        required: true,
    },
    createSettings: {
        type: Object,
        required: true,
    },
    fullProject: {
        type: Object,
        required: true,
    },

});

const menuVisible = ref(false);
const menuPosition = ref({ x: 0, y: 0 });
const currentProjectId = ref(null);
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
    BuilderDropDown
};

const page = ref(route().params.page ?? 1);
const perPage = ref(route().params.entitiesPerPage ?? 10);
const query = ref(route().params.query ?? '');
const openMenu = (projectId, event) => {
    menuVisible.value = projectId;
    currentProjectId.value = projectId;
    const boundingRect = event.currentTarget.getBoundingClientRect();
    const offset = 10;
    menuPosition.value = {
        x: event.clientX - boundingRect.left + offset,
        y: event.clientY - boundingRect.top + offset,
    };

    nextTick(() => {
        document.getElementById('project-invisible-menu-' + projectId).click();
    })
}

const closeEditProjectModal = () => {
    editingProject.value = false;
}

const openProject = (component, project) => {
    if (component.type !== 'ActionsComponent') {
        router.visit(route('projects.tab', { project: project.id, projectTab: project.firstTabId }));
    }
};

const openProjectInNewTab = (project) => {
    window.open(route('projects.tab', { project: project.id, projectTab: project.firstTabId }), '_blank');
};

const openProjectInNewTabWithCmdOrSTRG = (event, project) => {
    if (event.metaKey || event.ctrlKey) {
        openProjectInNewTab(project);
    }
}

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
        component?.type === null
    ) {
        return true;
    } else if (component?.type === 'ActionsComponent') {
        showActionComponent.value = true;
        return false;
    } else {
        return canSeeComponent(component.component);
    }
}

const openEditProjectModal = () => {
    editingProject.value = true;
}

const checkPermission = (project, type) => {
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

    if(viewAuth.includes(usePage().props.auth.user.id) && type === 'view') {
        return true;
    }

    if (writeAuth.includes(usePage().props.auth.user.id) && type === 'edit') {
        return true;
    }
    if (managerAuth.includes(usePage().props.auth.user.id) || deleteAuth.includes(usePage().props.auth.user.id) && type === 'delete') {
        return true;
    }
    return false;
}

const pinProject = () => {
    router.post(route('project.pin', {project: props.project.id}), {
        page: page.value,
        entitiesPerPage: perPage.value,
        query: query.value
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

const duplicateProject = () => {
    router.post(route('projects.duplicate', {project: props.project.id}), {
        page: page.value,
        entitiesPerPage: perPage.value,
        query: query.value
    }, {
        preserveScroll: true,
    });
}

const openDeleteProjectModal = () => {
    deletingProject.value = true;
}

const deleteProject = () => {
    router.delete(route('projects.destroy', {project: props.project.id}), {
        data: {
            page: page.value,
            entitiesPerPage: perPage.value,
            query: query.value
        },
        preserveScroll: true,
    });
    closeDeleteProjectModal();
}

const closeDeleteProjectModal = () => {
    deletingProject.value = false;
}
</script>

<style scoped>

</style>