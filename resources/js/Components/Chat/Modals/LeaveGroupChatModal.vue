<template>
    <ArtworkBaseModal @close="$emit('close')" :title="$t('Leave Group Chat')"
                      :description="$t('Are you sure you want to leave this group chat? You will no longer see its messages. The chat remains available to the other members.')">
        <div class="flex justify-between gap-3 mt-6">
            <BaseUIButton
                type="button"
                is-delete-button
                @click="handleLeave"
                :label="$t('Leave')"
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
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    chat: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'left']);

const handleLeave = async () => {
    try {
        const response = await axios.post(route('chat-system.leave-chat', { chat: props.chat.id }));

        if (response.status === 200) {
            emit('left', props.chat.id);
        }

        emit('close');
    } catch (error) {
        console.error('Error leaving group chat:', error);
    }
};
</script>
