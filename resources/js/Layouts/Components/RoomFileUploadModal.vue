<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <ModalHeader
                    :title="$t('Upload document')"
                    :description="$t('Upload documents to this room.')"
                />

                <div>
                    <input
                        @change="upload"
                        class="hidden"
                        ref="room_files"
                        id="file"
                        type="file"
                        multiple
                    />
                    <div @click="selectNewFiles" @dragover.prevent
                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex rounded-lg justify-center items-center
                        border-accent-600 border-dotted border-2 h-32  p-2 cursor-pointer">
                        <p class="text-accent-600 font-bold text-center">
                            {{ $t('Drag document here to upload or click in the field')}}
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div class="mb-3">
                    <MultiAlertComponent :errors="roomFileForm.errors" v-show="Object.keys(roomFileForm.errors).length > 0"  :error-count="Object.keys(roomFileForm.errors).length" />
                </div>
                <div class="mb-6">
                    <div v-for="file in files">
                        <div class="flex items-center justify-between">
                            <div>
                                {{ file.name }}
                            </div>
                            <div>
                                <component :is="IconCircleX" class="size-5 text-danger cursor-pointer hover:text-accent-700 transition-colors duration-300 ease-in-out" @click="files.splice(files.indexOf(file), 1)"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <FormButton
                        :text="$t('Upload document')"
                        @click="storeFiles"
                        :disabled="files.length < 1 || isUploading"
                    />
                </div>
            </div>
    </BaseModal>
</template>

<script setup>
import JetInputError from '@/Jetstream/InputError.vue'
import {ref} from "vue";
import {router, useForm} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";
import {
    inertiaUploadErrorMessage,
    isInvalidResponse,
    submitInertiaForm,
    uploadSequentially,
} from "@/Helper/sequentialUpload.js";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import MultiAlertComponent from "@/Components/Alerts/MultiAlertComponent.vue";
import {IconCircleX} from "@tabler/icons-vue";

const props = defineProps({
    show: Boolean,
    closeModal: Function,
    roomId: Number
})

const uploadDocumentFeedback = ref("")
const files = ref([])
const room_files = ref(null)

const closeModalIfUploaded = ref(false)
const isUploading = ref(false)
const $t = useTranslation()

const roomFileForm = useForm({
    file: null
})

const selectNewFiles = () => {
    room_files.value.click();
}

const uploadDraggedDocuments = (event) => {
    validateType([...event.dataTransfer.files])
}

const upload = (event) => {
    validateType([...event.target.files])
}

/** Eine Datei hochladen (Promise) – Fehler meldet das Modal selbst (kein globaler alert/Toast). */
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
        closeModalIfUploaded.value = false
        uploadDocumentFeedback.value = failed
            .map(({ file, error }) => `${file.name}: ${inertiaUploadErrorMessage(error, $t)}`)
            .join(' ')
        return
    }

    closeModalIfUploaded.value = true
    props.closeModal()
}

</script>

<style scoped>

</style>
