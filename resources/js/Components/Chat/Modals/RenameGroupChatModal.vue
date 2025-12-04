<template>
    <ArtworkBaseModal @close="$emit('close')"  :title="$t('Rename Group Chat')"
                      :description="$t('Enter a new name for this group chat')">
        <div class="mt-4">
            <BaseInput
                id="newGroupName"
                v-model="groupName"
                label="Group Name"
                :placeholder="$t('Enter group name')"
            />
        </div>
        <div class="flex justify-between gap-3 mt-6">
            <BaseUIButton
                type="button"
                @click="handleRename"
                :label="$t('Rename')"
                is-add-button
                :disabled="!groupName.trim()"
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
import { ref, watch } from 'vue';
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    chat: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'renamed']);

const groupName = ref('');

// Watch for chat changes to set initial value
watch(
    () => props.chat,
    (newChat) => {
        if (newChat && newChat.name) {
            groupName.value = newChat.name;
        }
    },
    { immediate: true }
);

const handleRename = async () => {
    if (!groupName.value.trim()) return;

    try {
        const response = await axios.patch(route('chat-system.update-chat', { chat: props.chat.id }), {
            name: groupName.value.trim()
        });

        if (response.data.chat) {
            emit('renamed', response.data.chat);
        }

        emit('close');

    } catch (error) {
        console.error('Error renaming group chat:', error);
        // Could add toast notification here for error handling
    }
};
</script>
