<template>
    <div class="w-full mt-24">
        <div class="flex">
            <div class="text-secondary text-md">
                Zugriff auf Raum
            </div>
            <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openRoomAccessModal"/>
        </div>
        <div class="text-secondary text-sm mt-4">RAUMADMIN</div>
        <div class="flex" v-if="room.room_admins.length > 0">
            <div class="flex flex-wrap mt-2 -mr-3" v-for="user in room.room_admins">
                <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
                     class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                <UserTooltip :user="user"/>
            </div>
        </div>
        <div v-else class="text-secondary text-sm mt-2">
            Noch keine Raumadmins vorhanden
        </div>
        <div class="text-secondary text-sm mt-4">ANFRAGEBERECHTIGT</div>
        <div class="flex" v-if="room.requestable_by.length > 0">
            <div class="flex flex-wrap mt-2 -mr-3" v-for="user in room.requestable_by">
                <img :data-tooltip-target="user?.id" :src="user?.profile_photo_url" :alt="user?.name"
                     class="ring-white ring-2 rounded-full h-11 w-11 object-cover"/>
                <UserTooltip :user="user"/>
            </div>
        </div>
        <div v-else class="text-secondary text-sm mt-2">
            Bislang ist kein Nutzer anfrageberechtigt
        </div>

        <hr class="my-10 border-darkGray">

        <div class="flex">
            <div class="text-secondary text-md">
                Raumeigenschaften
            </div>
            <PencilAltIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                           @click="openEditRoomAttributesModal"/>
        </div>
        <div v-if="adjoiningRooms.length > 0 || categories.length > 0 || attributes.length > 0">
            <div class="mt-4 flex flex-wrap">
                <div v-for="category in categories"
                     class="mr-2 mb-2 flex text-sm px-3 py-1 border border-darkGray bg-primary w-fit rounded-full">
                    {{ category.name}}
                </div>
                <div v-for="attribute in attributes"
                     class="mr-2 mb-2 flex text-sm px-3 py-1 border border-darkGray bg-primary w-fit rounded-full">
                    {{ attribute.name}}
                </div>
                <div v-for="room in adjoiningRooms"
                     class="mr-2 mb-2 flex text-sm px-3 py-1 border border-darkGray bg-primary w-fit rounded-full">
                    {{ room.name}}
                </div>
            </div>
        </div>
        <div v-else class="text-secondary text-sm mt-4">
            Noch keine Eigenschaften ausgewählt
        </div>

        <hr class="my-10 border-darkGray">

        <div class="flex items-center">
            <div class="text-secondary text-md">
                Dokumente
            </div>
            <ChevronDownIcon class="w-4 h-4 ml-4" :class="[ showRoomFiles ? 'rotate-180' : '']"
                             @click="showRoomFiles = !showRoomFiles"/>
            <UploadIcon class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                        @click="openFileUploadModal"/>
            <RoomFileUploadModal
                :show="showFileUploadModal"
                :close-modal="closeFileUploadModal"
                :room-id="room.id"
            />
        </div>
        <div v-if="showRoomFiles">
            <div v-if="room.room_files.length > 0" class="mt-4">
                <div v-for="roomFile in room.room_files">
                    <div class="flex items-center w-full mb-2 cursor-pointer text-secondary hover:text-white" >
                        <DownloadIcon class="w-4 h-4 mr-2" @click="downloadRoomFile(roomFile)"/>
                        <div>{{ roomFile.name }}</div>
                        <XCircleIcon class="w-4 h-4 ml-auto bg-error rounded-full text-white" @click="openFileDeleteModal(roomFile)"/>
                    </div>
                </div>
                <FileDeleteModal
                    :show="showFileDeleteModal"
                    :close-modal="closeFileDeleteModal"
                    :file="roomFileToDelete"
                    type="room"
                />
            </div>
            <div v-else>
                <div class="text-secondary text-sm mt-4">Keine Dokumente vorhanden</div>
            </div>
        </div>
        <RoomAccessModal
            :show="showRoomAccessModal"
            :room="props.room"
            @close="closeRoomAccessModal"
        />

        <RoomAttributeEditModal
            :show="showRoomAttributeEditModal"
            :room="props.room"
            :categories="categories"
            :available-categories="availableCategories"
            :adjoining-rooms="adjoiningRooms"
            :available-adjoining-rooms="availableAdjoiningRooms"
            :attributes="attributes"
            :available-attributes="availableAttributes"
            @close="closeEditRoomAttributesModal"
        />
    </div>
</template>

<script setup>
import {
    UploadIcon,
    PencilAltIcon,
    DownloadIcon,
    XCircleIcon,
    ChevronDownIcon
} from '@heroicons/vue/outline';
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import {onMounted, ref} from "vue";
import RoomAccessModal from "@/Layouts/Components/RoomAccessModal.vue";
import RoomAttributeEditModal from "@/Layouts/Components/RoomAttributeEditModal.vue";
import FileDeleteModal from "@/Layouts/Components/FileDeleteModal.vue";
import RoomFileUploadModal from "@/Layouts/Components/RoomFileUploadModal.vue";


const props = defineProps({
    room: Object,
    categories: Array,
    availableCategories: Array,
    attributes: Array,
    availableAttributes: Array,
    adjoiningRooms: Array,
    availableAdjoiningRooms: Array
})

const showRoomAccessModal = ref(false);
const showRoomAttributeEditModal = ref(false);
const showFileUploadModal = ref(false);
const showFileEditModal = ref(false);
const showFileDeleteModal = ref(false);
const showRoomFiles = ref(false);
const roomFiles = ref(props.room.room_files)
const roomFileToDelete = ref(null)

const openRoomAccessModal = () => {
    showRoomAccessModal.value = true
}

const closeRoomAccessModal = () => {
    showRoomAccessModal.value = false
}

const openEditRoomAttributesModal = () => {
    showRoomAttributeEditModal.value = true
}

const closeEditRoomAttributesModal = () => {
    showRoomAttributeEditModal.value = false
}

const downloadRoomFile = (file) => {
    let link = document.createElement('a');
    link.href = route('download_room_file', {room_file: file});
    link.target = '_blank';
    link.click();
}

const openFileUploadModal = () => {
    showFileUploadModal.value = true
}

const closeFileUploadModal = () => {
    showFileUploadModal.value = false
}

const openFileDeleteModal = (roomFile) => {
    roomFileToDelete.value = roomFile
    showFileDeleteModal.value = true
}

const closeFileDeleteModal = () => {
    showFileDeleteModal.value = false
}

</script>

<style scoped>

</style>
