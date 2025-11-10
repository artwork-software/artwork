<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        :title="$t('Convert to planned event')"
        :description="$t('Are you sure you want to convert this event to a planning event?')"
    >
        <div class="text-center">

            <div class="flex justify-center gap-4 mt-8">
                <ArtworkBaseModalButton
                    variant="primary"
                    size="md"
                    @click="$emit('convert')"
                >
                    {{ $t('Convert') }}
                </ArtworkBaseModalButton>

                <ArtworkBaseModalButton
                    variant="secondary"
                    size="md"
                    @click="$emit('close')"
                >
                    {{ $t('No, never mind') }}
                </ArtworkBaseModalButton>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

defineEmits(['close', 'convert']);
</script>
