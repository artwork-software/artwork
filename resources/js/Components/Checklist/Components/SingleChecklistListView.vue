<template>
    <div class="" :class="$page.props.auth.user.opened_checklists.includes(checklist?.id) ? 'border-artwork-buttons-create' : 'border-gray-400'">
        <div class="p-5">
            <div class="flex items-center justify-between mb-4 bg-blue-50 rounded-lg px-4 py-3">
                <div class="flex items-center gap-x-1">
                        <span v-if="checklist.private">
                            <IconLock stroke-width="1.5" class="h-6 w-6" />
                        </span>
                    <div class="truncate print:headline3 text-xs font-semibold flex items-center gap-x-1">
                        <span>{{ checklist.name }}</span>
                        <Link
                            v-if="checklist.hasProject && checklist?.project?.id"
                            :href="route('projects.tab', {project: checklist?.project?.id, projectTab: checklist?.project?.checklist_tab_id ?? 1})"
                            class="text-artwork-buttons-create hover:underline whitespace-nowrap"
                        >
                            ({{ checklist?.project?.name }})
                        </Link>
                    </div>
                </div>
                <div class="flex items-center gap-x-2 print:hidden">
                        <span class="bg-blue-50 border border-blue-200 text-blue-500 text-xs px-2 py-0.5 rounded print:border print:bg-gray-200 print:text-gray-500 print:border-gray-200 print:rounded-lg">
                            {{ checklist.tasks.length }}
                        </span>
                    <IconCirclePlus v-if="canEditComponent || isInOwnTaskManagement" class="h-5 w-5 cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out print:hidden" @click.stop="openAddTaskModal = true"/>
                    <BaseMenu has-no-offset white-menu-background v-if="(canEditComponent && (isAdmin || projectCanWriteIds?.includes($page.props.auth.user.id) || projectManagerIds.includes($page.props.auth.user.id))) || isInOwnTaskManagement">
                        <BaseMenuItem icon="IconUserPlus" title="Assign users" white-menu-background v-if="!checklist.private" @click="openEditChecklistTeamsModal = true"/>
                        <BaseMenuItem icon="IconEdit" title="Edit" white-menu-background v-if="checklist" @click="showChecklistEditModal = true"/>
                        <BaseMenuItem icon="IconListCheck" title="Mark all tasks as completed" white-menu-background v-if="!checkIfAllTasksChecked && checklist.tasks.length > 0" @click="doneOrUndoneAllTasks(true)"/>
                        <BaseMenuItem icon="IconListDetails" title="Mark all tasks as unfinished" white-menu-background v-if="checkIfAllTasksChecked && checklist.tasks.length > 0" @click="doneOrUndoneAllTasks(false)"/>
                        <BaseMenuItem icon="IconFilePlus" title="Save as template" white-menu-background  @click="createTemplateFromChecklist"/>
                        <BaseMenuItem icon="IconCopy" title="Duplicate" white-menu-background  @click="duplicateChecklist"/>
                        <BaseMenuItem icon="IconTrash" title="Delete" white-menu-background  @click="showDeleteChecklistModal = true" v-if="can('can use checklists') && checklist.user_id === usePage().props.auth.user.id || can('can edit checklist') || isAdmin || checklist.user_id === usePage().props.auth.user.id"/>
                    </BaseMenu>
                    <div class="h-6 w-6 cursor-pointer" @click.stop="changeChecklistStatus(checklist)">
                        <IconChevronDown class="h-6 w-6" :class="$page.props.auth.user.opened_checklists.includes(checklist?.id) ? 'rotate-180' : 'closed'" />
                    </div>
                </div>
            </div>
            <div class="my-4 border-b border-dashed border-gray-200 pb-3" v-if="$page.props.auth.user.opened_checklists.includes(checklist?.id)">
                <div class="">
                    <SingleTaskInListView
                        v-for="element in orderTasksByDeadline"
                        :key="`task-${element.id}`"
                        v-if="checkIfUserIsInTaskIfInOwnTaskManagement(element)"
                        :can-edit-component="canEditComponent"
                        :project-manager-ids="projectManagerIds"
                        :project-can-write-ids="projectCanWriteIds"
                        :is-admin="isAdmin"
                        :task="element"
                        :project="project ?? checklist?.project"
                        :tab_id="tab_id"
                        :checklist="checklist"
                        :is-in-own-task-management="isInOwnTaskManagement"
                    />
                </div>
            </div>

            <div class="px-5 py-2.5 cursor-pointer flex items-center text-center justify-center gap-x-4 h-8" @click="openAddTaskModal = true">
                <AlertComponent :text="$t('Click here to create a task')" type="info" />
                <DropTaskInListView :checklist-id="checklist.id" />
            </div>
        </div>
    </div>
    <AddEditChecklistModal
        :checklist_templates="checklist_templates"
        :project="project ?? checklist?.project"
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
        :project="project ?? checklist?.project"
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
    IconChevronDown,
    IconCopy,
    IconEdit,
    IconFilePlus, IconListCheck,
    IconListDetails,
    IconLock,
    IconTrash, IconUserPlus, IconChevronRight, IconCirclePlus
} from "@tabler/icons-vue";
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import axios from "axios";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import SingleTaskInListView from "@/Components/Checklist/Components/SingleTaskInListView.vue";
import draggable from "vuedraggable";
import AddEditChecklistModal from "@/Components/Checklist/Modals/AddEditChecklistModal.vue";
import {computed, ref} from "vue";
import DeleteChecklistModal from "@/Components/Checklist/Modals/DeleteChecklistModal.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import AddEditTaskModal from "@/Components/Checklist/Modals/AddEditTaskModal.vue";
import AddChecklistUserModal from "@/Pages/Projects/Components/AddChecklistUserModal.vue";
import { usePermission } from "@/Composeables/Permission.js";
import DropTaskInKanbanView from "@/Components/Checklist/Components/DropTaskInKanbanView.vue";
import DropTaskInListView from "@/Components/Checklist/Components/DropTaskInListView.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
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
    const tasks = props.checklist.tasks;
    return tasks.every(task => task.done === true);
});


