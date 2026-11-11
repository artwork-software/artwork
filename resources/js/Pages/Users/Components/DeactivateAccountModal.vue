<template>
    <ArtworkBaseModal
        title="Deactivate account"
        modal-size="sm:max-w-lg"
        @close="close"
    >
        <p class="text-sm text-text">
            {{ $t('{name} can no longer sign in. Rights, settings and assignments to projects and shifts are kept, and the account can be reactivated at any time.', { name: fullName }) }}
        </p>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="close"
            />
            <BaseUIButton
                label="Deactivate"
                variant="primary"
                icon="IconUserOff"
                :processing="processing"
                :disabled="processing"
                @click="deactivate"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

// Bestätigung vor dem Deaktivieren (Nutzer*innenliste und Reiter „Persönliche Daten“)
const props = defineProps({
    user: { type: Object, required: true },
})
const emit = defineEmits(['closed'])

const processing = ref(false)

const fullName = computed(() => `${props.user.first_name} ${props.user.last_name}`)

const close = () => emit('closed')

const deactivate = () => {
    if (processing.value) {
        return
    }
    processing.value = true
    router.post(route('user.deactivate', { user: props.user.id }), {}, {
        preserveScroll: true,
        onSuccess: () => emit('closed'),
        onFinish: () => (processing.value = false),
    })
}
</script>
