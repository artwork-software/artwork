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
                        <div @click="selectNewFiles" @dragover.prevent @drop.stop.prevent="uploadDraggedDocuments($event)" class="w-full flex rounded-lg justify-center items-center border-artwork-buttons-create border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                            <p class="text-artwork-buttons-create font-bold text-center">
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
                            <div v-for="(user,index) in usersWithAccess" class="flex mr-5 rounded-full items-center font-bold text-primary">
                                <div class="flex items-center">
                                    <img class="flex h-11 w-11 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                                    <span class="flex ml-4 sDark">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                                    <button type="button" @click="deleteUserFromFileUserArray(index)">
                                        <span class="sr-only">{{ $t('Remove user from contract')}}</span>
                                        <XIcon class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full text-primary border-0 "/>
                                    </button>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <div class="justify-end flex w-full my-6">
                        <BaseUIButton
                            :label="$t('Upload document')"
                            :disabled="files.length < 1"
                            type="submit"
                            is-add-button
                        />
                    </div>
                </form>
            </div>
    </ArtworkBaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {XIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/vue3";
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
        XIcon
    },
    data() {
        return {
            uploadDocumentFeedback: "",
            files: [],
            comment: "",
            user_query: '',
            user_search_results: [],
            usersWithAccess: [],
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
        storeFile(file) {
            this.projectFileForm.file = file
            this.projectFileForm.comment = this.comment
            const userIds = [];
            this.usersWithAccess.forEach((user) => {
                userIds.push(user.id);
            })
            this.projectFileForm.accessibleUsers = userIds;
            this.projectFileForm.post(this.route('project_files.store', this.projectId))
        },
        validateType(files) {
            this.uploadDocumentFeedback = "";
            for (let file of files) {
              this.files.push(file)
            }
        },
        storeFiles() {
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
