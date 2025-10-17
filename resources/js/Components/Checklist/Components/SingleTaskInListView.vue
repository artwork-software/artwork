<template>
    <article
        :id="String(task.id)"
        class="group my-3 cursor-grab rounded-xl border border-zinc-200 bg-white p-3 transition hover:shadow-xs focus-within:ring-2 focus-within:ring-blue-600 print:border print:shadow-none"
        draggable="true"
        @dragstart="onDragStart"
        @dragend="onDragEnd"
    >
        <div class="flex items-start justify-between gap-4">
            <!-- Left: checkbox + title/desc -->
            <div class="flex min-w-0 flex-1 items-start gap-3">
                <div class="pt-0.5">
                    <input
                        :id="checkboxId"
                        name="task"
                        type="checkbox"
                        v-model="localTaskDone"
                        @change="updateTaskStatus"
                        class="h-5 w-5 cursor-pointer rounded-full border-2 border-zinc-300 text-emerald-600 ring-0 focus:ring-0 focus:outline-none"
                        :aria-labelledby="titleId"
                    />
                </div>
                <div class="min-w-0">
                    <h3 :id="titleId" class="xsDark mb-0.5 break-words" :class="localTaskDone ? 'text-zinc-400 line-through' : 'text-zinc-900'">
                        {{ task.name }}
                    </h3>
                    <p class="xxsLight break-words" :class="localTaskDone ? 'text-zinc-400 line-through' : 'text-zinc-600'">
                        {{ task.description }}
                    </p>
                </div>
            </div>

            <!-- Middle: dates / done info -->
            <div class="hidden shrink-0 md:flex md:min-w-[14rem] md:items-center md:justify-end">
                <template v-if="!localTaskDone">
                    <IconCalendar class="h-4 w-4 mr-2" :class="isOverdue ? 'text-rose-500' : 'text-zinc-400'" />
                    <span
                        class="text-[10px] rounded-md px-1.5 py-0.5"
                        :class="isOverdue ? 'bg-rose-500 text-white' : 'bg-zinc-100 text-zinc-700'"
                    >
            {{ task.formatted_dates?.deadline }}
          </span>
                </template>
                <template v-else>
          <span class="flex items-center text-xs text-zinc-600">
            <span class="mr-2">
              <UserPopoverTooltip
                  v-if="task.done_by_user"
                  :user="task.done_by_user"
                  :id="`${task.id}-doneby`"
                  height="6"
                  width="6"
              />
            </span>
            {{ task.formatted_dates?.done_at_with_day }}
          </span>
                </template>
            </div>

            <!-- Right: assignees + actions -->
            <div class="flex shrink-0 items-center gap-3">
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
                <BaseMenu white-menu-background v-if="canManage" class="ml-1">
                    <BaseMenuItem white-menu-background icon="IconEdit" title="Edit" @click="openEditTaskModal = true" />
                    <BaseMenuItem white-menu-background icon="IconTrash" title="Delete" @click="openDeleteTaskModal = true" />
                </BaseMenu>
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
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import { IconCalendar, IconEdit, IconTrash } from '@tabler/icons-vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import { MenuItem } from '@headlessui/vue'
import AddEditTaskModal from '@/Components/Checklist/Modals/AddEditTaskModal.vue'
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import { EventListenerForDragging } from '@/Composeables/EventListenerForDragging.js'
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging()

interface User { id: number }
interface DatesFmt { deadline?: string; done_at_with_day?: string }
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

const openEditTaskModal = ref(false)
const openDeleteTaskModal = ref(false)

// local mirror for snappy checkbox UX
const localTaskDone = ref<boolean>(!!props.task.done)

const page = usePage()
const authId = computed<number | null>(() => page.props?.auth?.user?.id ?? null)

const canManage = computed(() => {
    const canEdit = !!props.canEditComponent
    const isWriter = props.projectCanWriteIds?.includes(authId.value as number)
    const isManager = props.projectManagerIds?.includes(authId.value as number)
    return (canEdit && (isWriter || isManager || !!props.isAdmin)) || !!props.isInOwnTaskManagement
})

const titleId = `task-title-${props.task.id}`
const checkboxId = `task-checkbox-${props.task.id}`

const isOverdue = computed(() => {
    if (localTaskDone.value) return false
    const ts = props.task?.deadline_dt_local ? Date.parse(props.task.deadline_dt_local) : NaN
    return Number.isFinite(ts) && ts < Date.now()
})

const updateTaskStatus = () => {
    router.patch(
        route('tasks.done', { task: props.task.id }),
        {},
        {
            preserveScroll: true,
            preserveState: false,
            onError: () => { localTaskDone.value = !!props.task.done },
            onSuccess: () => { localTaskDone.value = !!props.task.done },
        }
    )
}

const onDragStart = (event: DragEvent) => {
    try { event.dataTransfer?.setData('task', JSON.stringify(props.task)) } catch {}
    // Behalte dein bestehendes API-Design bei
    try { /* @ts-ignore */ dispatchEventStart?.(props.task) } catch {}
}

const onDragEnd = () => {
    dispatchEventEnd()
}

const deleteTask = () => {
    router.delete(route('tasks.destroy', { task: props.task.id }), {
        preserveScroll: true,
        onSuccess: () => { openDeleteTaskModal.value = false },
    })
}
</script>

<style scoped>
:host, article:focus-within { outline: none; }
</style>
