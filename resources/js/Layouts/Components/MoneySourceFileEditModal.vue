<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_money_source_create.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Dokument bearbeiten
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="flex items-center cursor-pointer" @click="downloadMoneySourceFile(file)">
                    <DownloadIcon class="w-4 h-4 mr-2 text-buttonBlue"/>
                    <div class="text-buttonBlue text-sm my-6">{{ file.name }}</div>
                </div>
                <div class="text-secondary text-sm my-2">
                    Dokument ersetzen
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
                        border-buttonBlue border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-buttonBlue font-bold text-center">Dokument zum Upload hierher ziehen
                            <br>oder ins Feld klicken
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div>
                <textarea placeholder="Kommentar / Notiz"
                          id="description"
                          v-model="comment"
                          rows="4"
                          class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <div class="mb-6">
                    <div v-for="file in files">Neues Dokument: {{ file?.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <AddButton text="Speichern" mode="modal" class="px-6 py-3"
                               @click="updateFile"/>
                </div>
                <div class="w-full my-4">
                    <div v-for="comment in file.comments">
                        <div class="flex items-center">
                            <img :src="comment.user.profile_photo_url" alt="profile_photo"
                                 class="h-5 w-5 mr-2 rounded-2xl"/>
                            <div class="text-secondary text-sm">{{comment.created_at}}</div>
                        </div>
                        <div class="mt-2 mb-4">
                            {{comment.text}}
                        </div>
                    </div>
                </div>
            </div>

        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon, DownloadIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import Permissions from "@/mixins/Permissions.vue";

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
        JetDialogModal,
        JetInputError,
        AddButton,
        XIcon,
        DownloadIcon
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
