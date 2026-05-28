<template>
    <ArtworkBaseModal
        :title="$t('Switch input mode?')"
        :description="$t('Switching the mode will discard all current entries in this mode. Do you want to continue?')"
        @close="$emit('close')"
    >
        <div class="flex justify-end gap-3 pt-4">
            <button @click="$emit('close')" class="text-sm text-gray-500 hover:text-gray-700">
                {{ $t('Cancel') }}
            </button>
            <BaseUIButton :label="$t('Continue')" @click="$emit('confirm')" />
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

defineEmits(['confirm', 'close']);
</script>
