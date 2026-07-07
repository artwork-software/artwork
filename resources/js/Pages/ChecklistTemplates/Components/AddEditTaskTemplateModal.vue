<template>
    <ArtworkBaseModal
        @close="$emit('closed', true)"
        :title="taskToEdit ? $t('Edit task') : $t('New task')"
        :description="taskToEdit ? '' : $t('Create a new task for this checklist template.')"
    >
        <form @submit.prevent="submit">
            <div class="mt-6 grid grid-cols-1 gap-4">
                <BaseInput
                    id="taskTemplateName"
                    v-model="form.name"
                    :label="$t('Task') + '*'"
                    required
                />
                <BaseTextarea
                    id="taskTemplateDescription"
                    v-model="form.description"
                    :label="$t('Comment')"
                    :rows="3"
                />
                <div>
                    <BaseInput
                        id="taskTemplateDeadlineDays"
                        v-model="form.deadline_days_after_creation"
                        type="number"
                        min="0"
                        :label="$t('Deadline: days after checklist creation')"
                    />
                    <p class="xxsLight mt-1 text-secondary">
                        {{ $t('Leave empty for no deadline. The deadline is set automatically when a checklist is created from this template.') }}
                    </p>
                </div>
                <div class="w-full flex items-center justify-end text-center">
                    <BaseUIButton
                        type="submit"
                        class="mt-4"
                        :disabled="form.name === '' || form.processing"
                        :label="taskToEdit ? $t('Save') : $t('Add')"
                        is-add-button
                    />
                </div>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    // When set, the modal persists the task via the task_templates routes.
    checklistTemplateId: {
        type: Number,
        required: false,
        default: null
    },
    taskToEdit: {
        type: Object,
        required: false
    },
    // Local mode: do not call the backend, emit the task object instead (used while creating a template).
    local: {
        type: Boolean,
        required: false,
        default: false
    }
})

const emits = defineEmits(['closed', 'save'])

const initial = () => ({
    name: props.taskToEdit ? props.taskToEdit.name : '',
    description: props.taskToEdit ? props.taskToEdit.description : '',
    deadline_days_after_creation: props.taskToEdit && props.taskToEdit.deadline_days_after_creation !== undefined
        ? props.taskToEdit.deadline_days_after_creation
        : null
})

// Local mode uses a plain reactive object, persisted mode an Inertia form.
const form = props.local
    ? reactive(initial())
    : useForm({ ...initial(), checklist_template_id: props.checklistTemplateId })

const normalizedDays = () => {
    const value = form.deadline_days_after_creation
    if (value === '' || value === null || value === undefined) {
        return null
    }
    const parsed = parseInt(value, 10)
    return Number.isNaN(parsed) ? null : parsed
}

const submit = () => {
    if (props.local) {
        emits('save', {
            name: form.name,
            description: form.description,
            deadline_days_after_creation: normalizedDays()
        })
        emits('closed')
        return
    }

    form.deadline_days_after_creation = normalizedDays()

    if (props.taskToEdit) {
        form.patch(route('task_templates.update', { task_template: props.taskToEdit.id }), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => emits('closed')
        })
    } else {
        form.post(route('task_templates.store'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => emits('closed')
        })
    }
}
</script>
