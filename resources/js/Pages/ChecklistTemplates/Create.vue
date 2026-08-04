<template>
    <ChecklistTemplatesHeader
        :title="$t('New checklist template')"
        :description="$t('You can create and edit your checklist template here - it can then be used in any project.')"
    >
        <template #actions>
            <Link :href="route('checklist_templates.management')" class="ui-button">
                {{ $t('Back') }}
            </Link>
        </template>

        <div class="my-8 max-w-3xl">
            <!-- Name -->
            <BaseInput
                id="checklistTemplateName"
                v-model="form.name"
                :label="$t('Name of the checklist template') + '*'"
                required
            />

            <!-- Users -->
            <div class="mt-8 bg-surface-sunken rounded-xl p-5">
                <div class="flex items-center justify-between">
                    <h3 class="headline4">{{ $t('Checklist users') }}</h3>
                    <BaseUIButton
                        :label="$t('Assign users')"
                        @click="showUsersModal = true"
                    />
                </div>
                <AlertComponent
                    class="mt-3"
                    type="info"
                    text-size="text-sm"
                    :text="$t('The tasks in this checklist are automatically assigned to all users. Users who are not in the project are added automatically.')"
                />
                <div class="mt-4 flex items-center">
                    <span v-if="form.users.length === 0" class="text-secondary text-sm">
                        {{ $t('No users added yet') }}
                    </span>
                    <div v-else class="flex -space-x-2">
                        <UserPopoverTooltip
                            v-for="(user, index) in form.users"
                            :key="user.id"
                            :user="user"
                            height="10"
                            width="10"
                            :classes="index > 0 ? '!ring-2 ring-white' : ''"
                        />
                    </div>
                </div>
            </div>

            <!-- Tasks -->
            <div class="mt-10">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="headline4">{{ $t('Tasks') }}</h3>
                    <BaseUIButton
                        is-add-button
                        :label="$t('New task')"
                        @click="openAddTaskModal = true"
                    />
                </div>

                <div v-if="form.task_templates.length === 0" class="text-center text-sm text-text-subtle py-6 border border-dashed border-border-subtle rounded-xl">
                    {{ $t('No tasks added yet') }}
                </div>
                <draggable
                    v-else
                    v-model="form.task_templates"
                    item-key="_tmpId"
                    handle=".drag-handle"
                    ghost-class="opacity-50"
                >
                    <template #item="{ element, index }">
                        <SingleTaskTemplateInListView
                            local
                            :task="element"
                            @update="updateTask(index, $event)"
                            @delete="removeTask(index)"
                        />
                    </template>
                </draggable>
            </div>

            <!-- Save -->
            <div class="mt-10">
                <BaseUIButton
                    is-add-button
                    :label="$t('Create template')"
                    :disabled="form.name === '' || form.processing"
                    @click="createTemplate"
                />
            </div>
        </div>

        <!-- Add task modal (local) -->
        <AddEditTaskTemplateModal
            v-if="openAddTaskModal"
            local
            @closed="openAddTaskModal = false"
            @save="addTask"
        />

        <!-- Users modal -->
        <ArtworkBaseModal
            v-if="showUsersModal"
            :title="$t('Assign checklist template')"
            :description="$t('Type the name of the user to whom you want to assign the checklist template.')"
            @close="showUsersModal = false"
        >
            <div class="mt-6">
                <UserSearch @user-selected="addUser" />
                <div v-if="form.users.length > 0" class="mt-6 bg-surface-sunken rounded-xl p-4 grid grid-cols-1 gap-3">
                    <div
                        v-for="user in form.users"
                        :key="user.id"
                        class="flex items-center justify-between"
                    >
                        <div class="flex items-center gap-x-3">
                            <img class="h-9 w-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                            <span class="sDark">{{ user.first_name }} {{ user.last_name }}</span>
                        </div>
                        <button type="button" @click="removeUser(user)">
                            <span class="sr-only">{{ $t('Remove user from checklist template') }}</span>
                            <XIcon class="h-4 w-4 p-0.5 rounded-full bg-artwork-buttons-create text-white hover:bg-artwork-buttons-hover" />
                        </button>
                    </div>
                </div>
                <div class="mt-8 flex items-center justify-end">
                    <BaseUIButton :label="$t('Done')" @click="showUsersModal = false" />
                </div>
            </div>
        </ArtworkBaseModal>
    </ChecklistTemplatesHeader>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { XIcon } from '@heroicons/vue/outline'
import draggable from 'vuedraggable'
import ChecklistTemplatesHeader from '@/Pages/ChecklistTemplates/Components/ChecklistTemplatesHeader.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import AlertComponent from '@/Components/Alerts/AlertComponent.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import SingleTaskTemplateInListView from '@/Pages/ChecklistTemplates/Components/SingleTaskTemplateInListView.vue'
import AddEditTaskTemplateModal from '@/Pages/ChecklistTemplates/Components/AddEditTaskTemplateModal.vue'

const openAddTaskModal = ref(false)
const showUsersModal = ref(false)
let tmpIdCounter = 0

const form = useForm({
    name: '',
    user_id: usePage().props.auth.user.id,
    task_templates: [],
    users: []
})

const addTask = (task) => {
    form.task_templates.push({ ...task, _tmpId: ++tmpIdCounter })
}

const updateTask = (index, task) => {
    form.task_templates.splice(index, 1, task)
}

const removeTask = (index) => {
    form.task_templates.splice(index, 1)
}

const addUser = (user) => {
    if (!form.users.some((u) => u.id === user.id)) {
        form.users.push(user)
    }
}

const removeUser = (user) => {
    form.users = form.users.filter((u) => u.id !== user.id)
}

const createTemplate = () => {
    if (form.name === '') {
        return
    }

    form
        .transform((data) => ({
            ...data,
            task_templates: data.task_templates.map((task, index) => ({
                name: task.name,
                description: task.description,
                deadline_days_after_creation: task.deadline_days_after_creation ?? null,
                order: index
            }))
        }))
        .post(route('checklist_templates.store'))
}
</script>
