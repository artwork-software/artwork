<template>
    <ArtworkBaseModal
        modal-size="sm:max-w-lg"
        :title="$t('Roles of {name}', { name: contact.display_name })"
        :description="$t('The special role “Artist” shows the person as an artist of the project everywhere.')"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <label class="flex items-center gap-2 rounded-lg border border-accent-200 bg-accent-50/60 px-3 py-2 text-sm text-text">
                <input v-model="isArtist" type="checkbox" class="input-checklist" />
                <IconPalette class="size-4 text-accent-700" stroke-width="1.5" />
                {{ $t('Artist') }}
            </label>

            <div v-if="rolesEnabled" class="space-y-2">
                <label v-for="role in allRoles" :key="role" class="flex items-center gap-2 text-sm text-text">
                    <input v-model="selected" type="checkbox" class="input-checklist" :value="role" />
                    {{ role }}
                </label>
                <div class="flex items-end gap-2">
                    <BaseInput
                        id="crm-contact-new-role"
                        v-model="newRole"
                        :label="$t('New role')"
                        list="crm-contact-role-suggestions"
                        without-translation
                        class="flex-1"
                        @keydown.enter.prevent="addNewRole"
                    />
                    <BaseUIButton type="button" variant="secondary" size="sm" hide-icon :disabled="!newRole.trim()" @click="addNewRole">
                        {{ $t('Add') }}
                    </BaseUIButton>
                </div>
                <datalist id="crm-contact-role-suggestions">
                    <option v-for="suggestion in suggestions" :key="suggestion" :value="suggestion" />
                </datalist>
            </div>

            <p v-if="error" class="text-xs text-danger">{{ error }}</p>

            <div class="flex justify-end gap-3 pt-2">
                <BaseUIButton type="button" hide-icon @click="$emit('close')">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton type="button" variant="primary" hide-icon :disabled="saving" @click="save">{{ $t('Save') }}</BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { IconPalette } from '@tabler/icons-vue'

const props = defineProps({
    contact: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    suggestions: { type: Array, default: () => [] },
    rolesEnabled: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'save'])

const isArtist = ref(!!props.contact.is_artist)
const selected = ref([...(props.contact.roles ?? [])])
const extraRoles = ref([])
const newRole = ref('')

const allRoles = computed(() => {
    const seen = new Set()
    return [...props.roles, ...extraRoles.value, ...selected.value].filter((role) => {
        const key = role.toLowerCase()
        if (seen.has(key)) return false
        seen.add(key)
        return true
    })
})

function addNewRole() {
    const role = newRole.value.trim()
    if (!role) return
    extraRoles.value.push(role)
    if (!selected.value.includes(role)) selected.value.push(role)
    newRole.value = ''
}

function save() {
    emit('save', {
        is_artist: isArtist.value,
        ...(props.rolesEnabled ? { roles: selected.value } : {}),
    })
}
</script>
