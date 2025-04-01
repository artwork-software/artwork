<template>
    <div class="flex items-center cursor-pointer" :key="chat.id">
        <div class="mr-4 shrink-0">
            <img class="size-14 min-h-14 min-w-14 rounded-full object-cover" v-if="!props.chat.is_group" :src="getUserWhereAreNotMe?.profile_photo_url" alt="Jese image">
            <component v-else is="IconUsersGroup" class="size-14 min-h-15 min-w-14 rounded-full object-cover p-3 bg-blue-50 text-blue-900 border border-blue-100" />
        </div>
        <div>
            <h4 class="font-lexend text-sm font-bold">{{ chat.is_group ? chat.name : getUserWhereAreNotMe?.full_name }}</h4>
            <div class="mt-1 font-lexend text-gray-500 text-xs">
                <div class="font-bold text-gray-900 flex items-center gap-x-1" v-if="chat.last_message">
                    <img class="size-4 rounded-full object-cover" :src="chat.last_message.sender?.profile_photo_url" alt="Jese image">
                    <span>
                        {{ chat.last_message.sender_id === usePage().props.auth.user.id ? $t('You') : chat.last_message.sender?.full_name }}:
                    </span>
                    <span class="text-xs text-gray-500 font-normal truncate">
                        {{ lastMessageText }}
                    </span>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>

import {computed, ref, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import useCrypto from "@/Composeables/useCrypto.js";

const {
    publicKey,
    privateKey,
    hasKeypair,
    generateKeypair,
    encrypt,
    decrypt,
    clearKeys,
} = useCrypto()

const props = defineProps({
    chat: {
        type: Object,
        required: true
    }
})

const getUserWhereAreNotMe = computed(() => {
    if (props.chat.is_group) {
        return {
            profile_photo_url: '/storage/profile-photos/users-group.png',
        }
    }

    return props.chat.users.find(user => user.id !== usePage().props.auth.user.id)
})

const lastMessageText = ref('')

watchEffect(async () => {
    if (!props.chat.last_message) return 'no messages yet'

    if (props.chat.last_message.sender_id === usePage().props.auth.user.id && hasKeypair.value) {
        lastMessageText.value = await decrypt(props.chat.last_message.cipher_for_sender)
    } else {
        lastMessageText.value = await decrypt(props.chat.last_message.ciphers_json[usePage().props.auth.user.id])
    }
})

</script>

<style scoped>

</style>