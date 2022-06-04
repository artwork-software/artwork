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
                                        <a @click="openEditRoomModal(room)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="duplicateRoom(room)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <DuplicateIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Duplizieren
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openSoftDeleteRoomModal(room)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
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
                <div class="grid grid-cols-7 mt-6">
                    <div class="col-span-5 mr-14">
                        <span class="text-secondary subpixel-antialiased">
                            {{room.area.name}}
                        </span>
                        <span class="flex mt-6 text-secondary text-sm subpixel-antialiased">
                            {{ room.description }}
                        </span>
                    </div>
                    <div class="col-span-2">
                        <div class="flex w-full mt-6 items-center mb-8">
                            <h3 class="text-2xl leading-6 font-bold font-lexend text-gray-900"> Dokumente </h3>
                        </div>
                        <input
                            @change="uploadChosenDocuments"
                            class="hidden"
                            ref="room_files"
                            id="file"
                            type="file"
                            multiple
                        />
                        <div @click="selectNewFiles" @dragover.prevent @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-8 w-full flex justify-center items-center
                        border-secondary border-dotted border-4 h-40 bg-stone-100 p-2 cursor-pointer">
                            <p class="text-secondary text-center">Ziehe Dokumente hier her
                                <br>oder klicke ins Feld
                            </p>
                        </div>
                        <jet-input-error :message="uploadDocumentFeedback"/>
                        <div class="space-y-1">
                            <div v-for="room_file in room.room_files"
                                 class="cursor-pointer group flex items-center">
                                <DocumentTextIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                                <p @click="downloadFile(room_file)" class="ml-2 truncate flex-grow">{{ room_file.name }}</p>
                                <XCircleIcon @click="removeFile(room_file)"
                                             class="ml-2 hidden group-hover:block h-5 w-5 text-error flex-shrink-0"
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
                        <img :data-tooltip-target="user.id" class="h-9 w-9 rounded-full"
                             :src="user.profile_photo_url"
                             alt=""/>
                        <UserTooltip :user="user" />
                    </div>
                    <button @click="openChangeRoomAdminsModal">
                        <PencilAltIcon class="mt-4 ml-6 h-6 w-6"/>
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
        <!-- Raum Bearbeiten-->
        <jet-dialog-modal :show="showEditRoomModal" @close="closeEditRoomModal">
            <template #content>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-3xl my-2">
                        Raum bearbeiten
                    </div>
                    <XIcon @click="closeEditRoomModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    {{editRoomForm}}
                    <div class="mt-4">
                        <div class="flex mt-10 relative">
                            <input id="roomNameEdit" v-model="editRoomForm.name" type="text"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="roomNameEdit"
                                   class="absolute left-0 text-base -top-4 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Raumname
                            </label>
                            <jet-input-error :message="editRoomForm.error" class="mt-2"/>
                        </div>
                        <div class="mt-8 mr-4">
                                            <textarea
                                                placeholder="Kurzbeschreibung"
                                                v-model="editRoomForm.description" rows="4"
                                                class="focus:border-black placeholder-secondary border-2 w-full font-semibold border border-gray-300 "/>
                        </div>
                        <div class="flex items-center my-6">
                            <input v-model="editRoomForm.temporary"
                                   type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[editRoomForm.temporary ? 'text-primary font-black' : 'text-secondary']"
                               class="ml-4 my-auto text-sm">Temporärer Raum</p>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="h-6 w-6 ml-2 mr-2 mt-4"/>
                                <span
                                    class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-xl">Richte einen temporären Raum ein - z.B wenn ein Teil eines Raumes abgetrennt wird. Dieser wird nur in diesem Zeitraum im Kalender angezeigt.</span>
                            </div>
                        </div>
                        <div class="flex" v-if="editRoomForm.temporary">
                            <input
                                v-model="editRoomForm.start_date" id="startDateEdit"
                                placeholder="Zu erledigen bis?" type="date"
                                class="border-gray-300 placeholder-secondary mr-2 w-full"/>
                            <input
                                v-model="editRoomForm.end_date" id="endDateEdit"
                                placeholder="Zu erledigen bis?" type="date"
                                class="border-gray-300 placeholder-secondary w-full"/>
                        </div>

                        <button :class="[editRoomForm.name.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-4 flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="editRoom"
                                :disabled="editRoomForm.name.length === 0">
                            Speichern
                        </button>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Delete Room Modal -->
        <jet-dialog-modal :show="showSoftDeleteRoomModal" @close="closeSoftDeleteRoomModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Raum in den Papierkorb
                    </div>
                    <XIcon @click="closeSoftDeleteRoomModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Bist du sicher, dass du den Raum {{ roomToSoftDelete.name }} in den Papierkorb legen möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="softDeleteRoom()">
                            Entfernen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeSoftDeleteRoomModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        {{ successHeading }}
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success">
                        {{ successDescription }}
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal">
                            <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                        </button>
                    </div>
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
import {useForm} from "@inertiajs/inertia-vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip";

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
        DuplicateIcon,
        UserTooltip
    },
    data() {
        return {
            showChangeRoomAdminsModal: false,
            showSuccess: false,
            user_query: "",
            user_search_results: [],
            uploadDocumentFeedback: "",
            showEditRoomModal: false,
            roomToSoftDelete: null,
            showSuccessModal: false,
            showSoftDeleteRoomModal: false,
            successHeading: "",
            successDescription: "",
            roomForm: this.$inertia.form({
                _method: 'PUT',
                room_admins: this.room.room_admins,
            }),
            editRoomForm: useForm({
                name: '',
                description: '',
                temporary: false,
                start_date: null,
                end_date: null,
                area_id: null,
                user_id: null,
            }),
            documentForm: useForm({
                file: null
            }),
        }
    },
    methods: {
        selectNewFiles() {
            this.$refs.room_files.click();
        },
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
        validateTypeAndUpload(files) {
            this.uploadDocumentFeedback = "";
            const forbiddenTypes = [
                "application/vnd.microsoft.portable-executable",
                "application/x-apple-diskimage",
            ]
            for (let file of files) {
                if (forbiddenTypes.includes(file.type) || file.type.match('video.*')) {
                    this.uploadDocumentFeedback = "Videos, .exe und .dmg Dateien werden nicht unterstützt"
                } else {
                    this.uploadDocumentToRoom(file)
                }
            }
        },
        uploadChosenDocuments(event) {
            this.validateTypeAndUpload([...event.target.files])
        },
        uploadDraggedDocuments(event) {
            this.validateTypeAndUpload([...event.dataTransfer.files])
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
        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successHeading = "";
            this.successDescription = "";
        },
        editRoomAdmins() {
            this.roomForm.patch(route('rooms.update', {room: this.room.id}));
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
        duplicateRoom(room) {
            this.$inertia.post(`/rooms/${room.id}/duplicate`);
        },
        openEditRoomModal(room){
            this.editRoomForm = room;
            if(room.temporary === 1){
                this.editRoomForm.temporary = true;
                this.editRoomForm.start_date = room.start_date_dt_local;
                this.editRoomForm.end_date = room.end_date_dt_local;
            }
            this.showEditRoomModal = true;
        },
        closeEditRoomModal(){
            this.showEditRoomModal = false;
        },
        openSoftDeleteRoomModal(room){
            this.roomToSoftDelete = room;
            this.showSoftDeleteRoomModal = true;
        },
        closeSoftDeleteRoomModal(){
            this.showSoftDeleteRoomModal = false;
            this.roomToSoftDelete = null;
        },
        softDeleteRoom() {
            this.$inertia.delete(`/rooms/${this.roomToSoftDelete.id}`);
            this.closeSoftDeleteRoomModal();
            this.successHeading = "Raum im Papierkorb"
            this.successDescription = "Der Raum wurde erfolgreich in den Papierkorb gelegt."
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000);
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
