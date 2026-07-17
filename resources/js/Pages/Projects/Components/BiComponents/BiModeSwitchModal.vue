<template>
    <ArtworkBaseModal
        :title="$t('Switch input mode?')"
        :description="$t('Switching the mode will discard all current entries in this mode. Do you want to continue?')"
        @close="$emit('close')"
    >
        <p v-if="metricLabel && entryCount > 0" class="mt-3 rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-900">
            {{ entryCount }} {{ $t('recorded value(s) for') }} „{{ metricLabel }}“ {{ $t('will be discarded.') }}
        </p>
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

defineProps({
    // Kennzahl-Name + Anzahl betroffener Einträge für eine konkrete Warnung
    metricLabel: { type: String, default: '' },
    entryCount: { type: Number, default: 0 },
});

defineEmits(['confirm', 'close']);
</script>
