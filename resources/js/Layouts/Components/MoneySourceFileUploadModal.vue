<template>
    <BaseModal @closed="resetModal" v-if="show" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-4">
                <div class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text my-2">
                    {{$t('Upload document')}}
                </div>
                <div class="text-text-subtle text-sm my-6">
                    {{$t('Upload documents that relate exclusively to the funding source.')}}
                </div>
                <div>
                    <input
                        @change="upload"
                        class="hidden"
                        ref="module_files"
                        id="file"
                        type="file"
                        multiple
                    />
                    <div @click="selectNewFiles" @dragover.prevent
                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-accent-600 border-dotted border-2 h-32  p-2 cursor-pointer">
                        <p class="text-accent-600 font-bold text-center">{{ $t('Drag document here to upload or click in the field')}}
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div>
                <textarea :placeholder="$t('Comment / Note')"
                          id="description"
                          v-model="comment"
                          rows="4"
                          class="border border-border resize-none w-full text-sm/5 font-semibold text-text placeholder:text-sm/5 font-bold text-text-subtle placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-text-subtle focus:border-1 w-full border-border"/>
                </div>
                <div class="mb-6">
                    <div v-for="file of files">{{ file.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <FormButton
                        :text="$t('Upload document')"
                        :disabled="files.length < 1 || isUploading"
                        @click="storeFiles"
                    />
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {router, useForm} from "@inertiajs/vue3";
import {
    inertiaUploadErrorMessage,
    isInvalidResponse,
    submitInertiaForm,
    uploadSequentially,
} from "@/Helper/sequentialUpload.js";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "MoneySourceFileUploadModal",
    props: {
        show: Boolean,
        closeModal: Function,
        moneySourceId: Number
    },
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        JetInputError,
        IconX
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            comment: "",
            isUploading: false,
            moneySourceFileForm: useForm({
                file: null,
                comment: this.comment,
            })
        }
    },
    methods: {
        selectNewFiles() {
            this.$refs.module_files.click();
        },
        uploadDraggedDocuments(event) {
            this.validateType([...event.dataTransfer.files])
        },
        upload(event) {
            this.validateType([...event.target.files])
        },
        storeFile(file) {
            this.moneySourceFileForm.file = file
            this.moneySourceFileForm.comment = this.comment

            return submitInertiaForm(
                this.moneySourceFileForm,
                'post',
                this.route('money_sources_files.store', this.moneySourceId),
                { preserveState: true, preserveScroll: true },
                { router }
            )
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            for (let file of files) {
              this.files.push(file)
            }
        },
        resetModal() {
            this.files = [];
            this.closeModal()
        },
        /**
         * Nacheinander hochladen: mehrere .post() derselben useForm brachen sich gegenseitig ab, praktisch kam nur
         * die letzte Datei an. Fehlgeschlagene Dateien bleiben mit Meldung stehen.
         */
        async storeFiles() {
            // Doppelklick: eine zweite Kette über dieselbe useForm bräche die erste ab
            if (this.isUploading || this.files.length < 1) {
                return
            }
            this.isUploading = true
            this.uploadDocumentFeedback = ''
            const { failed, skipped } = await uploadSequentially(
                [...this.files],
                (file) => this.storeFile(file),
                { stopOnError: isInvalidResponse }
            )

            this.isUploading = false
            this.moneySourceFileForm.file = null
            this.files = [...failed.map(({ file }) => file), ...skipped]
            if (failed.length > 0) {
                const translate = (key, params) => this.$t(key, params)
                this.uploadDocumentFeedback = failed
                    .map(({ file, error }) => `${file.name}: ${inertiaUploadErrorMessage(error, translate)}`)
                    .join(' ')
                return
            }

            this.comment = null
            this.closeModal()
        }
    }
}
</script>

<style scoped>

</style>
