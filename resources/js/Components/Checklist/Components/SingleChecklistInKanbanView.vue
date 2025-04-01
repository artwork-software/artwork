<template>
    <div class="relative w-72 max-w-72 mb-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-x-1">
                <span v-if="checklist.private">
                    <IconLock stroke-width="1.5" class="h-6 w-6" />
                </span>
                <div class="flex items-center gap-x-4 ">
                   <div class="truncate w-44 print:headline3">
                       {{ checklist.name }}
                   </div>
                    <span class="bg-white text-xs px-2 py-0.5 rounded print:border print:bg-gray-200 print:text-gray-500 print:border-gray-200 print:rounded-lg">
                        {{ checklist.tasks.length }}
                    </span>
                </div>
            </div>
            <div class="flex items-center justify-end gap-x-3 print:hidden">
                <IconCirclePlus v-if="canEditComponent || isInOwnTaskManagement" class="h-5 w-5 cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out" @click="openAddTaskModal = true"/>
                <BaseMenu v-if="(canEditComponent && (isAdmin || projectCanWriteIds?.includes($page.props.auth.user.id) || projectManagerIds.includes($page.props.auth.user.id))) || isInOwnTaskManagement">
                    <MenuItem v-slot="{ active }" v-if="!checklist.private">
                        <div @click="openEditChecklistTeamsModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                            <IconUserPlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Assign users') }}
                        </div>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }">
                        <div @click="showChecklistEditModal = true" v-if="checklist" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                            <IconEdit stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Edit') }}
                        </div>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }" v-if="!checkIfAllTasksChecked && checklist.tasks.length > 0">
                        <div @click="doneOrUndoneAllTasks(true)"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                            <IconListCheck stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Mark all tasks as completed') }}
                        </div>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }" v-if="checkIfAllTasksChecked && checklist.tasks.length > 0">
                        <div @click="doneOrUndoneAllTasks(false)"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                            <IconListDetails stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Mark all tasks as unfinished') }}
                        </div>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }">
                        <div @click="createTemplateFromChecklist()"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                            <IconFilePlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Save as template') }}
                        </div>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }">
                        <div
                            @click="duplicateChecklist"
                            :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                            <IconCopy stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Duplicate') }}
                        </div>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }" v-if="can('can use checklists') && checklist.user_id === usePage().props.auth.user.id || can('can edit checklist') || isAdmin || checklist.user_id === usePage().props.auth.user.id">
                        <div @click="showDeleteChecklistModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                            <IconTrash stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Delete') }}
                        </div>
                    </MenuItem>
                </BaseMenu>
            </div>
        </div>
        <div class="checklist-card-body">
            <div class="checklist-card-body-project text-xs" v-if="checklist.hasProject">
                <div class=" flex gap-x-1">
                    {{ $t('Project') }}:
                    <Link v-if="checklist.project.id" :href="route('projects.tab', {project: checklist.project.id, projectTab: checklist?.project?.checklist_tab_id ?? 1})" class="text-artwork-buttons-create underline flex items-center gap-x-0.5">
                        {{ checklist.project.name }} > {{ checklist.name }}
                    </Link>
                </div>
                <div v-if="checklist?.project?.firstEventInProject && checklist?.project?.lastEventInProject">
                    {{ checklist?.project?.firstEventInProject?.start_time }} -
                    {{ checklist?.project?.lastEventInProject?.end_time }}
                </div>
            </div>
            <div v-for="element in orderTasksByDeadline">
                <SingleTaskInKanbanView
                    :can-edit-component="canEditComponent"
                    :project-manager-ids="projectManagerIds"
                    :project-can-write-ids="projectCanWriteIds"
                    :is-admin="isAdmin"
                    :task="element"
                    :project="project"
                    :tab_id="tab_id"
                    :checklist="checklist"
                    :is-in-own-task-management="isInOwnTaskManagement"
                    v-if="checkIfUserIsInTaskIfInOwnTaskManagement(element)"
                />
            </div>

            <!--<draggable :disabled="!canEditComponent" ghost-class="opacity-50" key="draggableKey" item-key="draggableID" :list="orderTasksByDeadline" @change="updateTaskOrder(checklist.tasks)" class="text-sm">
                <template #item="{element}" :key="element.id">
                    <div>
                        <SingleTaskInKanbanView
                            :can-edit-component="canEditComponent"
                            :project-manager-ids="projectManagerIds"
                            :project-can-write-ids="projectCanWriteIds"
                            :is-admin="isAdmin"
                            :task="element"
                            :project="project"
                            :tab_id="tab_id"
                            :checklist="checklist"
                            :is-in-own-task-management="isInOwnTaskManagement"
                            v-if="checkIfUserIsInTaskIfInOwnTaskManagement(element)"
                        />

                    </div>
                </template>
            </draggable>-->

            <DropTaskInKanbanView :checklist-id="checklist.id"/>
            <div v-if="canEditComponent"   class="checklist-body-add-task" @click="openAddTaskModal = true">
                <AlertComponent :text="$t('Click here to create a task')" type="plus" show-icon icon-size="h-4 w-4" />
            </div>
        </div>
    </div>

    <AddEditChecklistModal
        :checklist_templates="checklist_templates"
        :project="project"
        :checklist-to-edit="checklist"
        :tab_id="tab_id"
        v-if="showChecklistEditModal"
        @closed="showChecklistEditModal = false"
    />

    <DeleteChecklistModal
        :checklist-to-delete="checklist"
        v-if="showDeleteChecklistModal"
        @closed="showDeleteChecklistModal = false"
        @delete-checklist="deleteChecklist"
    />

    <AddEditTaskModal
        :project="project"
        :tab_id="tab_id"
        :checklist="checklist"
        v-if="openAddTaskModal"
        @closed="openAddTaskModal = false"
        :is-private="checklist.private"
    />

    <AddChecklistUserModal
        :checklist="checklist"
        :users="checklist?.users"
        :project="project ?? checklist?.project"
        @closed="openEditChecklistTeamsModal = false"
        v-if="openEditChecklistTeamsModal"
    />
