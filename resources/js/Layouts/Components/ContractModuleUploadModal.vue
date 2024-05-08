<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_new_contract_module.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Upload contract module')}}
                </div>
                <div class="text-secondary text-sm my-6">
                    {{ $t('Upload contract modules. Any user with authorization to view contracts can then download and use them for contract design.')}}
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
                        border-artwork-buttons-create border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-artwork-buttons-create font-bold text-center">
                           {{ $t('Drag document here to upload or click in the field') }}
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div>
                <textarea :placeholder="$t('Comment / Note')"
                          id="description"
                          v-model="description"
                          rows="4"
                          class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <div class="mb-6">
                    <div v-for="file of files">{{ file.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <FormButton
                        :text="$t('Upload document')"
                        :disabled="files.length < 1"
                        @click="storeFiles"
                    />
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: "ContractModuleUploadModal",
    mixins: [Permissions, IconLib],
    props: {
        show: Boolean,
        closeModal: Function
    },
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        JetInputError,
        XIcon
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            description: ""
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
            this.$inertia.post('/contract_modules', {module: file}, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit('upload')
                }

            })
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
                } else {
                    const fileSize = file.size;
                    if (fileSize > 2097152) {
                        this.uploadDocumentFeedback = "Dateien, welche größer als 2MB sind, können nicht hochgeladen werden."
                    } else {
                        this.files.push(file)
                    }

                }
            }
        },
        storeFiles() {
            for (let file of this.files) {
                this.storeFile(file)
            }
            this.closeModal()
        }
    }
}
</script>

<style scoped>

</style>
