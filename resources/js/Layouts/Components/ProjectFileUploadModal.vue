<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Dokument hochladen
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary text-sm my-6">
                    Lade Dokumente hoch, die ausschließlich das Budget betreffen. Diese können nur User mit entsprechender Berechtigung einsehen.
                </div>
                <div>
                    <input
                        @change="upload"
                        class="hidden"
                        ref="module_files"
                        id="file"
                        type="file"
                        multiple
                    />
                    <div @click="selectNewFiles" @dragover.prevent
                         @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-buttonBlue border-dotted border-2 h-32 bg-colorOfAction p-2 cursor-pointer">
                        <p class="text-buttonBlue font-bold text-center">Dokument zum Upload hierher ziehen
                            <br>oder ins Feld klicken
                        </p>
                    </div>
                    <jet-input-error :message="uploadDocumentFeedback"/>
                </div>
                <div>
                <textarea placeholder="Kommentar / Notiz"
                          id="description"
                          v-model="comment"
                          rows="4"
                          class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>
                <div class="my-1">
                    <div class="relative w-full">
                        <div class="w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   placeholder="Dokumentzugriff für*"
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
                                        <div class="flex-1 text-sm py-4" v-if="budgetAccess.includes(user.id)">
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
                                                <span class="sr-only">User aus Vertrag entfernen</span>
                                                <XIcon
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
                    </div>
                </div>
                <div class="mb-6">
                    <div v-for="file of files">{{ file.name }}</div>
                </div>
                <div class="justify-center flex w-full my-6">
                    <AddButton text="Dokument hochladen" mode="modal" class="px-6 py-3" :disabled="files.length < 1"
                               @click="storeFiles"/>
                </div>
            </div>

        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/DialogModal.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    name: "ProjectFileUploadModal",
    props: {
        show: Boolean,
        closeModal: Function,
        projectId: Number,
        budgetAccess: Array
    },
    components: {
        JetDialogModal,
        JetInputError,
        AddButton,
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
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*') || file.type === "") {
                    this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
                } else {
                    this.files.push(file)
                }
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
