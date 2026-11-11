<template>
    <ArtworkBaseModal :title="title" modal-size="sm:max-w-lg" z-index="999999999999999999999" @close="$emit('close')">
        <div class="space-y-4">
            <p v-if="description" class="text-sm text-text">{{ $t(description) }}</p>
            <BaseAlertComponent message="This action cannot be undone." use-translation type="error" class="!mb-0" />
        </div>
        <template #footer>
            <BaseUIButton label="Cancel" is-cancel-button hide-icon @click="$emit('close')" />
            <BaseUIButton label="Delete" variant="danger" icon="IconTrash" @click="$emit('delete')" />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineProps({
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    },
})

defineEmits(['close', 'delete'])
</script>
