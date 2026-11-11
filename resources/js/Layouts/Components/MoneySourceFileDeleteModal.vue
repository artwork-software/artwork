<template>
    <ArtworkBaseModal
        title="Delete document"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <div class="space-y-3">
            <p class="text-sm text-text">
                {{ $t('Are you sure you want to delete this document from the system?') }}
            </p>
            <div
                v-if="file?.name"
                class="truncate rounded-md border border-border-subtle bg-surface-sunken px-3 py-2 text-sm font-semibold text-text"
            >
                {{ file.name }}
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
                :disabled="!file?.id"
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

defineOptions({name: 'MoneySourceFileDeleteModal'})

const props = defineProps({
    file: {type: Object, default: null},
    show: {type: Boolean, default: false},
    closeModal: {type: Function, required: true},
    moneySourceId: {type: Number, default: null},
})

const processing = ref(false)

function destroy() {
    if (!props.file?.id || processing.value) {
        return
    }
    processing.value = true
    router.delete(route('money_sources_delete_file', [props.moneySourceId, props.file.id]), {
        onSuccess: () => props.closeModal(),
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