</template>

<script setup>
import {
    IconChevronDown, IconChevronRight,
    IconCopy,
    IconEdit,
    IconFilePlus, IconListCheck,
    IconListDetails,
    IconLock,
    IconTrash, IconUserPlus, IconCirclePlus
} from "@tabler/icons-vue";
import {MenuItem} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import AddEditChecklistModal from "@/Components/Checklist/Modals/AddEditChecklistModal.vue";
import DeleteChecklistModal from "@/Components/Checklist/Modals/DeleteChecklistModal.vue";
import AddEditTaskModal from "@/Components/Checklist/Modals/AddEditTaskModal.vue";
import {computed, ref} from "vue";
import draggable from "vuedraggable";
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import SingleTaskInKanbanView from "@/Components/Checklist/Components/SingleTaskInKanbanView.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import AddChecklistUserModal from "@/Pages/Projects/Components/AddChecklistUserModal.vue";


import { usePermission } from "@/Composeables/Permission.js";
import DropTaskInKanbanView from "@/Components/Checklist/Components/DropTaskInKanbanView.vue";
const {can} = usePermission(usePage().props)
const props = defineProps({
    checklist: {
        type: Object,
        required: true
    },
    canEditComponent: {
        type: Boolean,
        required: false,
        default: false
    },
    projectCanWriteIds: {
        type: Array,
        required: false,
        default: []
    },
    projectManagerIds: {
        type: Array,
        required: false,
        default: []
    },
    isAdmin: {
        type: Boolean,
        required: false,
        default: false
    },
    project: {
        type: Object,
        required: false
    },
    checklist_templates: {
        type: Object,
        required: false
    },
    tab_id: {
        type: Number,
        required: false
    },
    isInOwnTaskManagement: {
        type: Boolean,
        required: false,
        default: false
    }
})

const dragging = ref(false);
const showChecklistEditModal = ref(false);
const showDeleteChecklistModal = ref(false);
const openAddTaskModal = ref(false);
const openEditChecklistTeamsModal = ref(false);


const checkIfAllTasksChecked = computed(() => {
    return props.checklist.tasks.every(task => task.done === true);
});

const templateForm = useForm({
    checklist_id: props.checklist.id,
});

const checkIfUserIsInTaskIfInOwnTaskManagement = (task) => {
    // if isInOwnTaskManagement is true, check if the current user ist in the task
    if (props.isInOwnTaskManagement && !props.checklist.private) {
        return task?.users.map(user => user.id).includes(usePage().props.auth.user.id);
    } else {
        return true;
    }
};

const doneOrUndoneAllTasks = (bool) => {
    router.patch(route('checklists.doneOrUndone.all.tasks', {checklist: props.checklist.id}), {
        done: bool,
    }, {
        preserveState: true,
        preserveScroll: true
    });
}

const createTemplateFromChecklist = () => {
    templateForm.post(route('checklist_templates.store'), {
        preserveState: true,
        preserveScroll: true
    });
}

const duplicateChecklist = () => {
    router.post(route('checklists.duplicate', {checklist: props.checklist.id}), {
        preserveState: true,
        preserveScroll: true
    });
}

const deleteChecklist = () => {
    router.delete(route('checklist.destroy', {checklist: props.checklist.id}), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteChecklistModal.value = false;
        }
    });
}

const updateTaskOrder = (checklistTasks) => {
    checklistTasks.map((task, index) => {
        task.order = index + 1
    })

    router.put(route('tasks.order'), {
        checklistTasks
    }, {
        preserveState: true,
        preserveScroll: true
    })
}

const orderTasksByDeadline = computed(() => {
    // Erstelle eine tiefe Kopie der Aufgaben, um sicherzustellen, dass keine Reaktivität verloren geht
    const tasksCopy = JSON.parse(JSON.stringify(props.checklist.tasks));

    // Partitioniere die Aufgaben in nicht erledigte und erledigte Aufgaben
    const notDoneTasks = tasksCopy.filter(task => !task.done);
    const doneTasks = tasksCopy.filter(task => task.done);

    // Sortiere die nicht erledigten Aufgaben nach Deadline
    notDoneTasks.sort((a, b) => {
        if (a.deadlineDate && b.deadlineDate) {
            return new Date(a.deadlineDate) - new Date(b.deadlineDate);
        }
        if (a.deadlineDate) {
            return -1; // A hat eine Deadline, B nicht
        }
        if (b.deadlineDate) {
            return 1; // B hat eine Deadline, A nicht
        }
        return 0; // Keine Deadline bei beiden
    });

    // Gib eine neue Liste zurück, die nicht erledigte und dann erledigte Aufgaben enthält
    return notDoneTasks.concat(doneTasks);
});

</script>

<style scoped>

</style>
