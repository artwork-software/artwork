<template>
    <!-- Vorschau der Vertragsdatei (PDF oder Bild), ohne sie herunterzuladen -->
    <ArtworkBaseModal
        :title="contract.name"
        modal-size="max-w-5xl"
        @close="$emit('close')"
    >
        <div class="h-[70vh]">
            <img
                v-if="isImage"
                :src="previewUrl"
                :alt="contract.name"
                class="mx-auto h-full max-w-full rounded-lg border border-border-subtle bg-surface-sunken object-contain"
            />
            <iframe
                v-else
                :src="previewUrl"
                class="h-full w-full rounded-lg border border-border-subtle bg-surface-sunken"
                :title="contract.name"
            />
        </div>

        <template #footer>
            <BaseUIButton label="Close" is-cancel-button hide-icon use-translation @click="$emit('close')"/>
            <BaseUIButton
                :label="$t('Download')"
                variant="primary"
                icon="IconDownload"
                @click="download"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({ name: 'ContractPreviewModal' })

const props = defineProps({
    contract: { type: Object, required: true },
})

defineEmits(['close'])

const previewUrl = computed(() => route('contracts.preview', props.contract.id))
const isImage = computed(() => props.contract.preview_kind === 'image')

function download() {
    window.location.href = route('contracts.download', props.contract.id)
}
</script>
