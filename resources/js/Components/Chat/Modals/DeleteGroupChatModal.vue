<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader
            :title="$t('Delete Group Chat')"
            :description="$t('Are you sure you want to delete this group chat? This action cannot be undone and all messages will be permanently deleted.')"
        />
        <div class="flex justify-between gap-3 mt-6">
            <FormButton
                type="button"
                class="bg-artwork-error hover:bg-artwork-error/80"
                @click="handleDelete"
                :text="$t('Delete')"
                variant="danger"
            />
            <FormButton
                type="button"
                @click="$emit('close')"
                :text="$t('Cancel')"
                variant="secondary"
            />

        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

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
