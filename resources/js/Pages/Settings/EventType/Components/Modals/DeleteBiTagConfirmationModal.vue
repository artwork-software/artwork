<template>
    <ArtworkBaseModal
        title="Delete BI tag"
        modal-size="sm:max-w-lg"
        @close="$emit('closed')"
    >
        <p class="text-sm text-text">
            {{ $t('Are you sure you want to delete the BI tag {tag}? Its counts will no longer appear in the BI dashboard and in BI exports.', { tag: tag.name_de }) }}
        </p>
        <template #footer>
            <BaseUIButton
                label="No, actually not"
                is-cancel-button
                hide-icon
                @click="$emit('closed')"
            />
            <BaseUIButton
                label="Delete"
                variant="danger"
                icon="IconTrash"
                @click="$emit('delete')"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineProps({
    tag: { type: Object, required: true },
})

defineEmits(['closed', 'delete'])
</script>