const templateForm = useForm({
    checklist_id: props.checklist?.id,
});

const checkIfAllTasksAreDone = computed(() => {
    return props.checklist.tasks.every(task => task.done === true);
});

const countDoneTasks = computed(() => {
    return props.checklist.tasks.filter(task => task.done === true).length;
});

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

const checkIfUserIsInTaskIfInOwnTaskManagement = (task) => {
    // if isInOwnTaskManagement is true, check if the current user is in the task or checklist
    if (props.isInOwnTaskManagement && !props.checklist.private) {
        const userId = usePage().props.auth.user.id;
        // User is assigned to the task directly
        const isInTask = task?.users?.map(user => user.id)?.includes(userId);
        // User is assigned to the checklist itself → show all tasks
        const isInChecklist = props.checklist?.users?.some(user => user.id === userId);
        return isInTask || isInChecklist;
    } else {
        return true;
    }
};

const changeChecklistStatus = (checklist) => {
    const user = usePage().props.auth.user;
    const isOpen = user.opened_checklists.includes(checklist.id);

    // Update local state immediately for instant UI feedback
    if (isOpen) {
        user.opened_checklists = user.opened_checklists.filter(id => id !== checklist.id);
    } else {
        user.opened_checklists = [...user.opened_checklists, checklist.id];
    }

    // Persist to backend via axios (no page reload)
    axios.patch(route('user.checklists.update', user.id), {
        opened_checklists: user.opened_checklists
    });
}

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
    router.post(route('checklists.duplicate', {checklist: props.checklist.id}), {}, {
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
</script>

<style scoped>

</style>
