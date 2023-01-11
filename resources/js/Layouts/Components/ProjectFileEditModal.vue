<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Dokument bearbeiten
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="flex items-center cursor-pointer" @click="downloadProjectFile(file)">
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
                        multiple
                    />
                    <div @click="selectNewFiles" @dragover.prevent
                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-secondary border-dotted border-2 h-32 bg-stone-100 p-2 cursor-pointer">
                        <p class="text-secondary text-center">Ziehe das Dokument hier her
                            <br>oder klicke ins Feld
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
                    <div v-for="file of files">Neues Dokument: {{ file.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <AddButton text="Speichern" mode="modal" class="px-6 py-3" :disabled="files.length < 1"
                               @click="updateFile"/>
                </div>
                <div class="w-full my-4">
                    <div v-for="comment in file.comments">
                        <div class="flex items-center">
                            <img :src="comment.user.profile_photo_url"  alt="profile_photo" class="h-5 w-5 mr-2 rounded-2xl"/>
                            <div class="text-secondary text-sm">{{comment.created_at}}</div>
                        </div>
                        <div class="mt-2">
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
import JetInputError from '@/Jetstream/DialogModal.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon, DownloadIcon} from "@heroicons/vue/outline";

export default {
    name: "ProjectFileEditModal",
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
            comment: ""
        }
    },
    methods: {
        downloadProjectFile(file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: file});
            link.target = '_blank';
            link.click();
        },
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
            this.$inertia.post(`/projects/${this.projectId}/files`, {file: file, comment: this.comment}, {
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
                    this.files.push(file)
                }
            }
        },
        updateFile() {
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
