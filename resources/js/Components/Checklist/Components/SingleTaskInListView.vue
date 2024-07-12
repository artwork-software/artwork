<template>
    <div class="px-5 py-2.5 cursor-grab">
        <div class="grid grid-cols-12 grid-rows-1 gap-x-4 py-1 group" :key="task.id">
            <div class="col-span-6">
                <div class="flex items-start">
                    <div class="mr-3">
                        <input :disabled="canEditComponent ? false : isInOwnTaskManagement ? false : true"
                               @change="updateTaskStatus"
                               v-model="task.done"
                               type="checkbox"
                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300 rounded-full"/>
                    </div>
                    <div :class="task.done  ? 'text-secondary line-through' : ''">
                        <div class="font-bold mb-1">
                            {{ task.name }}
                        </div>
                        <div class="xxsLight">
                            {{ task.description }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-3 col-start-7 flex items-start">
                <span v-if="!task.done" class="ml-2 text-sm subpixel-antialiased" :class="Date.parse(task.deadline_dt_local) < new Date().getTime()? 'text-error subpixel-antialiased' : ''">
                    {{ task.formatted_dates.deadline }}
                </span>
                <span v-if="task.done && task.done_by_user" class="ml-2 flex text-sm text-secondary">
                    <span class="mr-2">
                        <UserPopoverTooltip v-if="task.done_by_user" height="7" width="7" :user="task.done_by_user" :id="task.id"/>
                    </span>
                    {{ task.formatted_dates.done_at_with_day }}
                </span>
            </div>
            <div class="col-span-3 col-start-10 flex justify-between">
                <div class="mx-3 flex">
                    <span class="flex -mr-2" v-for="(user, index) in task.users">
                        <UserPopoverTooltip :id="task.id + 'user' + user.id" :user="user" height="8" width="8" :classes="index > 0 ? '!ring-1 !ring-white' : ''"/>
                    </span>
                </div>
                <BaseMenu class="ml-3 hidden group-hover:block" v-if="canEditComponent && (projectCanWriteIds?.includes($page.props.user.id) || projectManagerIds?.includes($page.props.user.id) || isAdmin)">
                    <MenuItem v-slot="{ active }">
                        <div @click="openEditTaskModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconEdit stroke-width="1.5"
                                      class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                      aria-hidden="true"/>
                            {{ $t('Edit') }}
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a @click="openDeleteTaskModal = true"
                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                            <IconTrash stroke-width="1.5"
                                       class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                       aria-hidden="true"/>
                            {{ $t('Delete') }}
                        </a>
                    </MenuItem>
                </BaseMenu>
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

import draggable from "vuedraggable";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {IconEdit, IconTrash} from "@tabler/icons-vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import AddEditTaskModal from "@/Components/Checklist/Modals/AddEditTaskModal.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

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
    });
}


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