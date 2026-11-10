<template>
    <ArtworkBaseModal
        v-if="show"
        title="Edit document"
        description="Replace document"
        modal-size="sm:max-w-xl"
        @close="close"
    >
        <form id="projectFileEditForm" class="space-y-4" @submit.prevent="updateFile">
            <button
                v-if="file"
                type="button"
                class="flex w-full cursor-pointer items-center gap-x-2 rounded-md border border-border-subtle bg-surface-sunken px-3 py-2 text-left text-sm font-semibold text-text transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                :aria-label="$t('Download')"
                @click="downloadProjectFile(file)"
            >
                <PropertyIcon name="IconDownload" class="size-4 shrink-0 text-accent-600" stroke-width="1.5" aria-hidden="true"/>
                <span class="truncate">{{ file.name }}</span>
            </button>

            <div>
                <input
                    id="projectFileEditInput"
                    ref="module_files"
                    type="file"
                    class="sr-only"
                    @change="upload"
                />
                <div
                    role="button"
                    tabindex="0"
                    class="flex min-h-28 w-full cursor-pointer flex-col items-center justify-center gap-y-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                    :class="isDragging ? 'border-accent-600 bg-accent-50' : 'border-border'"
                    @click="selectNewFile"
                    @keydown.enter.prevent="selectNewFile"
                    @keydown.space.prevent="selectNewFile"
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

            <div
                v-if="files.length > 0"
                class="flex items-center justify-between gap-x-3 rounded-lg border border-border-subtle px-3 py-2 text-sm text-text"
            >
                <div class="flex min-w-0 items-center gap-x-2">
                    <PropertyIcon name="IconFile" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                    <span class="truncate">{{ $t('New document') }}: {{ files[0].name }}</span>
                </div>
                <button
                    type="button"
                    class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger disabled:cursor-default disabled:text-text-subtle"
                    :aria-label="$t('Remove file')"
                    :disabled="projectFileForm.processing"
                    @click="files = []"
                >
                    <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                </button>
            </div>

            <BaseAlertComponent
                v-if="uploadDocumentFeedback"
                type="error"
                class="mb-0!"
                :message="uploadDocumentFeedback"
            />

            <BaseTextarea
                id="projectFileEditComment"
                v-model="comment"
                label="Comment / Note"
                :rows="4"
            />

            <div>
                <BaseInput
                    id="projectFileEditUserSearch"
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
                        <UserPopoverTooltip :id="`project-file-edit-user-${user.id}`" :user="user" height="8" width="8"/>
                        <span class="truncate text-sm font-medium text-text">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                        :aria-label="$t('Remove user')"
                        @click="deleteUserFromFileUserArray(index)"
                    >
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </li>
            </ul>

            <div v-if="file?.comments?.length > 0" class="space-y-3 border-t border-border-subtle pt-4">
                <h3 class="font-lexend text-xs font-medium text-text-muted">{{ $t('Previous comments') }}</h3>
                <div v-for="(fileComment, index) in file.comments" :key="fileComment.id ?? index" class="space-y-1">
                    <div class="flex items-center gap-x-2">
                        <UserPopoverTooltip :id="`project-file-comment-${index}`" :user="fileComment.user" height="5" width="5"/>
                        <span class="text-xs text-text-muted tabular-nums">{{ fileComment.created_at }}</span>
                    </div>
                    <p class="text-sm text-text">{{ fileComment.text }}</p>
                </div>
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="projectFileForm.processing"
                @click="close"
            />
            <BaseUIButton
                type="submit"
                form="projectFileEditForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="projectFileForm.processing"
                :disabled="!file?.id"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import axios from "axios";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {sharedUsersOf} from "@/Helper/sharedAccess.js";

export default {
    name: "ProjectFileEditModal",
    // saved: Speichern ist abgeschlossen (Aufrufer laden ihre Liste neu)
    emits: ['saved'],
    props: {
        show: Boolean,
        closeModal: Function,
        projectId: Number,
        file: Object
    },
    components: {
        ArtworkBaseModal,
        BaseUIButton,
        BaseInput,
        BaseTextarea,
        BaseAlertComponent,
        PropertyIcon,
        UserPopoverTooltip,
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            comment: "",
            isDragging: false,
            user_query: '',
            user_search_results: [],
            // Bestehende Freigabeliste vorbelegen (Kopie): leer gespeichert entzöge sie allen anderen den Zugriff
            usersWithAccess: sharedUsersOf(this.file),
            projectFileForm: useForm({
                file: null,
                comment: "",
                accessibleUsers: [],
                // Mit neuer Datei geht das Formular als FormData raus und ein leeres Array fiele weg. Der Marker
                // sagt dem Backend, dass die (ggf. leere) Freigabeliste übernommen werden soll
                accessibleUsersSent: true,
            })
        }
    },
    watch: {
        // Das Modal bleibt eingehängt und bekommt die Datei erst beim Öffnen: Liste dann neu vorbelegen
        file(newFile) {
            this.usersWithAccess = sharedUsersOf(newFile);
        },
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
                if (query === this.user_query) {
                    this.user_search_results = response.data;
                }
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
        downloadProjectFile(file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: file});
            link.target = '_blank';
            link.click();
        },
        selectNewFile() {
            this.$refs.module_files.click();
        },
        uploadDraggedDocuments(event) {
            this.isDragging = false;
            this.validateType([...event.dataTransfer.files]);
        },
        upload(event) {
            this.validateType([...event.target.files]);
            // Gleiche Datei erneut wählbar machen
            event.target.value = '';
        },
        // Ersetzt wird genau ein Dokument: eine neue Auswahl ersetzt die vorherige
        validateType(files) {
            this.uploadDocumentFeedback = "";
            if (files.length > 0) {
                this.files = [files[0]];
            }
        },
        resetState() {
            this.files = [];
            this.comment = "";
            this.user_query = '';
            this.uploadDocumentFeedback = "";
            this.isDragging = false;
        },
        close() {
            if (this.projectFileForm.processing) {
                return;
            }
            this.resetState();
            // Ungespeicherte Änderungen an der Freigabeliste verwerfen
            this.usersWithAccess = sharedUsersOf(this.file);
            this.closeModal();
        },
        updateFile() {
            if (!this.file?.id || this.projectFileForm.processing) {
                return;
            }
            this.uploadDocumentFeedback = "";
            this.projectFileForm.file = this.files[0] ?? null;
            this.projectFileForm.comment = this.comment;
            this.projectFileForm.accessibleUsers = this.usersWithAccess.map((user) => user.id);
            this.projectFileForm.accessibleUsersSent = true;
            this.projectFileForm.post(this.route('project_files.update', this.file.id), {
                preserveScroll: true,
                onSuccess: () => {
                    this.resetState();
                    this.closeModal();
                },
                onError: (errors) => {
                    this.uploadDocumentFeedback = Object.values(errors).flat().find(Boolean) ?? this.$t('Upload failed');
                },
                onFinish: () => {
                    this.projectFileForm.file = null;
                    this.$emit('saved');
                },
            });
        }
    }
}
</script>
