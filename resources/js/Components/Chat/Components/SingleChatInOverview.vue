<template>
    <div
        class="flex items-center w-full gap-3 cursor-pointer select-none"
        :key="chat.id"
        aria-label="Chat row"
    >
        <!-- Avatar -->
        <div class="shrink-0 relative">
            <img
                v-if="!props.chat.is_group"
                class="size-10 rounded-xl object-cover ring-2 ring-white shadow-sm"
                :src="getUserWhereAreNotMe?.profile_photo_url"
                alt="Profilbild"
            />
            <component
                v-else
                :is="IconUsersGroup"
                class="size-10 rounded-xl p-2.5 bg-blue-50 text-blue-900 border border-blue-100 ring-2 ring-white shadow-sm"
                aria-hidden="true"
            />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <!-- Name -->
            <h4
                class="truncate"
                :class="isUnread ? 'text-[13px] font-semibold text-zinc-900' : 'text-[13px] font-medium text-zinc-900'"
            >
                {{ chat.is_group ? chat.name : getUserWhereAreNotMe?.full_name }}
            </h4>

            <!-- Preview -->
            <div class="mt-0.5 text-xs text-zinc-500">
                <div
                    v-if="chat.last_message"
                    class="flex items-center gap-1 overflow-hidden"
                >
                    <!-- Sender-Präfix -->
                    <span
                        class="shrink-0"
                        :class="isUnread ? 'text-zinc-700 font-semibold' : 'text-zinc-600'"
                    >
                        {{ chat.last_message.sender_id === usePage().props.auth.user.id ? $t('You') : (chat.last_message.sender?.full_name || '') }}:
                      </span>

                    <!-- Letzte Nachricht (1 Zeile, sauber abgeschnitten) -->
                    <p
                        class="line-clamp-1 break-words"
                        :class="isUnread ? 'text-zinc-700 font-semibold' : 'text-zinc-500 font-normal'"
                        v-html="lastMessageText"
                    />
                </div>

                <div v-else class="text-zinc-400">
                    {{ $t('No messages yet') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watchEffect } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { IconUsersGroup } from '@tabler/icons-vue'

const props = defineProps({
    chat: {
        type: Object,
        required: true
    }
})

const isUnread = computed(() => (props.chat?.unread_count || 0) > 0)

const getUserWhereAreNotMe = computed(() => {
    if (props.chat.is_group) {
        return { profile_photo_url: '/storage/profile-photos/users-group.png' }
    }
    return props.chat.users.find(user => user.id !== usePage().props.auth.user.id)
})

const lastMessageText = ref('')

watchEffect(async () => {
    if (!props.chat.last_message) {
        lastMessageText.value = ''
        return
    }
    // Plaintext direkt anzeigen (wie bisher); ggf. HTML sicher serverseitig escapen
    lastMessageText.value = props.chat.last_message.message
})
</script>

<style scoped>
/* nichts weiter nötig – alles via Utility-Klassen */
</style>
