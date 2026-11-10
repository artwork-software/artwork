<template>
    <ArtworkBaseModal
        v-if="show"
        title="Edit document"
        modal-size="sm:max-w-xl"
        @close="cancel"
    >
        <form id="moneySourceFileEditForm" class="space-y-4" @submit.prevent="updateFile">
            <div class="flex items-center justify-between gap-x-3 rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                <div class="flex min-w-0 items-center gap-x-2 text-sm text-text">
                    <PropertyIcon name="IconFile" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                    <span class="truncate">{{ file.name }}</span>
                </div>
                <BaseUIButton
                    label="Download"
                    variant="ghost"
                    size="sm"
                    icon="IconDownload"
                    @click="downloadMoneySourceFile(file)"
                />
            </div>

            <div>
                <div class="mb-1 font-lexend text-xs font-medium text-text-muted">
                    {{ $t('Replace document') }}
                </div>
                <input
                    id="moneySourceFileEditInput"
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

            <ul v-if="files.length > 0" class="divide-y divide-border-subtle rounded-lg border border-border-subtle">
                <li
                    v-for="(newFile, index) in files"
                    :key="newFile.name + '-' + index"
                    class="flex items-center justify-between gap-x-3 px-3 py-2 text-sm text-text"
                >
                    <div class="flex min-w-0 items-center gap-x-2">
                        <PropertyIcon name="IconFile" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" aria-hidden="true"/>
                        <span class="truncate">
                            <span class="text-text-muted">{{ $t('New document') }}:</span> {{ newFile.name }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-hover hover:text-danger disabled:cursor-default disabled:text-text-subtle"
                        :aria-label="$t('Remove file')"
                        :disabled="processing"
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
                id="moneySourceFileEditComment"
                v-model="comment"
                label="Comment / Note"
                :rows="4"
            />

            <div v-if="file.comments?.length > 0" class="border-t border-border-subtle pt-4">
                <div class="mb-2 font-lexend text-xs font-medium text-text-muted">
                    {{ $t('Comments') }}
                </div>
                <ul class="space-y-3">
                    <li v-for="existingComment in file.comments" :key="existingComment.id">
                        <div class="flex items-center gap-x-2 text-xs text-text-subtle">
                            <img
                                :src="existingComment.user.profile_photo_url"
                                alt=""
                                class="size-5 rounded-full object-cover"
                            />
                            {{ existingComment.created_at }}
                        </div>
                        <div class="mt-1 text-sm text-text">
                            {{ existingComment.text }}
                        </div>
                    </li>
                </ul>
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="cancel"
            />
            <BaseUIButton
                type="submit"
                form="moneySourceFileEditForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    mixins: [Permissions],
    name: "MoneySourceFileEditModal",
    props: {
        // Der Aufrufer steuert per v-if und reicht show nicht durch, daher standardmäßig sichtbar
        show: {type: Boolean, default: true},
        closeModal: Function,
        projectId: Number,
        file: Object
    },
    components: {
        ArtworkBaseModal,
        BaseUIButton,
        BaseTextarea,
        BaseAlertComponent,
        PropertyIcon,
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            comment: "",
            isDragging: false,
            processing: false,
            moneySourceFileForm: useForm({
                file: null,
                comment: "",
            })
        }
    },
    computed: {
        canSubmit() {
            return !this.processing && (this.files.length > 0 || !!this.comment?.trim())
        },
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
        validateType(files) {
            this.uploadDocumentFeedback = "";
            // Ersetzt wird genau ein Dokument, eine neue Auswahl löst die vorige ab
            if (files.length > 0) {
                this.files = [files[0]]
            }
        },
        cancel() {
            if (this.processing) {
                return
            }
            this.closeModal()
        },
        updateFile() {
            if (!this.canSubmit) {
                return
            }
            this.processing = true
            this.uploadDocumentFeedback = ''
            this.moneySourceFileForm.file = this.files[0] ?? null
            this.moneySourceFileForm.comment = this.comment
            this.moneySourceFileForm.post(this.route('money_sources_files.update', {money_source_file: this.file}), {
                preserveScroll: true,
                onSuccess: () => {
                    this.files = []
                    this.comment = ''
                    this.closeModal()
                },
                onError: (errors) => {
                    this.uploadDocumentFeedback = errors.file ?? Object.values(errors)[0] ?? ''
                },
                onFinish: () => {
                    this.processing = false
                },
            })
        }
    }
}
</script>
