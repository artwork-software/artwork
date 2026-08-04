<template>
    <ArtworkBaseModal
        @close="$emit('closed', true)"
        :title="$t('Edit checklist template')"
        :description="$t('Change the name and the user assignment of this checklist template.')"
    >
        <form @submit.prevent="submit">
            <div class="mt-6 grid grid-cols-1 gap-4">
                <BaseInput
                    id="checklistTemplateName"
                    v-model="form.name"
                    :label="$t('Name of the checklist template') + '*'"
                    required
                />

                <!-- Users -->
                <div class="bg-surface-sunken rounded-xl p-5">
                    <h3 class="headline4">{{ $t('Checklist users') }}</h3>
                    <AlertComponent
                        class="mt-3"
                        type="info"
                        text-size="text-sm"
                        :text="$t('The tasks in this checklist are automatically assigned to all users. Users who are not in the project are added automatically.')"
                    />
                    <div class="mt-4">
                        <UserSearch @user-selected="addUser" />
                    </div>
                    <div v-if="form.users.length === 0" class="mt-4 text-secondary text-sm">
                        {{ $t('No users added yet') }}
                    </div>
                    <div v-else class="mt-4 grid grid-cols-1 gap-3">
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
                </div>

                <div class="w-full flex items-center justify-end text-center">
                    <BaseUIButton
                        type="submit"
                        class="mt-4"
                        :disabled="form.name === '' || form.processing"
                        :label="$t('Save')"
                        is-add-button
                    />
                </div>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { XIcon } from '@heroicons/vue/outline'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import AlertComponent from '@/Components/Alerts/AlertComponent.vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'

const props = defineProps({
    checklistTemplate: {
        type: Object,
        required: true
    }
})

const emits = defineEmits(['closed'])

const form = useForm({
    name: props.checklistTemplate.name,
    users: [...(props.checklistTemplate.users ?? [])]
})

const addUser = (user) => {
    if (!form.users.some((u) => u.id === user.id)) {
        form.users.push(user)
    }
}

const removeUser = (user) => {
    form.users = form.users.filter((u) => u.id !== user.id)
}

const submit = () => {
    form.patch(route('checklist_templates.update', { checklist_template: props.checklistTemplate.id }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => emits('closed')
    })
}
</script>
