<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader
            :title="$t('Rename Group Chat')"
            :description="$t('Enter a new name for this group chat')"
        />
        <div class="mt-4">
            <BaseInput
                id="newGroupName"
                v-model="groupName"
                label="Group Name"
                :placeholder="$t('Enter group name')"
            />
        </div>
        <div class="flex justify-between gap-3 mt-6">
            <FormButton
                type="button"
                @click="handleRename"
                :text="$t('Rename')"
                :disabled="!groupName.trim()"
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
import { ref, watch } from 'vue';
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

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
