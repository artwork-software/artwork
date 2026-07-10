<template>
    <ArtworkBaseModal
        :title="$t('Accept shift plan request')"
        :description="$t('Optionally add a note for the requesting person. It will be included in their notification.')"
        modal-size="sm:max-w-lg"
        @close="$emit('close')"
    >
        <div class="space-y-4 text-sm">
            <BaseTextarea
                id="accept-request-comment"
                rows="3"
                class="w-full"
                maxlength="1000"
                :label="$t('Note for the requesting person (optional)')"
                v-model="comment"
            />

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                <BaseUIButton type="button" :label="$t('Cancel')" is-cancel-button @click="$emit('close')" />
                <BaseUIButton
                    type="button"
                    is-add-button
                    icon="IconCheck"
                    :label="$t('Accept')"
                    @click="$emit('confirm', comment)"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from 'vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';

defineEmits(['close', 'confirm']);

const comment = ref('');
</script>
