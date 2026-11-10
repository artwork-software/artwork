<template>
    <ArtworkBaseModal
        title="Assign users"
        description="Enter the name of the user to whom you want to assign the checklist."
        modal-size="sm:max-w-lg"
        @close="emitClose"
    >
        <div class="space-y-4">
            <UserSearch
                label="Name"
                :only-team="checklist?.private"
                :team-member="teamMemberIds"
                @user-selected="addUserToChecklist"
            />
            <ul
                v-if="selectedUsers.length > 0"
                class="max-h-72 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
            >
                <li
                    v-for="user in selectedUsers"
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
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                        :aria-label="$t('Remove user from checklist')"
                        @click="removeUser(user)"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </li>
            </ul>
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="emitClose"
            />
            <BaseUIButton
                label="Assign"
                variant="primary"
                icon="IconUserPlus"
                :processing="processing"
                :disabled="!checklist?.id"
                @click="submitUsers"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'

defineOptions({name: 'AddChecklistUserModal'})

const props = defineProps({
    checklist: {type: Object, default: null},
    users: {type: Array, default: () => []},
    project: {type: Object, default: null},
})

const emit = defineEmits(['closed'])

// Eigene Kopie: Abbrechen darf die Liste der Elternkomponente nicht verändern
const selectedUsers = ref([...(props.users ?? [])])
const processing = ref(false)

watch(() => props.users, (users) => {
    selectedUsers.value = [...(users ?? [])]
}, {deep: true})

const teamMemberIds = computed(() => props.project?.users?.map((user) => user.id) ?? [])

function addUserToChecklist(user) {
    if (!selectedUsers.value.some((selected) => selected.id === user.id)) {
        selectedUsers.value.push(user)
    }
}

function removeUser(user) {
    selectedUsers.value = selectedUsers.value.filter((selected) => selected.id !== user.id)
}

function emitClose() {
    emit('closed')
}

async function submitUsers() {
    if (!props.checklist?.id || processing.value) {
        return
    }
    processing.value = true
    try {
        await axios.patch(route('checklists.update', {checklist: props.checklist.id}), {
            assigned_user_ids: selectedUsers.value.map((user) => user.id),
        })
        // Die Listenansicht liest checklist.users direkt; erst nach dem Speichern übernehmen
        if (Array.isArray(props.users)) {
            props.users.splice(0, props.users.length, ...selectedUsers.value)
        }
        emitClose()
    } catch {
        // Fehlermeldung kommt als globaler Toast, das Modal bleibt für einen neuen Versuch offen
    } finally {
        processing.value = false
    }
}
</script>
