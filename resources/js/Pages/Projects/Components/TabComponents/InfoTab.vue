<template>
    <div class="mt-6 bg-lightBackgroundGray">
        <div class="ml-14 pr-14 pt-6">
            <div class="grid grid-cols-6">
                <div class="col-span-4">
                    <!-- Description -->
                    <div class="">
                        <div class="sDark">{{ $t('Short description') }}</div>
                        <div v-if="descriptionClicked === false"
                             class="mt-2 subpixel-antialiased text-secondary"
                             @click="handleDescriptionClick()" v-html="project.description ? project.description : $t('Click here to add text')">
                        </div>
                        <textarea v-else v-model="project.description_without_html" type="text"
                                  @focusout="updateDescription()"
                                  :ref="`description-${this.project.id}`"
                                  class="w-full border-gray-300 text-primary h-40"
                                  :placeholder="project.description_without_html || $t('Click here to add text')"/>
                    </div>
                    <!-- Individual Projectinformation -->
                    <div v-for="headline in project.project_headlines" class="mt-7">
                        <div class="sDark" >{{ headline.name }}</div>
                        <div v-if="!headline.clicked" class="mt-2 subpixel-antialiased text-secondary"
                             @click="handleTextClick(headline)">
                            <p v-if="headline.text" v-html="headline.text"></p>
                            <p v-else>{{ $t('Click here to add text') }}</p>
                        </div>
                        <textarea v-else v-model="headline.text_without_html" type="text" :ref="`text-${headline.id}`"
                                  @focusout="changeHeadlineText(headline)"
                                  class="w-full border-gray-300 text-primary h-40"
                                  :placeholder="headline.text || 'Hier klicken um Text hinzuzufügen'"/>
                    </div>
                </div>
                <div
                    v-if="$can('write projects') || $role('artwork admin') || $can('admin projects') || projectWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || project.isMemberOfADepartment"
                    class="col-span-2">
                    <div class="ml-10 ">
                        <label class="block my-4 sDark">{{ $t('Key Visual') }}</label>
                       <div class="group">
                           <div
                               class=" flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                               @dragover.prevent
                               @drop.stop.prevent="uploadDraggedKeyVisual($event)"
                               @click="selectNewKeyVisual"
                               v-if="this.project.key_visual_path === null">
                               <div class="space-y-1 text-center">
                                   <div class="xsLight flex my-auto h-40 items-center"
                                        v-if="this.project.key_visual_path === null">
                                       <span v-html="$t('Drag your key visual here')"></span>
                                       <input id="keyVisual-upload" ref="keyVisual"
                                              name="file-upload" type="file" class="sr-only"
                                              @change="updateKeyVisual"/>
                                   </div>
                               </div>
                           </div>
                           <div v-else class="flex items-center justify-center relative w-full">
                               <div
                                   class="absolute !gap-4 w-full text-center flex items-center justify-center hidden group-hover:block">
                                   <button @click="downloadKeyVisual" type="button"
                                           class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                       <SvgCollection svg-name="ArrowDownTray" class="h-5 w-5" aria-hidden="true"/>
                                   </button>
                                   <button @click="selectNewKeyVisual" type="button"
                                           class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                       <PencilAltIcon
                                           class="h-5 w-5 text-primaryText group-hover:text-white"
                                           aria-hidden="true"/>
                                   </button>
                                   <button @click="deleteKeyVisual" type="button"
                                           class="inline-flex rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                       <XIcon class="h-5 w-5 text-primaryText group-hover:text-white"
                                              aria-hidden="true"/>
                                   </button>
                               </div>
                               <div class="text-center">
                                   <div class="cursor-pointer">
                                       <img src="">
                                       <img :src="'/storage/keyVisual/' + this.project.key_visual_path"
                                            alt="Aktuelles Key-Visual"
                                            class="rounded-md w-full h-48">
                                       <input id="keyVisual-upload" ref="keyVisual"
                                              name="file-upload" type="file" class="sr-only"
                                              @change="updateKeyVisual"/>
                                   </div>
                               </div>
                           </div>
                           <jet-input-error :message="uploadKeyVisualFeedback"/>
                       </div>
                        <div class="flex w-full items-center my-4">
                            <h3 class="sDark">{{ $t('Documents') }}</h3>
                        </div>
                        <div
                            v-if="$role('artwork admin') || projectWriteIds.includes(this.$page.props.user.id)">
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
                                 class="mb-4 w-full flex justify-center items-center border-buttonBlue border-dotted border-2 h-40 bg-colorOfAction p-2 cursor-pointer">
                                <p class="text-buttonBlue font-bold text-center"
                                   v-html="$t('Drag document here to upload or click in the field')">
                                </p>
                            </div>
                            <jet-input-error :message="uploadDocumentFeedback"/>
                        </div>
                        <div>
                            <div class="space-y-1"
                                 v-if="$role('artwork admin') || projectWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                                <div v-for="project_file in project.project_files"
                                     class="cursor-pointer group flex items-center">
                                    <div :data-tooltip-target="project_file.name" class="flex truncate">
                                        <DocumentTextIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                                        <p @click="downloadFile(project_file)" class="ml-2 truncate">
                                            {{ project_file.name }}</p>

                                        <XCircleIcon
                                            v-if="$role('artwork admin') || projectWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    <ConfirmDeleteModal :title="$t('Delete file')"
                        :description="$t('Are you sure you want to delete the selected file from the project?')"
                        @closed="closeConfirmDeleteModal"
                        @delete="deleteFile"
                        v-if="deletingFile"
    />
