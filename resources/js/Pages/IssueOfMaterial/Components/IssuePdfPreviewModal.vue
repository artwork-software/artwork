<template>
    <!-- Dokumentenvorschau vor der finalen Leihschein-/PDF-Erstellung (Abnahme MAT-03 Ref. 1.14):
         Die Vorschau rendert das PDF über ?preview=1 — gespeichert und an die Ausgabe angehängt
         wird erst beim Klick auf „Final erstellen". -->
    <ArtworkBaseModal
        :title="$t('Document preview')"
        :description="$t('Check the document first. It will only be saved and attached to the issue as a file when you create it.')"
        modal-size="max-w-5xl"
        @close="$emit('close')"
    >
        <div class="h-[70vh]">
            <iframe
                :src="previewUrl"
                class="h-full w-full rounded-lg border border-border-subtle bg-surface-sunken"
                :title="$t('Document preview')"
            />
        </div>

        <template #footer>
            <BaseUIButton label="Cancel" is-cancel-button hide-icon use-translation @click="$emit('close')"/>
            <BaseUIButton
                :label="$t('Create and attach document')"
                variant="primary"
                icon="IconFileCheck"
                @click="createFinal"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    // Finale Druck-URL (route('…print', id)) — die Vorschau hängt ?preview=1 an
    printUrl: {
        type: String,
        required: true,
    },
})

const emit = defineEmits(['close', 'created'])

const previewUrl = computed(() => props.printUrl + (props.printUrl.includes('?') ? '&' : '?') + 'preview=1')

const createFinal = () => {
    // Finaler Aufruf erzeugt das PDF, hängt es an die Ausgabe und zeigt es im neuen Tab
    window.open(props.printUrl, '_blank')
    emit('created')
    emit('close')
    // Datei-Anhänge der Zeile aktualisieren (das PDF hängt jetzt an der Ausgabe)
    setTimeout(() => router.reload({ preserveScroll: true }), 1500)
}
</script>
