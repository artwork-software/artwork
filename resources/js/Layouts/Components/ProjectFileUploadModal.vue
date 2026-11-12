<template>
    <ArtworkBaseModal
        v-if="show"
        title="Upload document"
        description="Upload documents that relate exclusively to the budget. These can only be viewed by users with the appropriate authorization."
        modal-size="sm:max-w-xl"
        @close="resetModal"
    >
        <form id="projectFileUploadForm" class="space-y-4" @submit.prevent="storeFiles">
            <div>
                <input
                    id="projectFileUploadInput"
                    ref="module_files"
                    type="file"
                    class="sr-only"
                    multiple
                    @change="upload"
                />
                <div
                    role="button"
                    tabindex="0"
                    class="flex min-h-28 w-full cursor-pointer flex-col items-center justify-center gap-y-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    :class="isDragging ? 'border-accent-600 bg-accent-50' : 'border-border'"
                    @click="selectNewFiles"
                    @keydown.enter.prevent="selectNewFiles"
                    @keydown.space.prevent="selectNewFiles"
                    @dragenter.prevent="isDragging = true"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = $event.currentTarget.contains($event.relatedTarget)"
                    @drop.stop.prevent="uploadDraggedDocuments($event)"
                >
                    <PropertyIcon name="IconUpload" class="size-6 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                    <p class="text-sm text-text-muted">
                        <template v-if="isDragging">{{ $t('Drop here') }}</template>
                        <template v-else>
                            <span class="font-semibold text-accent-600">{{ $t('Choose file') }}</span>
                            {{ $t('or drag and drop') }}
                        </template>
                    </p>
                </div>
            </div>

            <ul v-if="files.length > 0" class="divide-y divide-border-subtle rounded-lg border border-border-subtle">
                <li
                    v-for="(file, index) in files"
                    :key="file.name + '-' + index"
                    class="flex items-center justify-between gap-x-3 px-3 py-2 text-sm text-text"
                >
                    <div class="flex min-w-0 items-center gap-x-2">
                        <PropertyIcon name="IconFile" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                        <span class="truncate">{{ file.name }}</span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger disabled:cursor-default disabled:text-text-subtle"
                        :aria-label="$t('Remove file')"
                        :disabled="isUploading"
                        @click="removeFile(index)"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </li>
            </ul>

            <BaseAlertComponent
                v-if="uploadDocumentFeedback"
                type="error"
                class="mb-0!"
                :message="uploadDocumentFeedback"
            />

            <BaseTextarea
                id="projectFileUploadComment"
                v-model="comment"
                label="Comment / Note"
                :rows="4"
            />

            <div>
                <BaseInput
                    id="projectFileUploadUserSearch"
                    v-model="user_query"
                    label="Document access for"
                    :placeholder="$t('Search for users')"
                    autocomplete="off"
                />
                <ul
                    v-if="user_search_results.length > 0 && user_query.length > 0"
                    class="mt-2 max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                >
                    <li v-for="user in user_search_results" :key="user.id">
                        <button
                            type="button"
                            class="block w-full cursor-pointer truncate px-3 py-2 text-left text-sm font-medium text-text transition-colors duration-150 hover:bg-surface-hover focus-visible:bg-surface-hover motion-reduce:transition-none"
                            @click="addUserToFileUserArray(user)"
                        >
                            {{ user.first_name }} {{ user.last_name }}
                        </button>
                    </li>
                </ul>
            </div>

            <ul
                v-if="usersWithAccess.length > 0"
                class="max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
            >
                <li
                    v-for="(user, index) in usersWithAccess"
                    :key="user.id"
                    class="flex items-center justify-between gap-x-3 px-3 py-2"
                >
                    <div class="flex min-w-0 items-center gap-x-3">
                        <UserPopoverTooltip :id="`project-file-upload-user-${user.id}`" :user="user" height="8" width="8"/>
                        <span class="truncate text-sm font-medium text-text">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                        :aria-label="$t('Remove user')"
                        :disabled="isUploading"
                        @click="deleteUserFromFileUserArray(index)"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </li>
            </ul>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="isUploading"
                @click="resetModal"
            />
            <BaseUIButton
                type="submit"
                form="projectFileUploadForm"
                label="Upload document"
                variant="primary"
                icon="IconUpload"
                :processing="isUploading"
                :disabled="files.length < 1 || isUploading"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import axios from "axios";
import {uploadErrorMessage} from "@/Composeables/UseUploadErrorMessage.js";
import {uploadSequentially} from "@/Helper/sequentialUpload.js";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";

export default {
    name: "ProjectFileUploadModal",
    // saved: ein Upload ist abgeschlossen (Aufrufer laden ihre Liste neu)
    emits: ['saved'],
    props: {
        show: Boolean,
        closeModal: Function,
        projectId: Number,
        budgetAccess: Array
    },
    components: {
        ArtworkBaseModal,
        BaseInput,
        BaseTextarea,
        BaseUIButton,
        BaseAlertComponent,
        PropertyIcon,
        UserPopoverTooltip,
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
            isDragging: false,
        }
    },
    watch: {
        // Budget-Dokument: freigeben lassen sich nur Personen mit Budgetzugriff
        user_query() {
            if (this.user_query.length === 0) {
                this.user_search_results = [];
                return;
            }
            const query = this.user_query;
            axios.get('/users/search', {
                params: {query}
            }).then(response => {
                // Veraltete Antworten (schnelles Tippen) nicht über neuere schreiben
                if (query !== this.user_query) {
                    return;
                }
                const budgetAccessIds = (this.budgetAccess ?? []).map((budgetUser) => budgetUser.id);
                this.user_search_results = response.data.filter((user) => budgetAccessIds.includes(user.id));
            });
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
            this.isDragging = false
            this.validateType([...event.dataTransfer.files])
        },
        upload(event) {
            this.validateType([...event.target.files])
            // Gleiche Datei erneut wählbar machen
            event.target.value = ''
        },
        removeFile(index) {
            this.files.splice(index, 1)
        },
        resetModal() {
            if (this.isUploading) {
                return
            }
            this.files = []
            this.user_query = ''
            this.uploadDocumentFeedback = ''
            this.closeModal()
        },
        /**
         * Eine Datei hochladen (Promise). project_files.store antwortet ohne Inertia-Seite, daher wie die
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
                // Fehler zeigt das Modal je Datei an, kein zusätzlicher globaler Toast
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
         * Nacheinander hochladen (parallele Posts brachen sich ab), danach die Liste leeren: erneutes Absenden
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
                // 413: Server lehnt die Größe ab, die übrigen Dateien nicht mehr versuchen
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

