<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader :title="$t('Create new Chat')" :description="$t('Select users to start a chat. When multiple users are selected, a group chat will be created and you will automatically be included as a member.')" />

        </div>

        <div class="">
            <UserSearch
                @userSelected="addUserToChat"
                :label="$t('Add users')"
                :only-use-chat-users="true"
                :without-self="true"
            />
        </div>

        <div>
            <div v-if="chatUsers.length > 0" class="mt-3 mb-4 flex items-center flex-wrap gap-3 ">
                <div v-for="(user, index) in chatUsers" class="group block shrink-0 bg-gray-50 w-fit pr-3 rounded-full border border-gray-300">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block size-9 rounded-full object-cover" :src="user?.profile_photo_url" alt="" />
                        </div>
                        <div class="mx-2">
                            <p class="xsDark group-hover:text-gray-900">{{ user.name }}</p>
                        </div>
                        <div class="flex items-center">
                            <button type="button" @click="deleteUserFromForm(index)">
                                <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="chatUsers.length > 1">
            <div class="rounded-md bg-yellow-50 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <component is="IconInfoSquareRoundedFilled" class="size-5 min-w-5 min-h-5 text-yellow-400" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-800">
                            {{ $t('You have selected multiple users. This will create a group chat. You will automatically be included as a member. Please provide a name for the group.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <TextInputComponent
                    id="chatName" v-model="chatName" label="Name"
                />
            </div>

        </div>

        <div>
            <div class="flex justify-end mt-4">
                <FormButton type="submit" @click="createChat" :text="$t('Create')" class="w-full sm:w-auto" />
            </div>
        </div>




    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import {XIcon} from "@heroicons/vue/outline";
import Button from "@/Jetstream/Button.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {ref} from "vue";
const props = defineProps({

})

const emit = defineEmits(['close'])

const chatName = ref('')
const chatUsers = ref([])

const addUserToChat = (user) => {
    if (!chatUsers.value.some(u => u.id === user.id)) {
        chatUsers.value.push(user);
    }
}

const deleteUserFromForm = (index) => {
    chatUsers.value.splice(index, 1);
}

const createChat = async () => {
    try {
        const response = await fetch("/api/chat/store", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                name: chatName.value,
                users: chatUsers.value.map(user => user.id),
            }),
        })

        const contentType = response.headers.get('Content-Type')
        const isJson = contentType && contentType.includes('application/json')
        const data = isJson ? await response.json() : null
        if (!response.ok || !data?.chat) {
            return
        }
        emit('close', data.chat.id)

    } catch (err) {
        console.error('Fehler bei der Anfrage:', err)
    }
}
</script>

<style scoped>

</style>
