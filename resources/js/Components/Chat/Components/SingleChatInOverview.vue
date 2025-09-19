<template>
    <div class="flex items-center cursor-pointer w-full" :key="chat.id">
        <div class="mr-4 shrink-0">
            <img
                v-if="!props.chat.is_group"
                class="size-10 rounded-full object-cover"
                :src="getUserWhereAreNotMe?.profile_photo_url"
                alt="Profilbild"
            >
            <component
                v-else
                :is="IconUsersGroup"
                class="size-10 rounded-full object-cover p-3 bg-blue-50 text-blue-900 border border-blue-100"
            />
        </div>

        <!-- WICHTIG: flex-1 + min-w-0 erlaubt korrektes Abschneiden -->
        <div class="flex-1 min-w-0">
            <h4 class="text-xs font-semibold truncate">
                {{ chat.is_group ? chat.name : getUserWhereAreNotMe?.full_name }}
            </h4>

            <div class="mt-1 text-gray-500 text-xs">
                <!-- overflow-hidden verhindert horizontales Scrollen -->
                <div v-if="chat.last_message" class="text-gray-900 gap-x-1 overflow-hidden">
                    <span class="shrink-0">
                      {{ chat.last_message.sender_id === usePage().props.auth.user.id ? $t('You') : chat.last_message.sender?.full_name }}:
                    </span>

                    <!-- Entweder line-clamp ODER truncate – hier 2 Zeilen ohne Scroll -->
                    <p class="text-xs text-gray-500 font-normal break-words line-clamp-1" v-html="lastMessageText">

                    </p>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>

import {computed, ref, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import useCrypto from "@/Composeables/useCrypto.js";
import {IconUsersGroup} from "@tabler/icons-vue";


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

    if (props.chat.last_message.sender_id === usePage().props.auth.user.id) {
        lastMessageText.value = props.chat.last_message.message
    } else {
        lastMessageText.value = props.chat.last_message.message
    }
})

</script>

<style scoped>

</style>
