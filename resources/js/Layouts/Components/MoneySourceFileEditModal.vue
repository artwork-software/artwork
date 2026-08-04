<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-4">
                <div class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text my-2">
                    {{$t('Edit document')}}
                </div>
                <div class="flex items-center cursor-pointer" @click="downloadMoneySourceFile(file)">
                    <IconDownload class="w-4 h-4 mr-2 text-accent-600"/>
                    <div class="text-accent-600 text-sm my-6">{{ file.name }}</div>
                </div>
                <div class="text-text-subtle text-sm my-2">
                    {{ $t('Replace document')}}
                </div>
                <div>
                    <input
                        @change="upload"
                        class="hidden"
                        ref="module_files"
                        id="file"
                        type="file"
                    />
                    <div @click="selectNewFile" @dragover.prevent
                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-accent-600 border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
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
                    <div v-for="file in files">{{ $t('New document')}}: {{ file?.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <FormButton :text="$t('Save')"
                               @click="updateFile"/>
                </div>
                <div class="w-full my-4">
                    <div v-for="comment in file.comments">
                        <div class="flex items-center">
                            <img :src="comment.user.profile_photo_url" alt="profile_photo"
                                 class="h-5 w-5 mr-2 rounded-2xl"/>
                            <div class="text-text-subtle text-sm">{{comment.created_at}}</div>
                        </div>
                        <div class="mt-2 mb-4">
                            {{comment.text}}
                        </div>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {IconDownload, IconX} from "@tabler/icons-vue";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "MoneySourceFileEditModal",
    props: {
        show: Boolean,
        closeModal: Function,
        projectId: Number,
        file: Object
    },
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        JetInputError,
        IconX,
        IconDownload
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            comment: null,
            moneySourceFileForm: useForm({
                file: null,
                comment: this.comment,
            })
        }
    },
    methods: {
        downloadMoneySourceFile(file) {
            let link = document.createElement('a');
            link.href = route('money_sources_download_file', {money_source_file: file});
            link.target = '_blank';
            link.click();
        },
        selectNewFile() {
            this.$refs.module_files.click();
        },
        uploadDraggedDocuments(event) {
            this.validateType([...event.dataTransfer.files])
        },
        upload(event) {
            this.validateType([...event.target.files])
        },
        updateRequest(file) {
            this.moneySourceFileForm.file = file
            this.moneySourceFileForm.comment = this.comment
            this.moneySourceFileForm.post(this.route('money_sources_files.update', {money_source_file: this.file}))
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            for (let file of files) {
              this.files.push(file)
            }
        },
        updateFile() {
            this.updateRequest(this.files[0])
            this.files = []
            this.comment = null
            this.closeModal()
        }
    }
}
</script>

<style scoped>

</style>