</template>

<script>
import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {nextTick} from "vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

export default{
    components: {
        ConfirmDeleteModal,
        PencilAltIcon,
        XCircleIcon,
        DocumentTextIcon,
        SvgCollection,
        XIcon,
        JetInputError
    },
    props: [
        'project',
        'projectWriteIds',
        'projectManagerIds',
    ],
    mixins: [Permissions],
    data() {
        return{
            descriptionClicked: false,
            keyVisualForm: useForm({
                keyVisual: null,
            }),
            uploadDocumentFeedback: "",
            uploadKeyVisualFeedback: "",
            documentForm: useForm({
                file: null
            }),
            deletingFile: false,
            project_file: null,
        }
    },
    methods:{
        async handleDescriptionClick() {
            if(this.$can('write projects') || this.$role('artwork admin') || this.$can('admin projects') || this.projectWriteIds.includes(this.$page.props.user.id) || this.projectManagerIds.includes(this.$page.props.user.id) || this.project.isMemberOfADepartment){
                this.descriptionClicked = true;

                await nextTick()

                this.$refs[`description-${this.project.id}`].select();
            }
        },
        async handleTextClick(headline) {

            if(this.$can('write projects') || this.$role('artwork admin') || this.$can('admin projects') || this.projectWriteIds.includes(this.$page.props.user.id) || this.projectManagerIds.includes(this.$page.props.user.id) || this.project.isMemberOfADepartment) {
                headline.clicked = !headline.clicked

                if (headline.clicked) {
                    await nextTick()

                    this.$refs[`text-${headline.id}`][0].select();
                }
            }
        },
        changeHeadlineText(headline) {
            this.$inertia.patch(route('project_headlines.update.text', {
                project_headline: headline.id,
                project: this.project.id
            }), {text: headline.text_without_html}, {
                preserveScroll: true,
                preserveState: true
            })
        },
        updateDescription() {
            this.$inertia.patch(route('projects.update_description', this.project.id), {
                description: this.project.description_without_html
            }, {
                preserveScroll: true,
                preserveState: true
            });
            this.descriptionClicked = false;
        },
        selectNewKeyVisual() {
            this.$refs.keyVisual.click();
        },
        updateKeyVisual() {
            this.validateTypeAndUploadKeyVisual(this.$refs.keyVisual.files[0]);
        },
        uploadDraggedKeyVisual(event) {
            this.validateTypeAndUploadKeyVisual(event.dataTransfer.files[0]);
        },
        validateTypeAndUploadKeyVisual(file) {
            this.uploadKeyVisualFeedback = "";
            const allowedTypes = [
                "image/jpeg",
                "image/svg+xml",
                "image/png",
                "image/gif"
            ]

            if (allowedTypes.includes(file.type)) {
                this.keyVisualForm.keyVisual = file;
                this.keyVisualForm.post(
                    route('projects_key_visual.update', {project: this.project.id}),
                    {
                        onError: error => {
                            this.uploadKeyVisualFeedback = error.keyVisual;
                        }
                    }
                );
            } else {
                this.uploadKeyVisualFeedback = this.$t('Only logos and illustrations of the type .jpeg, .svg, .png and .gif are accepted.');
            }
        },
        downloadKeyVisual() {
            let link = document.createElement('a');
            link.href = route('project.download.keyVisual', this.project.id);
            link.target = '_blank';
            link.click();
        },
        deleteKeyVisual() {
            this.$inertia.delete(route('project.delete.keyVisual', this.project.id))
        },
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
}
</script>
