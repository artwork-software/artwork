<template>
    <ArtworkBaseModal :title="title" :description="description" @close="$emit('close')">
        <div>
            <BaseAlertComponent message="This action cannot be undone." use-translation type="error" />
        </div>
        <div class="flex items-center justify-between mt-5">
            <ArtworkBaseButton size="md" variant="danger" type="button" @click="$emit('delete')">
                {{ $t('Delete') }}
            </ArtworkBaseButton>
            <ArtworkBaseButton size="md" variant="secondary" type="button" @click="$emit('close')">
                {{ $t('Cancel') }}
            </ArtworkBaseButton>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    },
})


const emits = defineEmits(['close', 'delete'])

</script>

<style scoped>

</style>