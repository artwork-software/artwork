<template>
    <div class="checklist-card w-72 rounded-lg relative">
        <div class="checklist-card-header px-4 py-2 bg-artwork-buttons-create flex items-center justify-between text-white truncate rounded-t-lg">
            <div class="flex items-center gap-x-1">
                        <span v-if="checklist.private">
                            <IconLock stroke-width="1.5" class="h-6 w-6 text-white" />
                        </span>
                {{ checklist.name }}
            </div>
            <BaseMenu v-if="!isInOwnTaskManagement && canEditComponent && (isAdmin || projectCanWriteIds?.includes($page.props.user.id) || projectManagerIds.includes($page.props.user.id)) || checklist.private" no-relative>
                <!--<MenuItem v-slot="{ active }" v-if="!checklist.private">
                <a @click="openEditChecklistTeamsModal()"
                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                    <IconUserPlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                    {{ $t('Assign users') }}
                </a>
            </MenuItem>-->
                <MenuItem v-slot="{ active }">
                    <a @click="showChecklistEditModal = true" v-if="isAdmin" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconEdit stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Edit') }}
                    </a>
                </MenuItem>
                <MenuItem v-slot="{ active }"
                          v-if="!checkIfAllTasksChecked && checklist.tasks.length > 0">
                    <a @click="doneOrUndoneAllTasks(true)"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconListCheck stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Mark all tasks as completed') }}
                    </a>
                </MenuItem>
                <MenuItem v-slot="{ active }" v-if="checkIfAllTasksChecked && checklist.tasks.length > 0">
                    <a @click="doneOrUndoneAllTasks(false)"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconListDetails stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Mark all tasks as unfinished') }}
                    </a>
                </MenuItem>
                <MenuItem
                    v-slot="{ active }">
                    <a @click="createTemplateFromChecklist()"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconFilePlus stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Save as template') }}
                    </a>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <div
                        @click="duplicateChecklist"
                        :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconCopy stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Duplicate') }}
                    </div>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                    <a @click="showDeleteChecklistModal = true"
                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'base-menu-link']">
                        <IconTrash stroke-width="1.5" class="base-menu-icon" aria-hidden="true"/>
                        {{ $t('Delete') }}
                    </a>
                </MenuItem>
            </BaseMenu>

        </div>
        <div class="bg-gray-100 rounded-b-lg">
            <div class="xxsLight w-full text-xs mb-2 flex items-start gap-x-2 px-4 py-2" v-if="isInOwnTaskManagement">
                {{ $t('Project') }}:
                <Link v-if="checklist.project.id" :href="route('projects.tab', {project: checklist.project.id, projectTab: checklist.checklist_tab_id})" class="text-artwork-buttons-create underline flex items-center gap-x-0.5">
                    {{ checklist.project.name }}
                    >
                    {{ checklist.name }}
                </Link>
            </div>
            <draggable :disabled="!canEditComponent" ghost-class="opacity-50" key="draggableKey" item-key="draggableID" :list="checklist.tasks" @start="dragging=true" @end="dragging=false" @change="updateTaskOrder(checklist.tasks)" class="divide-y-2 divide-dashed text-sm">
                <template #item="{element}" :key="element.id">
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
                </template>
            </draggable>
            <div v-if="!isInOwnTaskManagement" class="px-5 py-2.5 cursor-pointer flex items-center text-center justify-center" @click="openAddTaskModal = true" :class="checklist.tasks.length > 0 ? ' border-t-2 border-dashed' : ''">
                <AlertComponent text="Klicke hier um eine Aufgabe zu erstellen" type="info" />
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
</template>

<script setup>
import {
    IconChevronDown, IconChevronRight,
    IconCopy,
    IconEdit,
    IconFilePlus, IconListCheck,
    IconListDetails,
    IconLock,
    IconTrash, IconUserPlus
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

const checkIfAllTasksChecked = computed(() => {
    return props.checklist.tasks.every(task => task.done === true);
});

const templateForm = useForm({
    checklist_id: props.checklist.id,
});


const checkIfUserIsInTaskIfInOwnTaskManagement = (task) => {
    // if isInOwnTaskManagement is true, check if the current user ist in the task
    if (props.isInOwnTaskManagement && !props.checklist.private) {
        return task.users.map(user => user.id).includes(usePage().props.user.id);
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

</script>

<style scoped>

</style>