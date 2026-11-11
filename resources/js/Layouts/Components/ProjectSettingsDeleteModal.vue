<template>
    <ArtworkBaseModal
        v-if="show"
        :title="title || 'Delete'"
        modal-size="sm:max-w-lg"
        @close="$emit('closeModal')"
    >
        <p v-if="description" class="text-sm text-text">
            {{ description }}
        </p>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                @click="$emit('closeModal')"
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

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: "ProjectSettingsDeleteModal",
    props: {
        show: Boolean,
        title: String,
        description: String
    },
    emits: ['delete', 'closeModal'],
    components: {
        ArtworkBaseModal,
        BaseUIButton,
    }
}
</script>
