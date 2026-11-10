<template>
    <ArtworkBaseModal
        title="Delete checklist"
        modal-size="sm:max-w-lg"
        @close="emit('closed')"
    >
        <p class="text-sm text-text">
            {{ $t('Are you sure you want to delete the checklist?', [checklistToDelete?.name ?? '']) }}
        </p>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                @click="emit('closed')"
            />
            <BaseUIButton
                label="Delete"
                variant="danger"
                icon="IconTrash"
                @click="emit('deleteChecklist')"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'DeleteChecklistModal'})

defineProps({
    checklistToDelete: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['closed', 'deleteChecklist'])
</script>
