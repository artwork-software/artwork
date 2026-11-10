<template>
    <ArtworkBaseModal
        :title="taskToEdit ? 'Edit task' : 'New task'"
        :description="taskToEdit ? '' : 'Create a new task. You can also add a deadline and a comment.'"
        modal-size="sm:max-w-xl"
        @close="closeModal"
    >
        <form id="addEditTaskForm" class="space-y-4" @submit.prevent="submit">
            <BaseInput
                id="taskName"
                v-model="taskForm.name"
                label="Task"
                required
                :error="taskForm.errors.name"
                autofocus
            />
            <BaseInput
                id="deadlineDate"
                v-model="taskForm.deadlineDate"
                label="To be done until?"
                type="date"
                :error="taskForm.errors.deadlineDate"
            />
            <div v-if="!checklist?.private" class="space-y-2">
                <UserSearch
                    :only-team="isInOwnTaskManagement"
                    :team-member="teamMemberIds"
                    label="Search for users"
                    @user-selected="addUserToTask"
                />
                <ul
                    v-if="usersToAdd.length > 0"
                    class="max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                >
                    <li
                        v-for="user in usersToAdd"
                        :key="user.id"
                        class="flex items-center justify-between gap-x-3 px-3 py-2"
                    >
                        <div class="flex min-w-0 items-center gap-x-3">
                            <img
                                class="size-8 shrink-0 rounded-full object-cover"
                                :src="user.profile_photo_url"
                                alt=""
                            />
                            <span class="truncate text-sm font-medium text-text">
                                {{ user?.first_name }} {{ user?.last_name }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                            :aria-label="$t('Remove user from the task')"
                            @click="deleteUserFromTask(user)"
                        >
                            <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                        </button>
                    </li>
                </ul>
            </div>
            <BaseTextarea
                id="taskDescription"
                v-model="taskForm.description"
                label="Comment"
                :rows="3"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="taskForm.processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="addEditTaskForm"
                :label="taskToEdit ? 'Save' : 'Add'"
                variant="primary"
                :icon="taskToEdit ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="taskForm.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

defineOptions({name: 'AddEditTaskModal'})

const props = defineProps({
    // Bei Aufgaben in eigenen To-do-Listen ohne Projekt kommt hier null an
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
    taskToEdit: {
        type: Object,
        required: false
    },
    isPrivate: {
        type: Boolean,
        required: false,
        default: false
    },
    isInOwnTaskManagement: {
        type: Boolean,
        required: false,
        default: false
    }
})

const taskForm = useForm({
    id: props.taskToEdit ? props.taskToEdit.id : null,
    name: props.taskToEdit ? props.taskToEdit.name : '',
    description: props.taskToEdit ? props.taskToEdit.description : '',
    deadlineDate: props.taskToEdit ? props.taskToEdit.deadlineDate : null,
    deadlineTime: props.taskToEdit ? props.taskToEdit.deadline_dt_local : null,
    private: props.taskToEdit ? props.taskToEdit.private : false,
    users: [],
    checklist_id: props.checklist ? props.checklist.id : null,
    tab_id: props.tab_id ? props.tab_id : null
})

const emits = defineEmits([
    'closed'
])

// Kopie, damit die Personenliste der Aufgabe in der Elternansicht nicht vor dem Speichern mitverändert wird
const usersToAdd = ref(props.taskToEdit?.users ? [...props.taskToEdit.users] : []);

const teamMemberIds = computed(() => (
    props.project?.users?.map((user) => user.id)
    ?? props.checklist?.project?.users?.map((user) => user.id)
));

const canSubmit = computed(() => Boolean(taskForm.name?.trim()) && !taskForm.processing);

const closeModal = () => {
    if (taskForm.processing) {
        return;
    }
    emits('closed', true);
}

const addUserToTask = (user) => {
    if (!usersToAdd.value.some((addedUser) => addedUser.id === user.id)) {
        usersToAdd.value.push(user);
    }
}

const deleteUserFromTask = (user) => {
    usersToAdd.value = usersToAdd.value.filter((addedUser) => addedUser.id !== user.id);
}

const submit = () => {
    if (!canSubmit.value) {
        return;
    }
    // Neu setzen statt anhängen, sonst doppeln sich die IDs bei erneutem Absenden nach einem Fehler
    taskForm.users = usersToAdd.value.map((user) => user.id);
    const options = {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            taskForm.reset();
            usersToAdd.value = [];
            emits('closed');
        }
    };
    if (props.taskToEdit) {
        taskForm.patch(route('tasks.update', {task: props.taskToEdit.id}), options);
    } else {
        taskForm.post(route('tasks.store'), options);
    }
}
</script>
