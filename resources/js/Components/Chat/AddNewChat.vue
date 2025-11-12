<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        :title="$t('Create new Chat')"
        :description="$t('Select users to start a chat. When multiple users are selected, a group chat will be created and you will automatically be included as a member.')"
    >
        <div class="space-y-4">
            <!-- User-Suche -->
            <UserSearch
                @userSelected="addUserToChat"
                :label="$t('Add users')"
                :only-use-chat-users="true"
                :without-self="true"
            />

            <!-- Ausgewählte Nutzer -->
            <div v-if="chatUsers.length > 0" class="mt-1">
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(user, index) in chatUsers"
                        :key="user.id ?? index"
                        class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 pl-1.5 pr-2 py-1"
                    >
                        <img
                            class="inline-block size-7 rounded-full object-cover"
                            :src="user?.profile_photo_url"
                            alt=""
                        />
                        <span class="text-xs font-medium text-zinc-800 max-w-[180px] truncate" :title="user.name">
              {{ user.name }}
            </span>
                        <button
                            type="button"
                            class="grid place-items-center size-6 rounded-full text-zinc-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                            @click="deleteUserFromForm(index)"
                            :aria-label="$t('Remove user')"
                        >
                            <XIcon class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hinweis + Name bei Gruppenchat -->
            <div v-if="chatUsers.length > 1" class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3">
                <div class="flex gap-3">
                    <component :is="IconInfoSquareRoundedFilled" class="size-5 text-yellow-500 shrink-0" />
                    <p class="text-xs sm:text-sm text-yellow-900">
                        {{ $t('You have selected multiple users. This will create a group chat. You will automatically be included as a member. Please provide a name for the group.') }}
                    </p>
                </div>

            </div>

            <div class="mt-3"  v-if="chatUsers.length > 1">
                <BaseInput
                    id="chatName"
                    v-model="chatName"
                    :label="$t('Name')"
                    :placeholder="$t('e.g. Project Alpha / Sales Team')"
                />
            </div>

            <!-- Aktionen -->
            <div class="flex items-center justify-end pt-1">
                <BaseUIButton
                    type="submit"
                    @click="createChat"
                    :label="$t('Create')"
                    :disabled="!canCreate"
                    :class="[
            'min-w-28',
            !canCreate ? 'opacity-60 cursor-not-allowed' : ''
          ]"
                    is-add-button
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'
import TextInputComponent from '@/Components/Inputs/TextInputComponent.vue'
import { XIcon } from '@heroicons/vue/outline'
import { IconInfoSquareRoundedFilled } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({})
const emit = defineEmits(['close'])

const chatName = ref('')
const chatUsers = ref([])

/** UX: Gruppen-Logik + einfache Validierung */
const isGroup = computed(() => chatUsers.value.length > 1)
const canCreate = computed(() => {
    if (chatUsers.value.length === 0) return false
    if (isGroup.value && !chatName.value.trim()) return false
    return true
})

const addUserToChat = (user) => {
    if (!user?.id) return
    if (!chatUsers.value.some(u => u.id === user.id)) {
        chatUsers.value.push(user)
    }
}

const deleteUserFromForm = (index) => {
    chatUsers.value.splice(index, 1)
}

/** API unverändert */
const createChat = async () => {
    try {
        const response = await fetch('/api/chat/store', {
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

        if (!response.ok || !data?.chat) return
        emit('close', data.chat.id)
    } catch (err) {
        console.error('Fehler bei der Anfrage:', err)
    }
}
</script>
