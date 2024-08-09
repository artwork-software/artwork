<template>
    <div class="flex items-center justify-between mb-2 relative">
        <div class="w-96 flex items-center gap-x-5 justify-between cursor-pointer relative">
            <div class="flex items-center gap-x-1 headline3" @click="changeChecklistStatus(checklist)">
                <span v-if="checklist?.private">
                    <IconLock stroke-width="1.5" class="h-6 w-6 text-primary" />
                </span>
                {{ checklist?.name }}
                <div>
                    <IconChevronDown class="h-6 w-6 text-primary" :class="$page.props.user.opened_checklists.includes(checklist.id) ? 'rotate-180' : 'closed'" />
                </div>
            </div>
            <BaseMenu v-if="(canEditComponent && (isAdmin || projectCanWriteIds?.includes($page.props.user.id) || projectManagerIds.includes($page.props.user.id))) || isInOwnTaskManagement" no-relative>
                <MenuItem as="div" v-slot="{ active }" v-if="!checklist.private">
                    <a @click="openEditChecklistTeamsModal = true"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconUserPlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Assign users') }}
                    </a>
                </MenuItem>
                <MenuItem as="div" v-slot="{ active }">
                    <a @click="showChecklistEditModal = true" v-if="isAdmin" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconEdit stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Edit') }}
                    </a>
                </MenuItem>
                <MenuItem as="div" v-slot="{ active }"
                          v-if="!checkIfAllTasksChecked && checklist.tasks.length > 0">
                    <a @click="doneOrUndoneAllTasks(true)"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconListCheck stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Mark all tasks as completed') }}
                    </a>
                </MenuItem>
                <MenuItem as="div" v-slot="{ active }" v-if="checkIfAllTasksChecked && checklist.tasks.length > 0">
                    <a @click="doneOrUndoneAllTasks(false)"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconListDetails stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Mark all tasks as unfinished') }}
                    </a>
                </MenuItem>
                <MenuItem as="div"
                    v-slot="{ active }">
                    <a @click="createTemplateFromChecklist "
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconFilePlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Save as template') }}
                    </a>
                </MenuItem>
                <MenuItem as="div" v-slot="{ active }">
                    <div
                        @click="duplicateChecklist"
                        :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconCopy stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Duplicate') }}
                    </div>
                </MenuItem>
                <MenuItem as="div" v-slot="{ active }">
                    <a @click="showDeleteChecklistModal = true"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconTrash stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Delete') }}
                    </a>
                </MenuItem>
            </BaseMenu>
        </div>

        <div class="flex items-center gap-x-1 text-xs" v-if="checklist.tasks.length > 0">
            <div v-for="task in checklist.tasks" class="w-2.5 h-2.5 flex flex-col justify-center overflow-hidden  text-xs text-white text-center whitespace-nowrap transition duration-500" v-show="!checkIfAllTasksAreDone" :class="task.done ? 'bg-teal-500' : 'bg-gray-500'" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
            <div class="ms-1" v-if="checkIfAllTasksAreDone">
                <span class="flex-shrink-0 ms-auto size-4 flex justify-center items-center rounded-full bg-teal-500 text-white">
                  <svg class="flex-shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                </span>
            </div>
            <div v-else class="ml-1">
                {{ countDoneTasks }} / {{ checklist.tasks.length }}
            </div>
        </div>
    </div>

    <div v-if="$page.props.user.opened_checklists.includes(checklist.id)">
        <div class="mb-2 text-xs" v-if="checklist.hasProject">
           <div class=" flex gap-x-1">
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
        <div class="border-l-4 border-l-artwork-buttons-create border rounded-lg shadow-md">
            <draggable :disabled="!canEditComponent" ghost-class="opacity-50" key="draggableKey" item-key="draggableID" :list="orderTasksByDeadline" @start="dragging = true" @end="dragging = false" @change="updateTaskOrder(checklist.tasks)" class="divide-y-2 divide-dashed text-sm">
                <template #item="{element}" :key="element.id">
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
                    />
                </template>
            </draggable>
            <div class="px-5 py-2.5 cursor-pointer flex items-center text-center justify-center" @click="openAddTaskModal = true" :class="checklist.tasks.length > 0 ? ' border-t-2 border-dashed' : ''">
                <AlertComponent :text="$t('Click here to create a task')" type="info" />
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
        return task.users.map(user => user.id).includes(usePage().props.user.id);
    } else {
        return true;
    }
};

const changeChecklistStatus = (checklist) => {
    if (!usePage().props.user.opened_checklists.includes(checklist.id)) {
        const openedChecklists = usePage().props.user.opened_checklists;

        openedChecklists.push(checklist.id)

        router.patch(route('user.checklists.update', usePage().props.user.id), {
            "opened_checklists": openedChecklists
        }, {
            preserveState: true,
            preserveScroll: true
        });
    } else {
        const filteredList = usePage().props.user.opened_checklists.filter(function (value) {
            return value !== checklist.id;
        })
        router.patch(route('user.checklists.update', usePage().props.user.id), {
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