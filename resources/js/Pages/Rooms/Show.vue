<template>
    <app-layout title="Teamprofil">
        <div class="max-w-screen-xl my-12 ml-20 mr-10">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="font-bold font-lexend text-3xl">{{ room.name }}</h2>
                    <Menu as="div" class="my-auto relative">
                        <div class="flex">
                            <MenuButton
                                class="flex ml-6">
                                <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                  aria-hidden="true"/>
                            </MenuButton>
                            <div v-if="$page.props.can.show_hints" class="absolute flex w-48 ml-12">
                                <div>
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                </div>
                                <div class="flex">
                                    <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite den Raum</span>
                                </div>
                            </div>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openEditProjectModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="duplicateProject(this.project)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <DuplicateIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Duplizieren
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click=""
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            In den Papierkorb
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
                <div class="grid grid-cols-7 mt-12">
                    <div class="col-span-5 mr-14">
                        <span>
                            Hier Room Area {{room}}
                        </span>
                        <span class="text-secondary text-sm subpixel-antialiased">
                            {{ room.description }}
                        </span>
                    </div>
                    <div class="col-span-2">
                        <div class="flex w-full items-center mb-8">
                            <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Dokumente </h3>
                        </div>
                        <div @dragover.prevent @drop.stop.prevent="uploadDocument($event)" class="mb-8 w-full flex justify-center items-center
                        border-secondary border-dotted border-4 h-40 bg-stone-100 p-2">
                            <p class="text-secondary">Zugelassene Formate:
                                <br>.jpg, .pdf, .docx, .xls
                            </p>
                        </div>
                        <jet-input-error :message="uploadDocumentFeedback"/>
                        <div class="space-y-1">
                            <div v-for="room_file in room.room_files"
                                 class="cursor-pointer group flex items-center">
                                <DocumentTextIcon class="h-5 w-5" aria-hidden="true"/>
                                <p @click="downloadFile(room_file)" class="ml-2">{{ room_file.name }}</p>
                                <XCircleIcon @click="removeFile(room_file)"
                                             class="ml-2 hidden group-hover:block h-5 w-5 text-error"
                                             aria-hidden="true"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center mt-16 mr-8">
                <span class="font-bold font-lexend text-2xl w-full">
                    Raumadmin
                </span>
                    <div class="mt-4" v-if="roomForm.room_admins.length === 0">
                        <span class="text-secondary subpixel-antialiased cursor-pointer">Noch keine Raumadmins festgelegt</span>
                    </div>
                    <div v-else class="mt-4 -mr-3" v-for="user in room.room_admins">
                        <img class="h-9 w-9 rounded-full"
                             :src="user.profile_photo_url"
                             alt=""/>
                    </div>
                    <button @click="openChangeRoomAdminsModal">
                        <PencilAltIcon class="mt-4 ml-2 h-8 w-8"/>
                    </button>
                </div>
            </div>

        </div>

        <!-- Change RoomAdmins Modal -->
        <jet-dialog-modal :show="showChangeRoomAdminsModal" @close="closeChangeRoomAdminsModal">
            <template #content>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Raumadmin bearbeiten
                    </div>
                    <XIcon @click="closeChangeRoomAdminsModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen der Nutzer*innen ein, welche den Raum bearbeiten und direkt belegen dürfen.
                    </div>
                    <div class="mt-6 relative">
                        <div class="my-auto w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="userSearch"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
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
                                            <p @click="addUserToRoomAdminsArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                        </div>
                        <span v-for="(user,index) in room.room_admins"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteUserFromRoomAdminArray(user)">
                                <span class="sr-only">User als Raumadmin entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                    </div>
                    <button @click="editRoomAdmins"
                            class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    >Speichern
                    </button>

                </div>

            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {PencilAltIcon, TrashIcon, XIcon, DocumentTextIcon,DuplicateIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Inertia} from "@inertiajs/inertia";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    name: "Show",
    props: ['room'],
    components: {
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        XCircleIcon,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        CheckIcon,
        ChevronDownIcon,
        DocumentTextIcon,
        DuplicateIcon
    },
    data() {
        return {
            showChangeRoomAdminsModal: false,
            showSuccess: false,
            user_query: "",
            user_search_results: [],
            uploadDocumentFeedback: "",
            roomForm: this.$inertia.form({
                _method: 'PUT',
                name: this.room.name,
                room_admins: this.room.room_admins,
                description: this.room.description,
                temporary: this.room.temporary,
                start_date: this.room.start_date,
                end_date: this.room.end_date,
            }),
            documentForm: useForm({
                file: null
            }),
        }
    },
    methods: {
        removeFile(room_file) {
            this.$inertia.delete(`/room_files/${room_file.id}`, {
                preserveState: true,
            })
        },
        downloadFile(room_file) {
            let link = document.createElement('a');
            link.href = route('download_room_file', {room_file: room_file});
            link.target = '_blank';
            link.click();
        },
        uploadDocument(event) {
            this.uploadDocumentFeedback = "";
            const allowedTypes = [
                "image/jpeg",
                "application/pdf",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "application/vnd.ms-excel",
                "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            ]

            for (let file of [...event.dataTransfer.files]) {
                if (allowedTypes.includes(file.type)) {

                    this.uploadDocumentToRoom(file)

                } else {
                    this.uploadDocumentFeedback = "Dieses Dateiformat wird nicht unterstützt"

                }
            }
        },
        uploadDocumentToRoom(file) {
            this.documentForm.file = file

            this.documentForm.post(`/rooms/${this.room.id}/files`, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.documentForm.file = null
                }
            })
        },
        openChangeRoomAdminsModal() {
            this.showChangeRoomAdminsModal = true;
        },
        closeChangeRoomAdminsModal() {
            this.showChangeRoomAdminsModal = false;
        },
        deleteUserFromRoomAdminArray(user) {
            this.roomForm.room_admins.splice(this.roomForm.room_admins.indexOf(user), 1);
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        editRoomAdmins() {
            this.roomForm.patch(route('rooms.update', {room: this.room.id}));
            this.showSuccessButton();
            this.closeChangeRoomAdminsModal();
        },
        addUserToRoomAdminsArray(user) {
            for (let adminUser of this.roomForm.room_admins) {
                //if User is already Room Admin, do nothing
                if (user.id === adminUser.id) {
                    this.user_query = ""
                    return;
                }
            }
            this.roomForm.room_admins.push(user);
            this.user_query = "";
            this.user_search_results = []
        },
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
        }
    },
}
</script>

<style scoped>

</style>
