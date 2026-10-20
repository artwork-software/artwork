<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        :title="count > 1 ? $t('Convert to planned events') : $t('Convert to planned event')"
        :description="count > 1
            ? $t('Are you sure you want to convert the {0} selected events to planned events?', [count])
            : $t('Are you sure you want to convert this event to a planning event?')"
    >
        <div class="text-center">

            <div class="flex justify-center gap-4 mt-8">
                <BaseUIButton
                    variant="primary"
                    hide-icon
                    @click="$emit('convert')"
                >
                    {{ $t('Convert') }}
                </BaseUIButton>

                <BaseUIButton
                    variant="secondary"
                    hide-icon
                    @click="$emit('close')"
                >
                    {{ $t('No, never mind') }}
                </BaseUIButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineProps({
    // Mehrfachauswahl: Anzahl der umzuwandelnden Termine
    count: { type: Number, default: 1 },
});

defineEmits(['close', 'convert']);
</script>
