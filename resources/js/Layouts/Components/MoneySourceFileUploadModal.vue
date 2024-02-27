<template>
    <jet-dialog-modal :show="show" @close="resetModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_money_source_create.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Upload document')}}
                </div>
                <XIcon @click="resetModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary text-sm my-6">
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
                        border-buttonBlue border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-buttonBlue font-bold text-center">{{ $t('Drag document here to upload or click in the field')}}
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div>
                <textarea :placeholder="$t('Comment / Note')"
                          id="description"
                          v-model="comment"
                          rows="4"
                          class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <div class="mb-6">
                    <div v-for="file of files">{{ file.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <AddButton :text="$t('Upload document')" mode="modal" class="px-6 py-3" :disabled="files.length < 1"
                               @click="storeFiles"/>
                </div>
            </div>

        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import Permissions from "@/mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "MoneySourceFileUploadModal",
    props: {
        show: Boolean,
        closeModal: Function,
        moneySourceId: Number
    },
    components: {
        JetDialogModal,
        JetInputError,
        AddButton,
        XIcon
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            comment: "",
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
            this.moneySourceFileForm.post(this.route('money_sources_files.store', this.moneySourceId))
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = this.$t('Videos, .exe and .dmg files are not supported')
                } else {
                    const fileSize = file.size;
                    if (fileSize > 2097152) {
                        this.uploadDocumentFeedback = this.$t('Files larger than 2MB cannot be uploaded.')
                    } else {
                        this.files.push(file)
                    }
                }

            }
        },
        resetModal() {
            this.files = [];
            this.closeModal()
        },
        storeFiles() {
            for (let file of this.files) {
                this.storeFile(file)
            }
            this.files = []
            this.comment = null
            this.closeModal()
        }
    }
}
</script>

<style scoped>

</style>
