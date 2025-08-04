<template>
    <BaseModal @closed="$emit('closed', true)" v-if="true" modal-image="/Svgs/Overlays/illu_task_new.svg">
        <form @submit.prevent="submit" class="mx-4">
            <div class="font-black font-lexend text-primary tracking-wide text-3xl my-2">
                {{ taskToEdit ? $t('Edit task') : $t('New task') }}
            </div>
            <div class="text-secondary tracking-tight leading-6 sub">
                {{ taskToEdit ? '' : $t('Create a new task. You can also add a deadline and a comment.') }}
            </div>
            <div class="mt-6">
                <div class="flex">
                    <div class="mt-1 w-full mr-4">
                        <BaseInput
                            id="taskName"
                            v-model="taskForm.name"
                            :label="$t('Task') + '*'"
                            required
                        />
                    </div>
                </div>
                <div class="flex mt-3 mr-4">
                    <BaseInput
                        id="deadlineDate"
                        v-model="taskForm.deadlineDate"
                        :label="$t('To be done until?')"
                        type="date"
                    />
                </div>
                <div class="mb-2 mr-4 mt-2" v-if="!checklist.private">
                    <UserSearch :only-team="isInOwnTaskManagement" :team-member="project?.users?.map((user) => user.id) ?? checklist?.project?.users?.map((user) => user.id)"
                                @user-selected="addUserToTask"
                    />
                    <div v-if="usersToAdd.length > 0" class="mt-2 mb-4 flex items-center">
                            <span v-for="(user,index) in usersToAdd"
                                  class="flex mr-5 rounded-full items-center font-bold text-primary">
                                <div class="flex items-center">
                                    <img class="flex h-11 w-11 rounded-full object-cover"
                                         :src="user.profile_photo_url"
                                         alt=""/>
                                    <span class="flex ml-4 sDark">
                                    {{ user?.first_name }} {{ user?.last_name }}
                                    </span>
                                    <button type="button" @click="deleteUserFromTask(index)">
                                        <span class="sr-only">{{ $t('Remove user from the task') }}</span>
                                        <XIcon
                                            class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-artwork-buttons-create text-white border-0 "/>
                                    </button>
                                </div>
                            </span>
                    </div>
                </div>
                <div class="mt-4 mr-4">
                        <BaseTextarea
                            id="taskDescription"
                            v-model="taskForm.description"
                            :label="$t('Comment')"
                            :rows="3"
                        />
                </div>
                <div class="w-full flex items-center justify-center text-center">
                    <FormButton
                        type="submit"
                        class="mt-4"
                        :disabled="taskForm.name === ''"
                        :text="taskToEdit ? $t('Save') : $t('Add')"
                    />
                </div>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import {XIcon} from "@heroicons/vue/outline";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import {ref} from "vue";

const props = defineProps({
    project: {
        type: Object,
        required: true
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

const usersToAdd = ref(props.taskToEdit ? props.taskToEdit.users : []);

const addUserToTask = (user) => {
    if (!usersToAdd.value.includes(user)) {
        usersToAdd.value.push(user);
    }
}

const deleteUserFromTask = (index) => {
    usersToAdd.value.splice(index, 1);
}

const submit = () => {
    usersToAdd.value.forEach((user) => {
        taskForm.users.push(user.id);
    })
    if ( props.taskToEdit ) {
        taskForm.patch(route('tasks.update', {task: props.taskToEdit.id}), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                taskForm.reset();
                usersToAdd.value = [];
                emits('closed');
            }
        });
    } else {
        taskForm.post(route('tasks.store'), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                taskForm.reset();
                usersToAdd.value = [];
                emits('closed');
            }
        });
    }
}

</script>

<style scoped>

</style>
