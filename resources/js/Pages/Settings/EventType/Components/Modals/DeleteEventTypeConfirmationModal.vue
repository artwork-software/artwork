<template>
    <ArtworkBaseModal
        title="Delete event type"
        modal-size="sm:max-w-lg"
        @close="$emit('closed')"
    >
        <p class="text-sm text-text">
            {{ $t('Are you sure you want to delete the event type {eventType} from the system? All events that are currently assigned to this type will be reassigned to the first event type.', {eventType: eventType.name}) }}
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
    eventType: {
        type: Object,
        required: true,
    },
})

defineEmits(['closed', 'delete'])
</script>
