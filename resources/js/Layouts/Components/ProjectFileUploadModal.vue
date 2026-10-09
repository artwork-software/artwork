<template>
    <ArtworkBaseModal @close="closeModal" v-if="show"  :title="$t('Upload document')"
                      :description="$t('Upload documents that relate exclusively to the budget. These can only be viewed by users with the appropriate authorization.')">
            <div class="">
                <form @submit.prevent="storeFiles" class="grid grid-cols-1 gap-4">
                    <div>
                        <input
                            @change="upload"
                            class="hidden"
                            ref="module_files"
                            id="file"
                            type="file"
                            multiple
                        />
                        <div @click="selectNewFiles" @dragover.prevent @drop.stop.prevent="uploadDraggedDocuments($event)" class="w-full flex rounded-lg justify-center items-center border-accent-600 border-dotted border-2 h-32  p-2 cursor-pointer">
                            <p class="text-accent-600 font-bold text-center">
                                {{$t('Drag document here to upload or click in the field')}}
                            </p>
                        </div>
                        <jet-input-error :message="uploadDocumentFeedback"/>
                    </div>
                    <div class="">
                        <div v-for="file of files">{{ file.name }}</div>
                    </div>
                    <div class="">
                        <BaseTextarea
                            :label="$t('Comment / Note')"
                            id="description"
                            v-model="comment"
                            rows="4"
                        />
                    </div>
                    <div>
                        <div>
                            <UserSearch
                                v-model="user_query"
                                @userSelected="addUserToFileUserArray"
                                :label="$t('Document access for') + '*'"
                            />
                        </div>
                        <div v-if="usersWithAccess.length > 0" class="mt-2 mb-4 flex items-center">
                            <div v-for="(user,index) in usersWithAccess" class="flex mr-5 rounded-full items-center font-bold text-text">
                                <div class="flex items-center">
                                    <img class="flex h-11 w-11 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                                    <span class="flex ml-4 text-base/5 font-semibold text-text">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                                    <button type="button" @click="deleteUserFromFileUserArray(index)">
                                        <span class="sr-only">{{ $t('Remove user from contract')}}</span>
                                        <IconX class="ml-2 h-4 w-4 p-0.5 hover:text-danger rounded-full text-text border-0 "/>
                                    </button>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <div class="justify-end flex w-full my-6">
                        <BaseUIButton
                            :label="$t('Upload document')"
                            :disabled="files.length < 1 || isUploading"
                            type="submit"
                            is-add-button
                        />
                    </div>
                </form>
            </div>
    </ArtworkBaseModal>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import axios from "axios";
import {uploadErrorMessage} from "@/Composeables/UseUploadErrorMessage.js";
import {uploadSequentially} from "@/Helper/sequentialUpload.js";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: "ProjectFileUploadModal",
    // saved: ein Upload ist abgeschlossen (Aufrufer laden ihre Liste neu)
    emits: ['saved'],
    mixins: [Permissions],
    props: {
        show: Boolean,
        closeModal: Function,
        projectId: Number,
        budgetAccess: Array
    },
    components: {
        BaseUIButton,
        BaseTextarea,
        ArtworkBaseModal,
        ModalHeader,
        UserSearch,
        TextareaComponent,
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
            user_query: '',
            user_search_results: [],
            usersWithAccess: [],
            isUploading: false,
        }
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data.filter(user => this.budgetAccess.some(budgetAccess => budgetAccess.id === user.id))
                    })
                }
            },
            deep: true
        },
    },
    methods: {
        addUserToFileUserArray(user) {
            if (!this.usersWithAccess.find(userToAdd => userToAdd.id === user.id)) {
                this.usersWithAccess.push(user);
            }
            this.user_query = '';
        },
        deleteUserFromFileUserArray(index) {
            this.usersWithAccess.splice(index, 1);
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
        /**
         * Eine Datei hochladen (Promise). project_files.store antwortet ohne Inertia-Seite – wie die
         * Dokumente-Komponenten per axios statt useForm.
         */
        storeFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            if (this.comment) {
                formData.append('comment', this.comment);
            }
            this.usersWithAccess.forEach((user) => formData.append('accessibleUsers[]', String(user.id)));
            // Budget-Dokument: nur für die Freigabeliste und Admins sichtbar (auch in "Alle Dokumente")
            formData.append('budgetDocument', '1');

            return axios.post(this.route('project_files.store', this.projectId), formData, {
                headers: {'Content-Type': 'multipart/form-data'},
                // Fehler zeigt das Modal je Datei an – kein zusätzlicher globaler Toast
                skipErrorToast: true,
            });
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            for (let file of files) {
              this.files.push(file)
            }
        },
        /**
         * Nacheinander hochladen (parallele Posts brachen sich ab), danach die Liste leeren – erneutes Absenden
         * legte sonst alles doppelt an. Fehlgeschlagene Dateien bleiben mit Meldung stehen, das Modal bleibt offen.
         */
        async storeFiles() {
            if (this.isUploading || this.files.length < 1) {
                return;
            }
            this.isUploading = true;
            this.uploadDocumentFeedback = '';

            const { uploaded, failed, skipped } = await uploadSequentially(
                [...this.files],
                (file) => this.storeFile(file),
                // 413: Server lehnt die Größe ab – die übrigen Dateien nicht mehr versuchen
                { stopOnError: (error) => error?.response?.status === 413 }
            );

            this.isUploading = false;
            this.files = [...failed.map(({ file }) => file), ...skipped];
            if (uploaded.length > 0) {
                this.$emit('saved');
            }

            if (failed.length > 0) {
                const translate = (key, params) => this.$t(key, params);
                this.uploadDocumentFeedback = failed
                    .map(({ file, error }) => `${file.name}: ${uploadErrorMessage(error, translate)}`)
                    .join(' ');
                return;
            }

            this.comment = '';
            this.usersWithAccess = [];
            this.closeModal();
        }
    }
}
</script>

<style scoped>

</style>
