<template>
    <ArtworkBaseModal
        title="Delete event type"
        modal-size="sm:max-w-lg"
        @close="$emit('closed')"
    >
        <BaseAlertComponent
            type="warning"
            :message="$t('The event type {eventType} cannot be deleted because it is the standard event type.', {eventType: eventType.name})"
            class="!mb-0"
        />
        <template #footer>
            <BaseUIButton
                label="Ok"
                variant="primary"
                hide-icon
                @click="$emit('closed')"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'

defineProps({
    eventType: {
        type: Object,
        required: true,
    },
})

defineEmits(['closed'])
</script>
