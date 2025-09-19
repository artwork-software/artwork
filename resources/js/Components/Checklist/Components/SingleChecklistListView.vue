<template>
    <div class="card glassy" :class="$page.props.auth.user.opened_checklists.includes(checklist?.id) ? 'border-artwork-buttons-create' : 'border-gray-400'">
        <div class="card glassy p-5">
            <div class="flex items-center justify-between w-fit">
                <div class="flex items-center gap-x-3 cursor-pointer" @click="changeChecklistStatus(checklist)">
                    <span v-if="checklist.private">
                        <IconLock stroke-width="1.5" class="h-6 w-6" />
                    </span>
                    <div class="font-bold">
                        {{ checklist?.name }}
                    </div>
                    <div>
                        <component :is="IconChevronDown" class="h-6 w-6" :class="$page.props.auth.user.opened_checklists.includes(checklist?.id) ? 'rotate-180' : 'closed'" />
                    </div>
                </div>

                <BaseMenu has-no-offset v-if="(canEditComponent && (isAdmin || projectCanWriteIds?.includes($page.props.auth.user.id) || projectManagerIds.includes($page.props.auth.user.id))) || isInOwnTaskManagement" >
                    <MenuItem as="div" v-slot="{ active }" v-if="!checklist.private">
                        <a @click="openEditChecklistTeamsModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'base-menu-link']">
                            <IconUserPlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Assign users') }}
                        </a>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }">
                        <a @click="showChecklistEditModal = true" :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'base-menu-link']">
                            <IconEdit stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Edit') }}
                        </a>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }"
                              v-if="!checkIfAllTasksChecked && checklist.tasks.length > 0">
                        <a @click="doneOrUndoneAllTasks(true)"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'base-menu-link']">
                            <IconListCheck stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Mark all tasks as completed') }}
                        </a>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }" v-if="checkIfAllTasksChecked && checklist.tasks.length > 0">
                        <a @click="doneOrUndoneAllTasks(false)"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'base-menu-link']">
                            <IconListDetails stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Mark all tasks as unfinished') }}
                        </a>
                    </MenuItem>
                    <MenuItem as="div"
                              v-slot="{ active }">
                        <a @click="createTemplateFromChecklist "
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'base-menu-link']">
                            <IconFilePlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Save as template') }}
                        </a>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }">
                        <div
                            @click="duplicateChecklist"
                            :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'base-menu-link']">
                            <IconCopy stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Duplicate') }}
                        </div>
                    </MenuItem>
                    <MenuItem as="div" v-slot="{ active }" v-if="can('can use checklists') && checklist.user_id === usePage().props.auth.user.id || can('can edit checklist') || isAdmin || checklist.user_id === usePage().props.auth.user.id">
                        <a @click="showDeleteChecklistModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'base-menu-link']">
                            <IconTrash stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                            {{ $t('Delete') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
            </div>
            <div class="my-4 border-b border-dashed pb-3" v-if="$page.props.auth.user.opened_checklists.includes(checklist?.id)">
                <div class="text-xs" v-if="checklist.hasProject">
                    <div class="flex gap-x-1">
                        {{ $t('Project') }}:
                        <Link v-if="checklist?.project?.id" :href="route('projects.tab', {project: checklist?.project?.id, projectTab: checklist?.project?.checklist_tab_id ?? 1})" class="text-artwork-buttons-create underline flex items-center gap-x-0.5">
                            {{ checklist?.project?.name }}
                            <IconChevronRight class="h-4 w-4 text-primary" />
                            {{ checklist.name }}
                        </Link>
                    </div>
                    <div v-if="checklist?.project?.firstEventInProject && checklist?.project?.lastEventInProject">
                        {{ checklist?.project?.firstEventInProject?.start_time }} -
                        {{ checklist?.project?.lastEventInProject?.end_time }}
                    </div>
                </div>
                <div class="">
                    <div class="card white my-2 p-4" v-for="element in orderTasksByDeadline" :key="element.id">
                        <SingleTaskInListView
                            :can-edit-component="canEditComponent"
                            :project-manager-ids="projectManagerIds"
                            :project-can-write-ids="projectCanWriteIds"
                            :is-admin="isAdmin"
                            :task="element"
                            :project="project ?? checklist?.project"
                            :tab_id="tab_id"
                            :checklist="checklist"
                            :is-in-own-task-management="isInOwnTaskManagement"
                            v-if="checkIfUserIsInTaskIfInOwnTaskManagement(element)"

                        />
                    </div>
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
    IconTrash, IconUserPlus, IconChevronRight
} from "@tabler/icons-vue";
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
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
    // if isInOwnTaskManagement is true, check if the current user ist in the task
    if (props.isInOwnTaskManagement && !props.checklist.private) {
        return task?.users.map(user => user.id)?.includes(usePage().props.auth.user.id);
    } else {
        return true;
    }
};

const changeChecklistStatus = (checklist) => {
    if (!usePage().props.auth.user.opened_checklists.includes(checklist.id)) {
        const openedChecklists = usePage().props.auth.user.opened_checklists;

        openedChecklists.push(checklist.id)

        router.patch(route('user.checklists.update', usePage().props.auth.user.id), {
            "opened_checklists": openedChecklists
        }, {
            preserveState: true,
            preserveScroll: true
        });
    } else {
        const filteredList = usePage().props.auth.user.opened_checklists.filter(function (value) {
            return value !== checklist.id;
        })
        router.patch(route('user.checklists.update', usePage().props.auth.user.id), {
            "opened_checklists": filteredList
        }, {
            preserveState: true,
            preserveScroll: true
        });
    }
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
</script>

<style scoped>

</style>
