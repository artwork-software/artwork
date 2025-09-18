<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_new_contract_module.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Upload contract module')}}
                </div>
                <div class="text-secondary text-sm my-6">
                    {{ $t('Upload contract modules. Any user with authorization to view contracts can then download and use them for contract design.')}}
                </div>
                <div class="mb-3">
                    <MultiAlertComponent :errors="contractModuleForm.errors" v-show="Object.keys(contractModuleForm.errors).length > 0"  :error-count="Object.keys(contractModuleForm.errors).length" />
                </div>
                <div class="grid grid-cols-1 gap-4">
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
                             @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex rounded-lg justify-center items-center
                        border-artwork-buttons-create border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                            <p class="text-artwork-buttons-create font-bold text-center">
                                {{ $t('Drag document here to upload or click in the field') }}
                            </p>
                        </div>
                        <jet-input-error :message="uploadDocumentFeedback"/>
                    </div>
                    <div>
                        <BaseTextarea
                            :label="$t('Comment / Note')"
                            id="description"
                            v-model="contractModuleForm.description"
                            rows="4"
                        />
                    </div>
                </div>
                <div class="mb-6">
                    <div v-for="file of files">
                        <div class="flex items-center justify-between">
                            <div>
                                {{ file.name }}
                            </div>
                            <div>
                                <component :is="IconCircleX" class="size-5 text-error cursor-pointer hover:text-artwork-buttons-hover transition-colors duration-300 ease-in-out" @click="files.splice(files.indexOf(file), 1)"/>
                            </div>
                        </div>
                    </div>
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
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import MultiAlertComponent from "@/Components/Alerts/MultiAlertComponent.vue";
import {useForm} from "@inertiajs/vue3";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import {IconCircleX} from "@tabler/icons-vue";

export default {
    name: "ContractModuleUploadModal",
    mixins: [Permissions, IconLib],
    props: {
        show: Boolean,
        closeModal: Function
    },
    components: {
        BaseTextarea,
        MultiAlertComponent,
        TextareaComponent,
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
            description: "",
            contractModuleForm: useForm({
                file: null,
                description: ""
            }),
            errorsAtUpload: [],
            closeModalIfUploaded: false
        }
    },
    methods: {
        IconCircleX,
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
            this.contractModuleForm.file = file;
            this.contractModuleForm.post(route('contracts.module.store'), {
                onSuccess: () => {
                    this.contractModuleForm.file = null;
                    this.files.splice(this.files.indexOf(file), 1);
                },
                onError: () => {
                    this.errorsAtUpload.push(this.contractModuleForm.errors);
                }
            })


        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            for (let file of files) {
              this.files.push(file)
            }
        },
        storeFiles() {
            this.errorsAtUpload = [];
            for (let file of this.files) {
                this.storeFile(file)
            }

            if(!this.errorsAtUpload.length > 0) {
                this.closeModalIfUploaded = true;
                this.closeModal();
            }
        }
    }
}
</script>

<style scoped>

</style>
