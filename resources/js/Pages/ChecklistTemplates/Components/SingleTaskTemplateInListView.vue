<template>
    <article
        class="group my-3 rounded-xl border border-zinc-200 bg-white p-3 transition hover:shadow-xs focus-within:ring-2 focus-within:ring-blue-600 print:border print:shadow-none"
    >
        <div class="flex items-start justify-between gap-4">
            <!-- Left: drag handle + title/desc -->
            <div class="flex min-w-0 flex-1 items-start gap-3">
                <div class="drag-handle cursor-grab pt-0.5 text-zinc-300 hover:text-zinc-500 transition" :title="$t('Drag to reorder')">
                    <IconGripVertical class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <h3 class="xsDark mb-0.5 break-words text-zinc-900">
                        {{ task.name }}
                    </h3>
                    <p v-if="task.description" class="xxsLight break-words text-zinc-600">
                        {{ task.description }}
                    </p>
                </div>
            </div>

            <!-- Right: deadline info + actions -->
            <div class="flex shrink-0 items-center gap-3">
                <span
                    v-if="hasDeadlineDays"
                    class="flex items-center gap-x-1 rounded-md bg-zinc-100 px-1.5 py-0.5 text-[12px] text-zinc-600"
                    :title="$t('Deadline: days after checklist creation')"
                >
                    <IconCalendarClock class="h-4 w-4 text-zinc-400" />
                    {{ $t('in {0} days', [task.deadline_days_after_creation]) }}
                </span>
                <BaseMenu white-menu-background class="ml-1">
                    <BaseMenuItem white-menu-background icon="IconEdit" title="Edit" @click="openEditTaskModal = true" />
                    <BaseMenuItem white-menu-background icon="IconTrash" title="Delete" @click="openDeleteTaskModal = true" />
                </BaseMenu>
            </div>
        </div>

        <!-- Modals -->
        <AddEditTaskTemplateModal
            v-if="openEditTaskModal"
            :local="local"
            :checklist-template-id="checklistTemplateId"
            :task-to-edit="task"
            @closed="openEditTaskModal = false"
            @save="onLocalSave"
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

<script setup>
import { IconGripVertical, IconCalendarClock } from '@tabler/icons-vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import AddEditTaskTemplateModal from '@/Pages/ChecklistTemplates/Components/AddEditTaskTemplateModal.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    task: {
        type: Object,
        required: true
    },
    checklistTemplateId: {
        type: Number,
        required: false,
        default: null
    },
    // Local mode: edit/delete emit events instead of calling the backend (used while creating a template).
    local: {
        type: Boolean,
        required: false,
        default: false
    }
})

const emits = defineEmits(['update', 'delete'])

const openEditTaskModal = ref(false)
const openDeleteTaskModal = ref(false)

const hasDeadlineDays = computed(() =>
    props.task.deadline_days_after_creation !== null && props.task.deadline_days_after_creation !== undefined
)

const onLocalSave = (data) => {
    emits('update', { ...props.task, ...data })
}

const deleteTask = () => {
    if (props.local) {
        emits('delete')
        openDeleteTaskModal.value = false
        return
    }
    router.delete(route('task_templates.destroy', { task_template: props.task.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => { openDeleteTaskModal.value = false }
    })
}
</script>
