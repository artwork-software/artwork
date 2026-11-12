<template>
    <ArtworkBaseModal
        v-if="show"
        title="Upload document"
        description="Upload documents to this room."
        modal-size="sm:max-w-xl"
        @close="resetModal"
    >
        <form id="roomFileUploadForm" class="space-y-4" @submit.prevent="storeFiles">
            <div>
                <input
                    id="roomFileUploadInput"
                    ref="room_files"
                    type="file"
                    class="sr-only"
                    multiple
                    @change="upload"
                />
                <div
                    role="button"
                    tabindex="0"
                    class="flex min-h-28 w-full cursor-pointer flex-col items-center justify-center gap-y-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    :class="isDragging ? 'border-accent-600 bg-accent-50' : 'border-border'"
                    @click="selectNewFiles"
                    @keydown.enter.prevent="selectNewFiles"
                    @keydown.space.prevent="selectNewFiles"
                    @dragenter.prevent="isDragging = true"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = $event.currentTarget.contains($event.relatedTarget)"
                    @drop.stop.prevent="uploadDraggedDocuments($event)"
                >
                    <PropertyIcon name="IconUpload" class="size-6 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                    <p class="text-sm text-text-muted">
                        <template v-if="isDragging">{{ $t('Drop here') }}</template>
                        <template v-else>
                            <span class="font-semibold text-accent-600">{{ $t('Choose file') }}</span>
                            {{ $t('or drag and drop') }}
                        </template>
                    </p>
                </div>
            </div>

            <ul v-if="files.length > 0" class="divide-y divide-border-subtle rounded-lg border border-border-subtle">
                <li
                    v-for="(file, index) in files"
                    :key="file.name + '-' + index"
                    class="flex items-center justify-between gap-x-3 px-3 py-2 text-sm text-text"
                >
                    <div class="flex min-w-0 items-center gap-x-2">
                        <PropertyIcon name="IconFile" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                        <span class="truncate">{{ file.name }}</span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger disabled:cursor-default disabled:text-text-subtle"
                        :aria-label="$t('Remove file')"
                        :disabled="isUploading"
                        @click="removeFile(index)"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </li>
            </ul>

            <BaseAlertComponent
                v-if="uploadDocumentFeedback"
                type="error"
                class="mb-0!"
                :message="uploadDocumentFeedback"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="isUploading"
                @click="resetModal"
            />
            <BaseUIButton
                type="submit"
                form="roomFileUploadForm"
                label="Upload document"
                variant="primary"
                icon="IconUpload"
                :processing="isUploading"
                :disabled="files.length < 1 || isUploading"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from "vue";
import {router, useForm} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";
import {
    inertiaUploadErrorMessage,
    isInvalidResponse,
    submitInertiaForm,
    uploadSequentially,
} from "@/Helper/sequentialUpload.js";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

defineOptions({name: 'RoomFileUploadModal'})

const props = defineProps({
    show: Boolean,
    closeModal: Function,
    roomId: Number
})

const uploadDocumentFeedback = ref("")
const files = ref([])
const room_files = ref(null)
const isUploading = ref(false)
const isDragging = ref(false)
const $t = useTranslation()

const roomFileForm = useForm({
    file: null
})

const selectNewFiles = () => {
    room_files.value.click();
}

const uploadDraggedDocuments = (event) => {
    isDragging.value = false
    validateType([...event.dataTransfer.files])
}

const upload = (event) => {
    validateType([...event.target.files])
    // Gleiche Datei erneut wählbar machen
    event.target.value = ''
}

const removeFile = (index) => {
    files.value.splice(index, 1)
}

const resetModal = () => {
    if (isUploading.value) {
        return
    }
    files.value = []
    uploadDocumentFeedback.value = ''
    props.closeModal()
}

/** Eine Datei hochladen (Promise). Fehler meldet das Modal selbst (kein globaler alert/Toast). */
const storeFile = (file) => {
    roomFileForm.file = file

    return submitInertiaForm(
        roomFileForm,
        'post',
        route('room_files.store', props.roomId),
        { preserveState: true, preserveScroll: true },
        { router }
    )
}

const validateType = (newFiles) => {
    uploadDocumentFeedback.value = "";
    for (let file of newFiles) {
      files.value.push(file)
    }
}

/**
 * Nacheinander hochladen: mehrere .post() derselben useForm brachen sich gegenseitig ab, praktisch kam nur die
 * letzte Datei an. Fehlgeschlagene (und nach einer ungültigen Antwort übersprungene) Dateien bleiben mit
 * Meldung stehen; das Modal schließt nur, wenn alles hochgeladen ist.
 */
const storeFiles = async () => {
    // Doppelklick: eine zweite Kette über dieselbe useForm bräche die erste ab
    if (isUploading.value || files.value.length < 1) {
        return
    }
    isUploading.value = true
    uploadDocumentFeedback.value = ''

    const { failed, skipped } = await uploadSequentially(
        [...files.value],
        (file) => storeFile(file),
        { stopOnError: isInvalidResponse }
    )

    isUploading.value = false
    roomFileForm.file = null
    files.value = [...failed.map(({ file }) => file), ...skipped]

    if (failed.length > 0) {
        uploadDocumentFeedback.value = failed
            .map(({ file, error }) => `${file.name}: ${inertiaUploadErrorMessage(error, $t)}`)
            .join(' ')
        return
    }

    props.closeModal()
}
</script>
