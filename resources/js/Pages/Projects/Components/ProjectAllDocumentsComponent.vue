<template>
    <div class="my-4">

        <div class="flex items-center justify-between">
            <TinyPageHeadline
                :title="$t('All Documents')"
                :description="$t('Here you can upload and download documents for the project.')"
            />
            <InfoButtonComponent :component="component" />
        </div>

        <div>
            <div class="mb-3 print:hidden" >
                <MultiAlertComponent :errors="documentForm.errors" v-show="Object.keys(documentForm.errors).length > 0" :error-count="Object.keys(documentForm.errors).length" />
            </div>

            <div v-if="this.canEditComponent || ($role('artwork admin') || projectWriteIds?.includes(this.$page.props.auth.user.id))" class="print:hidden">
                <div
                    @click="selectNewFiles"
                    @dragover.prevent
                    @drop.stop.prevent="uploadDraggedDocuments($event)"
                    class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center hover:border-gray-400 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <component :is="IconFileUpload" class="mx-auto size-12 text-gray-400" />
                    <span class="mt-2 block text-sm font-semibold text-gray-900">{{ $t('Drag document here to upload or click in the field') }}</span>
                </div>
                <input
                    @change="uploadChosenDocuments"
                    class="hidden"
                    ref="project_files"
                    id="file"
                    type="file"
                    multiple
                />
                <jet-input-error :message="uploadDocumentFeedback"/>
            </div>


            <div class="my-4">
                <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200"  v-if="documents.length > 0">
                    <li class="flex items-center justify-between py-4 pl-4 pr-5 text-sm/6 group" v-for="project_file in documents">
                        <div class="flex w-0 flex-1 items-center">
                            <component :is="IconFileText" class="size-5 shrink-0 text-gray-400" aria-hidden="true" />
                            <div class="ml-4 flex min-w-0 flex-1 gap-2">
                                <span class="truncate font-medium">{{ project_file.name }}</span>
                                <span class="shrink-0 text-gray-400">{{ project_file.file_size }}</span>
                            </div>
                        </div>
                        <div class="ml-4 shrink-0 flex items-center gap-x-4">
                            <div v-if="this.canEditComponent || ($role('artwork admin') || projectWriteIds?.includes(this.$page.props.auth.user.id) || projectManagerIds?.includes(this.$page.props.auth.user.id))"
                                 @click="openConfirmDeleteModal(project_file)"
                                 class="invisible group-hover:visible font-medium text-gray-900 hover:text-artwork-messages-error cursor-pointer">
                                {{ $t('Löschen') }}
                            </div>
                            <div @click="downloadFile(project_file)" class="font-medium text-gray-900 hover:text-artwork-buttons-hover cursor-pointer print:hidden">{{ $t('Download') }}</div>
                        </div>
                    </li>
                </ul>
                <div v-if="documents.length === 0" class="xsDark">
                    {{ $t('No files available') }}
                </div>
            </div>
        </div>

        <ConfirmDeleteModal :title="$t('Delete file')"
                            :description="$t('Are you sure you want to delete the selected file from the project?')"
                            @closed="closeConfirmDeleteModal"
                            @delete="deleteFile"
                            v-if="deletingFile"
        />
    </div>
</template>

<script>
import {defineComponent} from "vue";
import JetInputError from "@/Jetstream/InputError.vue";
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {useForm} from "@inertiajs/vue3";
import MultiAlertComponent from "@/Components/Alerts/MultiAlertComponent.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";

import { useProjectDocumentListener } from "@/Composeables/Listener/useProjectDocumentListener.js";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import axios from "axios";
import {IconFileText, IconFileUpload} from "@tabler/icons-vue";

export default defineComponent({
    mixins: [
        Permissions,
        IconLib
    ],
    components: {
        InfoButtonComponent,
        TinyPageHeadline,
        MultiAlertComponent,
        ConfirmDeleteModal,
        JetInputError
    },
    props: [
        'project',
        'projectWriteIds',
        'projectManagerIds',
        'tab_id',
        'canEditComponent',
        'component'
    ],
    data() {
        return {
            uploadDocumentFeedback: "",
            documentForm: {
                errors: {}
            },
            deletingFile: false,
            documents: this.project?.project_files_all ?? []
        };
    },
    mounted() {
        useProjectDocumentListener(this.documents, this.project.id).init();
    },
    methods: {
        IconFileText,
        IconFileUpload,
        async uploadChosenDocuments(event) {
            const files = Array.from(event.target.files);
            await this.validateTypeAndUpload(files);
            event.target.value = '';
        },
        async uploadDraggedDocuments(event) {
            const files = Array.from(event.dataTransfer.files);
            await this.validateTypeAndUpload(files);
        },
        async uploadDocumentToProject(file) {
            const formData = new FormData();
            formData.append('file', file);
            if (this.tab_id) {
                formData.append('tabId', this.tab_id);
            }
            try {
                await axios.post(route('project_files.store', {project: this.project.id}), formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                // Erfolgreich, Listener aktualisiert die Liste
            } catch (error) {
                if (error.response && error.response.data && error.response.data.errors) {
                    this.documentForm.errors = error.response.data.errors;
                } else {
                    this.uploadDocumentFeedback = this.$t('Upload failed');
                }
            }
        },
        async validateTypeAndUpload(files) {
            this.documentForm.errors = {};
                await this.uploadDocumentToProject(file);
            this.uploadDocumentFeedback = "";
            for (let file of files) {
                this.uploadDocumentToProject(file);
            }
        },
        selectNewFiles() {
            this.$refs.project_files.click();
        },
        downloadFile(project_file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: project_file});
            link.target = '_blank';
            link.click();
        },
        openConfirmDeleteModal(project_file) {
            this.deletingFile = true;
            this.project_file = project_file
        },
        closeConfirmDeleteModal() {
            this.deletingFile = false;
            this.project_file = null;
        },
        deleteFile() {
            this.$inertia.delete(route('project_files.destroy', {project_file: this.project_file.id}), {
                preserveScroll: true,
                preserveState: true
            })
            this.closeConfirmDeleteModal()
        }
    }
});
</script>
