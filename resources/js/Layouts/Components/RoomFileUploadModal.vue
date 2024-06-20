<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Upload document') }}
                </div>
                <div class="text-secondary text-sm my-6">
                    {{$t('Upload documents to this room.')}}
                </div>
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
                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-artwork-buttons-create border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-artwork-buttons-create font-bold text-center">
                            {{ $t('Drag document here to upload or click in the field')}}
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div class="mb-6">
                    <div v-for="file of files">{{ file.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <FormButton
                        :text="$t('Upload document')"
                        @click="storeFiles"
                        :disabled="files.length < 1"
                    />
                </div>
            </div>
    </BaseModal>
</template>

<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {XIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

const props = defineProps({
    show: Boolean,
    closeModal: Function,
    roomId: Number
})

const uploadDocumentFeedback = ref("")
const files = ref([])
const room_files = ref(null)
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

const storeFile = (file) => {
    roomFileForm.file = file
    roomFileForm.post(route('room_files.store', props.roomId))
    roomFileForm.file = null
    files.value = []
}

const validateType = (newFiles) => {
    uploadDocumentFeedback.value = "";
    const forbiddenTypes = [
        "application/vnd.microsoft.portable-executable",
        "application/x-apple-diskimage",
    ]
    for (let file of newFiles) {
        if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
            uploadDocumentFeedback.value = this.$t('Videos, .exe and .dmg files are not supported')
        } else {
            const fileSize = file.size;
            if (fileSize > 2097152) {
                uploadDocumentFeedback.value = this.$t('Files larger than 2MB cannot be uploaded.')
            } else {
                files.value.push(file)
            }
        }
    }
}

const storeFiles = () => {
    for (let file of files.value) {
        storeFile(file)
    }
    props.closeModal()
}

</script>

<style scoped>

</style>
