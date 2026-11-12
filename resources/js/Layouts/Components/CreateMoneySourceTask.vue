<template>
    <ArtworkBaseModal
        title="New task"
        description="Create a new task. You can also add a deadline and a comment."
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <form id="createMoneySourceTaskForm" class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="createTask">
            <BaseInput
                id="moneySourceTaskName"
                v-model="task.name"
                class="col-span-full"
                label="Title"
                required
                :error="task.errors.name"
                autofocus
            />
            <BaseInput
                id="moneySourceTaskEndDate"
                v-model="task.end_date"
                type="date"
                label="To be completed by?"
                required
                :error="task.errors.deadline"
            />
            <BaseInput
                id="moneySourceTaskEndTime"
                v-model="task.end_time"
                type="time"
                label="Time"
                required
            />
            <BaseTextarea
                id="moneySourceTaskDescription"
                v-model="task.description"
                class="col-span-full"
                label="Comment"
                :rows="4"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="task.processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="createMoneySourceTaskForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="task.processing"
                :disabled="task.processing || task.name.trim() === ''"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {useForm} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'CreateMoneySourceTask'})

const props = defineProps({
    money_source_id: {type: Number, required: true},
})

const emit = defineEmits(['closed'])

const task = useForm({
    name: '',
    description: '',
    end_date: null,
    end_time: null,
    // Ohne Auswahl ordnet das Backend die Zuständigen der Quelle zu
    users: [],
    money_source: props.money_source_id,
    deadline: null,
})

function closeModal(saved) {
    emit('closed', saved)
}

function createTask() {
    if (task.processing || task.name.trim() === '') {
        return
    }
    task
        .transform((data) => ({
            ...data,
            deadline: data.end_date ? `${data.end_date} ${data.end_time || '00:00'}` : null,
        }))
        .post(route('money_source.task.add'), {
            preserveScroll: true,
            onSuccess: () => closeModal(true),
        })
}
</script>
