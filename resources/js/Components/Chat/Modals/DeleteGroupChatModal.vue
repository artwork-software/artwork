<template>
    <ArtworkBaseModal @close="$emit('close')" :title="$t('Delete Group Chat')"
                      :description="$t('Are you sure you want to delete this group chat? This action cannot be undone and all messages will be permanently deleted.')">
        <div class="flex justify-between gap-3 mt-6">
            <BaseUIButton
                type="button"
                is-delete-button
                @click="handleDelete"
                :label="$t('Delete')"
            />
            <BaseUIButton
                type="button"
                @click="$emit('close')"
                :label="$t('Cancel')"
                is-cancel-button
            />

        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    chat: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'deleted']);

const handleDelete = async () => {
    try {
        const response = await axios.delete(route('chat-system.delete-chat', { chat: props.chat.id }));

        if (response.status === 200) {
            emit('deleted', props.chat.id);
        }

        emit('close');

    } catch (error) {
        console.error('Error deleting group chat:', error);
        // Could add toast notification here for error handling
    }
};
</script>
