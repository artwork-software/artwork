<template>
    <ArtworkBaseModal
        title="Decline shift"
        description="You can add an optional comment explaining why you decline this shift."
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div v-if="workerName" class="text-sm text-text">
                {{ $t('Decline shift assignment for {name}?', { name: workerName }) }}
            </div>

            <BaseTextarea
                v-model="comment"
                rows="3"
                :label="$t('Comment (optional)')"
                id="shift-confirmation-decline-comment"
                :maxlength="500"
            />

            <div class="mt-4 flex justify-between gap-4">
                <BaseUIButton type="button" @click="$emit('close')" :label="$t('Cancel')" is-cancel-button />
                <BaseUIButton type="submit" is-delete-button :label="$t('Decline shift')" />
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineProps({
    workerName: { type: String, required: false, default: null },
})

const emit = defineEmits(['close', 'submit'])

const comment = ref('')

const submit = () => {
    emit('submit', comment.value.trim() || null)
}
</script>
