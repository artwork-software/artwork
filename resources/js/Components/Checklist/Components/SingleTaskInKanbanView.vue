<template>
    <article
        class="group my-3 rounded-xl border border-zinc-200 bg-white p-3 transition hover:shadow-xs focus-within:ring-2 focus-within:ring-blue-600 print:border print:shadow-none"
        draggable="true"
        @dragstart="onDragStart"
        @dragend="onDragEnd"
    >
        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <div class="flex flex-1 items-start gap-3">
                <!-- Checkbox -->
                <div class="pt-0.5">
                    <input
                        :id="checkboxId"
                        @change="updateTaskStatus"
                        v-model="localTaskDone"
                        type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded-full border-2 border-zinc-300 text-emerald-600 ring-0 focus:ring-0 focus:outline-none"
                        :aria-labelledby="titleId"
                    />
                </div>

                <!-- Title + Description -->
                <div class="min-w-0 flex-1">
                    <div :id="titleId" class="mb-1  font-semibold text-sm text-zinc-900" :class="localTaskDone ? 'text-zinc-400 line-through' : ''">
                        {{ task.name }}
                    </div>
                    <p class="xxsLight mt-0.5 break-words text-zinc-600" :class="localTaskDone ? 'line-through text-zinc-400' : ''">
                        {{ task.description }}
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <BaseMenu white-menu-background v-if="canManage" class="ml-1">
                <BaseMenuItem white-menu-background icon="IconEdit" title="Edit" @click="openEditTaskModal = true" />
                <BaseMenuItem white-menu-background icon="IconTrash" title="Delete" @click="openDeleteTaskModal = true" />
            </BaseMenu>
        </div>

        <!-- Footer: Avatars + Dates -->
        <div class="mt-4 grid grid-cols-1 gap-2 md:grid-cols-2">
            <!-- Assignees -->
            <div class="flex items-center">
                <ul class="flex -space-x-3">
                    <li v-for="(user, index) in task.users" :key="user.id">
                        <UserPopoverTooltip
                            :id="`${task.id}-user-${user.id}`"
                            :user="user"
                            height="8"
                            width="8"
                            :classes="index > 0 ? '!ring-1 !ring-white' : ''"
                        />
                    </li>
                </ul>
            </div>

            <!-- Dates -->
            <div class="text-right">
                <div class="flex items-center justify-end gap-2 text-xs">
                    <!-- Open / deadline -->
                    <template v-if="!localTaskDone">
                        <IconCalendar class="h-6 w-6" :class="isOverdue ? 'text-rose-500' : 'text-zinc-400'" />
                        <span
                            class="rounded-md px-1.5 py-0.5 text-sm"
                            :class="isOverdue ? 'bg-rose-500 text-white' : 'bg-zinc-100 text-zinc-700'"
                        >
                          {{ task.formatted_dates?.deadline }}
                        </span>
                    </template>

                    <!-- Done / done_at -->
                    <template v-else>
                        <div class="flex flex-wrap items-center">
                            <div class="flex">
                            <UserPopoverTooltip
                                v-if="task.done_by_user"
                                :user="task.done_by_user"
                                :id="`${task.id}-doneby`"
                                height="6"
                                width="6"
                            />
                            <span class="text-sm text-zinc-600">{{ task.formatted_dates?.done_at }}</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <AddEditTaskModal
            v-if="openEditTaskModal"
            :project="project"
            :tab_id="tab_id"
            :checklist="checklist"
            :task-to-edit="task"
            :is-private="checklist?.private"
            @closed="openEditTaskModal = false"
        />

        <ConfirmDeleteModal
            v-if="openDeleteTaskModal"
            :title="$t('Delete task')"
            :description="$t('Are you sure you want to delete this task?')"
            @closed="openDeleteTaskModal = false"
            @delete="deleteTask"
        />
    </article>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AddEditTaskModal from '@/Components/Checklist/Modals/AddEditTaskModal.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import { IconEdit, IconTrash, IconCalendar } from '@tabler/icons-vue'
import { MenuItem } from '@headlessui/vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import { EventListenerForDragging } from '@/Composeables/EventListenerForDragging.js'
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging()

// Props
interface User { id: number }
interface DatesFmt { deadline?: string; done_at?: string }
interface Task {
    id: number | string
    name: string
    description?: string
    users: User[]
    done: boolean
    done_by_user?: User | null
    deadline_dt_local?: string | null
    formatted_dates?: DatesFmt
}

const props = defineProps<{
    task: Task
    canEditComponent?: boolean
    projectCanWriteIds?: Array<number>
    projectManagerIds?: Array<number>
    isAdmin?: boolean
    project?: Record<string, any> | null
    tab_id?: number
    checklist?: Record<string, any> | null
    isInOwnTaskManagement?: boolean
}>()

// Local state derived from props to keep checkbox snappy
const localTaskDone = ref<boolean>(!!props.task.done)

watch(
    () => props.task.done,
    (isDone) => {
        localTaskDone.value = !!isDone
    }
)

// Ids for a11y
const titleId = `task-title-${props.task.id}`
const checkboxId = `task-checkbox-${props.task.id}`

// Auth user id
const page = usePage()
const authId = computed<number | null>(() => page.props?.auth?.user?.id ?? null)

// Permissions
const canManage = computed(() => {
    const canEdit = !!props.canEditComponent
    const isWriter = props.projectCanWriteIds?.includes(authId.value as number)
    const isManager = props.projectManagerIds?.includes(authId.value as number)
    return (canEdit && (isWriter || isManager || !!props.isAdmin)) || !!props.isInOwnTaskManagement
})

// Overdue detection
const isOverdue = computed(() => {
    if (localTaskDone.value) return false
    const ts = props.task?.deadline_dt_local ? Date.parse(props.task.deadline_dt_local) : NaN
    return Number.isFinite(ts) && ts < Date.now()
})

// Actions
const updateTaskStatus = () => {
    // Keep UI responsive; server is source of truth
    router.patch(
        route('tasks.done', { task: props.task.id }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                // rollback UI on error
                localTaskDone.value = !!props.task.done
            },
        }
    )
}

const onDragStart = (event: DragEvent) => {
    try {
        event.dataTransfer?.setData('task', JSON.stringify(props.task))
    } catch {}
    dispatchEventStart()
}

const onDragEnd = () => {
    dispatchEventEnd()
}

const openEditTaskModal = ref(false)
const openDeleteTaskModal = ref(false)

const deleteTask = () => {
    router.delete(route('tasks.destroy', { task: props.task.id }), {
        preserveScroll: true,
        onSuccess: () => {
            openDeleteTaskModal.value = false
        },
    })
}
</script>

<style scoped>
/***** Optional: subtle group focus ring for keyboard users *****/
:host, article:focus-within { outline: none; }
</style>
