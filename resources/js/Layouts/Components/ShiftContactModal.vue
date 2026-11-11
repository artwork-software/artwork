<template>
    <ArtworkBaseModal
        v-if="show"
        title="Contact persons"
        description="Define contact persons for this shift planning"
        modal-size="sm:max-w-xl"
        @close="closeModal"
    >
        <div class="space-y-4">
            <ul v-if="projectManagers?.length > 0" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <li v-for="user in projectManagers" :key="user.id" class="flex min-w-0 items-center gap-x-3">
                    <img
                        :src="user?.profile_photo_url"
                        :alt="user?.name ?? ''"
                        class="size-9 shrink-0 rounded-full object-cover"
                    />
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium text-text">
                            {{ user.first_name }} {{ user.last_name }}
                        </div>
                        <div class="text-xs text-text-muted">
                            {{ $t('Project management') }}
                        </div>
                    </div>
                </li>
            </ul>

            <div>
                <BaseInput
                    id="shiftContactUserSearch"
                    v-model="userQuery"
                    label="Employee"
                    :placeholder="$t('Search for users')"
                    autocomplete="off"
                />
                <ul
                    v-if="userSearchResults.length > 0 && userQuery.length > 0"
                    class="mt-2 max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                >
                    <li v-for="user in userSearchResults" :key="user.id">
                        <button
                            type="button"
                            class="block w-full cursor-pointer truncate px-3 py-2 text-left text-sm font-medium text-text transition-colors duration-150 hover:bg-surface-hover focus-visible:bg-surface-hover motion-reduce:transition-none"
                            @click="addUserToContactArray(user)"
                        >
                            {{ user.first_name }} {{ user.last_name }}
                        </button>
                    </li>
                </ul>
            </div>

            <ul
                v-if="shiftContacts.length > 0"
                class="max-h-72 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
            >
                <li
                    v-for="user in shiftContacts"
                    :key="user.id"
                    class="flex items-center justify-between gap-x-3 px-3 py-2"
                >
                    <div class="flex min-w-0 items-center gap-x-3">
                        <img
                            :src="user.profile_photo_url"
                            alt=""
                            class="size-8 shrink-0 rounded-full object-cover"
                        />
                        <span class="truncate text-sm font-medium text-text">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                        :aria-label="$t('Remove user as contact person')"
                        @click="deleteUserFromContactArray(user)"
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
                @click="closeModal"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                @click="changeShiftContacts"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref, watch} from 'vue'
import axios from 'axios'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineOptions({name: 'ShiftContactModal'})

const props = defineProps({
    show: {type: Boolean, default: false},
    assignedShiftContacts: {type: Array, default: () => []},
    projectId: {type: [Number, String], default: null},
    projectManagers: {type: Array, default: () => []},
})

const emit = defineEmits(['closeModal'])

const userSearchResults = ref([])
const userQuery = ref('')
const shiftContacts = ref([])
const processing = ref(false)

// Das Modal bleibt eingehängt: beim Öffnen frisch aus den Props kopieren
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        shiftContacts.value = [...(props.assignedShiftContacts ?? [])]
        userQuery.value = ''
        userSearchResults.value = []
    }
}, {immediate: true})

watch(userQuery, (query) => {
    if (query.length === 0) {
        userSearchResults.value = []
        return
    }
    axios.get('/users/search', {params: {query}}).then((response) => {
        // Veraltete Antworten verwerfen, wenn inzwischen weitergetippt wurde
        if (userQuery.value === query) {
            userSearchResults.value = response.data
        }
    })
})

function closeModal() {
    emit('closeModal')
}

function changeShiftContacts() {
    if (processing.value) {
        return
    }
    processing.value = true
    router.patch(route('projects.update.shift_contacts', {project: props.projectId}), {
        contactIds: shiftContacts.value.map((contact) => contact.id),
    }, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => {
            processing.value = false
        },
    })
}

function addUserToContactArray(user) {
    if (!shiftContacts.value.some((contact) => contact.id === user.id)) {
        shiftContacts.value.push(user)
    }
    userQuery.value = ''
}

function deleteUserFromContactArray(user) {
    shiftContacts.value = shiftContacts.value.filter((contact) => contact.id !== user.id)
}
</script>
