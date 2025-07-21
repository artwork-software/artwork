<template>
    <div class="px-4 py-3 group rounded-lg shadow bg-white my-3 print:border print:border-gray-200 print:rounded-lg print:shadow-none"
         draggable="true"
         @dragstart="onDragStart"
         @dragend="onDragEnd"
    >
        <div class="flex items-start justify-between">
            <div class="flex items-start">
                <div class="mr-3">
                    <input @change="updateTaskStatus"
                           v-model="task.done"
                           type="checkbox"
                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300 rounded-full"/>
                </div>
                <div :class="task.done ? 'text-secondary line-through' : ''">
                    <div class="font-bold mb-1">
                        {{ task.name }}
                    </div>

                </div>
            </div>
            <div>
                <BaseMenu class="ml-3" v-if="(canEditComponent && (projectCanWriteIds?.includes($page.props.auth.user.id) || projectManagerIds?.includes($page.props.auth.user.id) || isAdmin)) || isInOwnTaskManagement">
                    <MenuItem v-slot="{ active }">
                        <div @click="openEditTaskModal = true"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconEdit stroke-width="1.5"
                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                      aria-hidden="true"/>
                            {{ $t('Edit') }}
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a @click="openDeleteTaskModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconTrash stroke-width="1.5"
                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                       aria-hidden="true"/>
                            {{ $t('Delete') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
            </div>
        </div>
        <div class="xxsLight mt-1">
            {{ task.description }}
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="">
                <div class="flex">
                    <span class="flex -mr-3" v-for="(user, index) in task.users">
                        <UserPopoverTooltip :id="task.id + 'user' + user.id" :user="user" height="8" width="8" :classes="index > 0 ? '!ring-1 !ring-white' : ''"/>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs flex justify-end">
                    <div v-if="!task.done" class="flex items-center justify-end">
                        <IconCalendar class="h-4 w-4 mr-1 text-gray-400"  :class="Date.parse(task.deadline_dt_local) < new Date().getTime()? 'text-red-500' : ''"/>
                        <div class="text-[9px]" :class="Date.parse(task.deadline_dt_local) < new Date().getTime()? 'bg-red-500 px-1 py-0.5 rounded-lg text-white subpixel-antialiased' : ''">{{ task.formatted_dates.deadline }}</div>
                    </div>
                    <div v-if="task.done && task.done_by_user">
                        <div class="flex items-center">
                            <div class="mr-2">
                                <UserPopoverTooltip v-if="task.done_by_user" height="4" width="4" :user="task.done_by_user" :id="task.id"/>
                            </div>
                            <div class="flex items-center justify-end">
                                <IconCalendar class="h-4 w-4 mr-1 text-gray-400"/>
                                <div class="text-[9px]" >{{ task.formatted_dates.done_at }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <AddEditTaskModal
            :project="project"
            :tab_id="tab_id"
            :checklist="checklist"
            :task-to-edit="task"
            v-if="openEditTaskModal"
            @closed="openEditTaskModal = false"
            :is-private="checklist.private"
        />
        <ConfirmDeleteModal
            :title="$t('Delete task')"
            :description="$t('Are you sure you want to delete this task?')"
            v-if="openDeleteTaskModal"
            @closed="openDeleteTaskModal = false"
            @delete="deleteTask"
        />
    </div>
</template>

<script setup>

import {computed, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import AddEditTaskModal from "@/Components/Checklist/Modals/AddEditTaskModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {IconEdit, IconTrash, IconCalendar} from "@tabler/icons-vue";
import {MenuItem} from "@headlessui/vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";
const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging();

const props = defineProps({
    task: {
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
        required: false,
        default: null
    },
    tab_id: {
        type: Number,
        required: false
    },
    checklist: {
        type: Object,
        required: false
    },
    isInOwnTaskManagement: {
        type: Boolean,
        required: false,
        default: false
    }
})

const openEditTaskModal = ref(false)
const openDeleteTaskModal = ref(false)
const updateTaskStatus = () => {
    router.patch(route('tasks.done', {task: props.task.id}), {
    }, {
        preserveScroll: true,
        preserveState: false
    });
}


const onDragStart = (event) => {
    event.dataTransfer.setData('task', JSON.stringify(props.task));
    dispatchEventStart()
};

const onDragEnd = () => {
    // Dispatch ein globales Event zum Beenden
    dispatchEventEnd()
};

const deleteTask = () => {
    router.delete(route('tasks.destroy', {task: props.task.id}), {
        preserveScroll: true,
        onSuccess: () => {
            openDeleteTaskModal.value = false;
        }
    });
}
</script>

<style scoped>

</style>
