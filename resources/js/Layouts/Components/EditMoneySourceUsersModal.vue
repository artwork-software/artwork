<template>
    <ArtworkBaseModal
        title="Manage release"
        description="Type the name of the users you want to give access to the source. You can only select users who are authorized to edit a funding source. Authorized users automatically have access to the source."
        modal-size="sm:max-w-2xl"
        @close="closeModal(false)"
    >
        <div class="space-y-4">
            <UserSearch label="Search for users" @user-selected="addUserToMoneySourceUserArray"/>
            <ul
                v-if="assignedUsers.length > 0"
                class="max-h-96 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
            >
                <li
                    v-for="user in assignedUsers"
                    :key="user.id"
                    class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-3 py-2"
                >
                    <div class="flex min-w-0 items-center gap-x-3">
                        <img class="size-8 shrink-0 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                        <span class="truncate text-sm font-medium text-text">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <div class="flex items-center gap-x-4">
                        <BaseCheckbox
                            :id="'moneySourceUserCompetent-' + user.id"
                            v-model="user.competent"
                            :label="$t('Responsible')"
                        />
                        <BaseCheckbox
                            :id="'moneySourceUserWriteAccess-' + user.id"
                            v-model="user.write_access"
                            :label="$t('Write and delete permission')"
                        />
                        <button
                            type="button"
                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger"
                            :aria-label="$t('Remove user')"
                            @click="deleteUserFromMoneySourceUserArray(user)"
                        >
                            <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                        </button>
                    </div>
                </li>
            </ul>
            <p v-else class="text-xs text-text-muted">
                {{ $t('No users added yet') }}
            </p>
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="processing"
                @click="editMoneySourceUsers"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'

defineOptions({name: 'EditMoneySourceUsersModal'})

const props = defineProps({
    moneySource: {type: Object, required: true},
})

const emit = defineEmits(['closed'])

/**
 * Arbeitskopie: früher wurden das Nutzer*innen-Array und die Pivot-Werte der Quelle direkt verändert,
 * Abbrechen hätte die Änderungen dann trotzdem in der Anzeige stehen lassen.
 */
function toAssignedUser(user) {
    return {
        ...user,
        competent: Boolean(user.pivot?.competent ?? user.competent ?? false),
        write_access: Boolean(user.pivot?.write_access ?? user.write_access ?? false),
    }
}

const assignedUsers = ref((props.moneySource?.users ?? []).map(toAssignedUser))
const processing = ref(false)

function closeModal(saved) {
    emit('closed', saved)
}

function addUserToMoneySourceUserArray(userToAdd) {
    if (assignedUsers.value.some((assignedUser) => assignedUser.id === userToAdd.id)) {
        return
    }
    assignedUsers.value.push(toAssignedUser(userToAdd))
}

function deleteUserFromMoneySourceUserArray(userToRemove) {
    assignedUsers.value = assignedUsers.value.filter((user) => user.id !== userToRemove.id)
}

function editMoneySourceUsers() {
    if (processing.value) {
        return
    }
    const users = {}
    assignedUsers.value.forEach((user) => {
        users[user.id] = {
            user_id: user.id,
            competent: user.competent,
            write_access: user.write_access,
        }
    })
    processing.value = true
    router.patch(
        route('money_sources.update_users', {moneySource: props.moneySource.id}),
        {users},
        {
            preserveScroll: true,
            onSuccess: () => closeModal(true),
            onFinish: () => {
                processing.value = false
            },
        }
    )
}
</script>
