<template>
    <ArtworkBaseModal
        v-if="show"
        title="Delete contract"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <div class="space-y-3">
            <p class="text-sm text-text">
                {{ $t('Are you sure you want to delete this contract from the system?') }}
            </p>
            <div
                v-if="contract?.name"
                class="truncate rounded-md border border-border-subtle bg-surface-sunken px-3 py-2 text-sm font-semibold text-text"
            >
                {{ contract.name }}
            </div>
        </div>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal"
            />
            <BaseUIButton
                label="Delete"
                variant="danger"
                icon="IconTrash"
                :processing="processing"
                :disabled="!contract?.id"
                @click="destroy"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'ContractDeleteModal'})

const props = defineProps({
    contract: {type: Object, default: null},
    show: {type: Boolean, default: false},
    closeModal: {type: Function, required: true},
})

const processing = ref(false)

function destroy() {
    if (!props.contract?.id || processing.value) {
        return
    }
    processing.value = true
    router.delete(route('contract.delete', props.contract.id), {
        onSuccess: () => props.closeModal(),
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
