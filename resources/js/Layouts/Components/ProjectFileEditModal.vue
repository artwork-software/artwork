<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Edit document')}}
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="flex items-center cursor-pointer" @click="downloadProjectFile(file)">
                    <DownloadIcon class="w-4 h-4 mr-2 text-buttonBlue"/>
                    <div class="text-buttonBlue text-sm my-6">{{ file.name }}</div>
                </div>
                <div class="text-secondary text-sm my-2">
                    {{$t('Replace document')}}
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
                        <p class="text-buttonBlue font-bold text-center">
                            {{ $t('Drag document here to upload or click in the field')}}
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div>
                <textarea :placeholder="$t('Comment / Note')"
                          id="description"
                          v-model="comment"
                          rows="4"
                          class="inputMain resize-none xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <div class="my-1">
                    <div class="relative w-full">
                        <div class="w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   :placeholder="$t('Document access for') + '*'"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUserToFileUserArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div v-if="usersWithAccess.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(user,index) in usersWithAccess"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                        <div class="flex items-center">
                                            <img class="flex h-11 w-11 rounded-full object-cover"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromFileUserArray(index)">
                                                <span class="sr-only">{{$t('Remove user from contract')}}</span>
                                                <XIcon
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
                    </div>
                </div>
                <div class="mb-6">
                    <div v-for="file in files">{{ $t('New document')}}: {{ file?.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <FormButton :text="$t('Save')"
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
import {XIcon, DownloadIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import Permissions from "@/mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    name: "ProjectFileEditModal",
    mixins: [Permissions],
    props: {
        show: Boolean,
        closeModal: Function,
        projectId: Number,
        file: Object
    },
    components: {
        FormButton,
        JetDialogModal,
        JetInputError,
        XIcon,
        DownloadIcon
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            comment: null,
            user_query: '',
            user_search_results: [],
            usersWithAccess: this.file?.accessibleUsers ? this.file.accessibleUsers : [],
            projectFileForm: useForm({
                file: null,
                comment: this.comment,
                accessibleUsers: this.usersWithAccess
            })
        }
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
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
            this.validateType([...event.dataTransfer.files])
        },
        upload(event) {
            this.validateType([...event.target.files])
        },
        updateRequest(file) {
            this.projectFileForm.file = file
            this.projectFileForm.comment = this.comment
            const userIds = [];
            this.usersWithAccess.forEach((user) => {
                userIds.push(user.id);
            })
            this.projectFileForm.accessibleUsers = userIds;
            this.projectFileForm.post(this.route('project_files.update', this.file.id))
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = this.$t('Videos, .exe and .dmg files are not supported')
                } else {
                    const fileSize = file.size;
                    if (fileSize > 2097152) {
                        this.uploadDocumentFeedback = this.$t('Files larger than 2MB cannot be uploaded.')
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
