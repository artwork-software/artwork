<template>
    <ArtworkBaseModal
        v-if="show"
        title="Upload contract module"
        description="Upload contract modules. Any user with authorization to view contracts can then download and use them for contract design."
        modal-size="sm:max-w-xl"
        @close="cancel"
    >
        <form id="contractModuleUploadForm" class="space-y-4" @submit.prevent="storeFiles">
            <div>
                <input
                    id="contractModuleUploadInput"
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
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="isUploading"
                @click="cancel"
            />
            <BaseUIButton
                type="submit"
                form="contractModuleUploadForm"
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
import Permissions from "@/Mixins/Permissions.vue";
import {router, useForm} from "@inertiajs/vue3";
import {
    inertiaUploadErrorMessage,
    isInvalidResponse,
    submitInertiaForm,
    uploadSequentially,
} from "@/Helper/sequentialUpload.js";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "ContractModuleUploadModal",
    mixins: [Permissions],
    props: {
        show: Boolean,
        closeModal: Function
    },
    components: {
        ArtworkBaseModal,
        BaseUIButton,
        BaseAlertComponent,
        PropertyIcon,
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            contractModuleForm: useForm({
                file: null
            }),
            errorsAtUpload: [],
            closeModalIfUploaded: false,
            isUploading: false,
            isDragging: false,
        }
    },
    methods: {
        selectNewFiles() {
            this.$refs.module_files.click();
        },
        uploadDraggedDocuments(event) {
            this.isDragging = false;
            this.validateType([...event.dataTransfer.files])
        },
        upload(event) {
            this.validateType([...event.target.files])
            // Gleiche Datei erneut wählbar machen
            event.target.value = '';
        },
        removeFile(index) {
            this.files.splice(index, 1);
        },
        storeFile(file) {
            this.contractModuleForm.file = file;

            return submitInertiaForm(this.contractModuleForm, 'post', route('contracts.module.store'), {
                preserveState: true,
                preserveScroll: true,
            }, { router });
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            for (let file of files) {
              this.files.push(file)
            }
        },
        cancel() {
            if (this.isUploading) {
                return;
            }
            this.closeModal();
        },
        /**
         * Nacheinander hochladen: mehrere .post() derselben useForm brachen sich gegenseitig ab. Hochgeladene
         * Dateien verschwinden aus der Liste, fehlgeschlagene bleiben mit Meldung stehen.
         */
        async storeFiles() {
            // Doppelklick: eine zweite Kette über dieselbe useForm bräche die erste ab
            if (this.isUploading || this.files.length < 1) {
                return;
            }
            this.isUploading = true;
            this.errorsAtUpload = [];
            this.uploadDocumentFeedback = '';

            const { failed, skipped } = await uploadSequentially(
                [...this.files],
                (file) => this.storeFile(file),
                { stopOnError: isInvalidResponse }
            );

            this.isUploading = false;
            this.contractModuleForm.file = null;
            this.files = [...failed.map(({ file }) => file), ...skipped];
            this.errorsAtUpload = failed.map(({ error }) => error?.errors ?? {});

            if (failed.length > 0) {
                const translate = (key, params) => this.$t(key, params);
                this.uploadDocumentFeedback = failed
                    .map(({ file, error }) => `${file.name}: ${inertiaUploadErrorMessage(error, translate)}`)
                    .join(' ');
                return;
            }

            this.closeModalIfUploaded = true;
            this.closeModal();
        }
    }
}
</script>
