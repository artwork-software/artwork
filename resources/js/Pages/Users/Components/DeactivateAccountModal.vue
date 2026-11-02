<template>
    <BaseModal @closed="emit('closed')" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="text-2xl font-bold text-text my-2">
                {{ $t('Deactivate account') }}
            </div>
            <div class="text-sm text-text-muted">
                {{ $t('{name} can no longer sign in. Rights, settings and assignments to projects and shifts are kept, and the account can be reactivated at any time.', { name: `${user.first_name} ${user.last_name}` }) }}
            </div>
            <div class="mt-6 flex items-center justify-between">
                <BaseUIButton :label="$t('Deactivate')" variant="primary" hide-icon :disabled="processing" @click="deactivate" />
                <button @click="emit('closed')" class="text-sm text-text-subtle hover:text-text">
                    {{ $t('No, not really') }}
                </button>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

// Bestätigung vor dem Deaktivieren (Nutzer*innenliste und Reiter „Persönliche Daten“)
const props = defineProps({
    user: { type: Object, required: true },
})
const emit = defineEmits(['closed'])

const processing = ref(false)

const deactivate = () => {
    processing.value = true
    router.post(route('user.deactivate', { user: props.user.id }), {}, {
        preserveScroll: true,
        onSuccess: () => emit('closed'),
        onFinish: () => (processing.value = false),
    })
}
</script>
