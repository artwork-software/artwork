<template>
    <div>
        <div class="flex w-full items-center my-4">
            <h3 class="sDark">{{ $t('Documents') }}</h3>
        </div>
        <div
            v-if="this.canEditComponent && ($role('artwork admin') || projectWriteIds?.includes(this.$page.props.user.id))">
            <input
                @change="uploadChosenDocuments"
                class="hidden"
                ref="project_files"
                id="file"
                type="file"
                multiple
            />
            <div @click="selectNewFiles" @dragover.prevent
                 @drop.stop.prevent="uploadDraggedDocuments($event)"
                 class="mb-4 w-full flex justify-center items-center border-artwork-buttons-create border-dotted border-2 h-40 bg-colorOfAction p-2 cursor-pointer">
                <p class="text-artwork-buttons-create font-bold text-center"
                   v-html="$t('Drag document here to upload or click in the field')">
                </p>
            </div>
            <jet-input-error :message="uploadDocumentFeedback"/>
        </div>
        <div class="mb-3">
            <div class="space-y-1"
                 v-if="$role('artwork admin') || projectWriteIds?.includes(this.$page.props.user.id) || projectManagerIds?.includes(this.$page.props.user.id)">
                <div v-for="project_file in project.project_files_tab"
                     class="cursor-pointer group flex items-center">
                    <div :data-tooltip-target="project_file.name" class="flex truncate">
                        <IconFileText class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                        <p @click="downloadFile(project_file)" class="ml-2 truncate">
                            {{ project_file.name }}</p>

                        <IconCircleX
                            v-if="this.canEditComponent && ($role('artwork admin') || projectWriteIds?.includes(this.$page.props.user.id) || projectManagerIds?.includes(this.$page.props.user.id))"
                            @click="openConfirmDeleteModal(project_file)"
                            class="ml-2 my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                            aria-hidden="true"/>
                    </div>
                    <div :id="project_file.name" role="tooltip"
                         class="max-w-md inline-block flex flex-wrap absolute invisible z-10 py-3 px-3 text-sm font-medium text-secondary bg-primary shadow-sm opacity-0 transition-opacity duration-300 tooltip">
                        <div class="flex flex-wrap">
                            {{ $t('To download the file, click on the file name') }}
                        </div>
                        <div class="tooltip-arrow" data-popper-arrow></div>
                    </div>
                </div>
            </div>
            <div v-else class="xsDark">
                {{ $t('No files available') }}
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

export default defineComponent({
    mixins: [
        Permissions,
        IconLib
    ],
    components: {
        ConfirmDeleteModal,
        JetInputError
    },
    props: [
        'project',
        'projectWriteIds',
        'projectManagerIds',
        'tab_id',
        'canEditComponent'
    ],
    data() {
        return {
            uploadDocumentFeedback: "",
            documentForm: useForm({
                file: null,
                tabId: this.tab_id ? this.tab_id : null
            }),
            deletingFile: false,
        };
    },
    methods: {
        uploadChosenDocuments(event) {
            this.validateTypeAndUpload([...event.target.files])
        },
        uploadDraggedDocuments(event) {
            this.validateTypeAndUpload([...event.dataTransfer.files])
        },
        uploadDocumentToProject(file) {
            this.documentForm.file = file

            this.documentForm.post(`/projects/${this.project.id}/files`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.documentForm.file = null
                }
            })
        },
        validateTypeAndUpload(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = this.$t('Videos, .exe and .dmg files are not supported.');
                } else {
                    const fileSize = file.size;
                    if (fileSize > 2097152) {
                        this.uploadDocumentFeedback = this.$t('Files larger than 2MB cannot be uploaded.');
                    } else {
                        this.uploadDocumentToProject(file)
                    }
                }
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
