<template>
    <BaseModal @closed="$emit('close')" v-if="show" modal-image="/Svgs/Overlays/illu_room_admin_edit.svg">
            <div class="mx-3">
                <ModalHeader
                    :title="$t('Access to room')"
                    :description="$t('Define who can edit the room and release bookings (room admin), and who can request the room.')"
                />
                <div class="">
                    <UserSearch
                        v-model="user_query"
                        @user-selected="addUserToRoom"
                        :label="$t('Type in the names of users')"
                        />
                </div>
                <div class="divide-dashed divide-y divide-gray-200 w-full">
                    <div v-for="(user,index) in roomUsers" class="grid grid-cols-1 md:grid-cols-4 py-3">
                        <div class="flex items-center col-span-2">
                           <div class="flex items-center">
                               <img class="flex h-11 w-11 rounded-full" :src="user.profile_photo_url" alt=""/>
                               <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                            </span>
                           </div>
                            <button type="button" @click="deleteUserFromRoom(user)">
                                <span class="sr-only">{{$t('Remove user as room admin')}}</span>
                                <XCircleIcon class="ml-2 h-5 w-5 hover:text-error "/>
                            </button>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox"
                                   v-model="user.is_room_admin"
                                   :value="user.id"
                                   @change="updateUserAccess(user)"
                                   class="input-checklist"/>
                            <p :class="[user.is_room_admin ? 'text-primary' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-sm">
                                {{ $t('Room admin')}}
                            </p>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox"
                                   v-model="user.can_request_room"
                                   :value="user.id"
                                   @change="updateUserAccess(user)"
                                   class="input-checklist"/>
                            <p :class="[user.can_request_room ? 'text-primary' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-sm">
                                {{$t('Authorized to request')}}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center">
                    <FormButton
                        @click="updateRoomUsers"
                        :text="$t('Save')"
                        class="mt-8" />
                </div>

            </div>
    </BaseModal>
</template>

<script setup>
import {XCircleIcon, XIcon} from '@heroicons/vue/outline';
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {useForm} from "@inertiajs/vue3";
import {onMounted, ref, watch} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";

const props = defineProps({
    show: Boolean,
    room: Object
})

const emit = defineEmits(['close'])


const user_query = ref("")
const user_search_results = ref([])

const roomAdmins = ref(props.room.room_admins);
const requestableBy = ref(props.room.requestable_by);
const roomUsers = ref(roomAdmins.value.concat(requestableBy.value))

const updateRoomUsersForm = useForm({
    room_admins: props.room.room_admins,
    requestable_by: props.room.requestable_by
})

onMounted(() => {
    roomAdmins.value.forEach(user => {
        user.is_room_admin = true;
    })

    requestableBy.value.forEach(user => {
        user.can_request_room = true;
    })
})

watch(user_query, (new_user_query) => {
    if (new_user_query.length > 0) {
        axios.get('/users/search', {
            params: {query: new_user_query}
        }).then(response => {
            user_search_results.value = response.data
        })
    }
})

const deleteUserFromRoom = (user) => {
    if (contains(updateRoomUsersForm.room_admins, user)) {
        updateRoomUsersForm.room_admins.splice(updateRoomUsersForm.room_admins.indexOf(user), 1);
    }

    if (contains(updateRoomUsersForm.requestable_by, user)) {
        updateRoomUsersForm.requestable_by.splice(updateRoomUsersForm.requestable_by.indexOf(user), 1);
    }

    roomUsers.value.splice(roomUsers.value.indexOf(user), 1)

}

const updateRoomUsers = () => {
    updateRoomUsersForm.patch(route('rooms.update', {room: props.room.id}));
    emit('close')
}

const updateUserAccess = (user) => {

    if (user.is_room_admin && !contains(updateRoomUsersForm.room_admins, user)) {
        updateRoomUsersForm.room_admins.push(user);
        updateRoomUsersForm.requestable_by.splice(updateRoomUsersForm.requestable_by.indexOf(user), 1);
    } else if (!user.is_room_admin && contains(updateRoomUsersForm.room_admins, user)) {
        updateRoomUsersForm.room_admins.splice(updateRoomUsersForm.room_admins.indexOf(user), 1);
        if (user.can_request_room && !contains(updateRoomUsersForm.requestable_by, user)) {
            updateRoomUsersForm.requestable_by.push(user)
        }
    } else if (
        user.can_request_room
        && !contains(updateRoomUsersForm.requestable_by, user)
        && !contains(updateRoomUsersForm.room_admins, user)
    ) {
        updateRoomUsersForm.requestable_by.push(user)
    } else if (!user.can_request_room && contains(updateRoomUsersForm.requestable_by, user)) {
        updateRoomUsersForm.requestable_by.splice(updateRoomUsersForm.requestable_by.indexOf(user), 1);
    }
}

const addUserToRoom = (user) => {
    roomUsers.value.push(user)
    user_query.value = "";
    user_search_results.value = []
}

const contains = (array, user) => {
    for (let userInArray of array) {
        if (user.id === userInArray.id) {
            user_query.value = ""
            return true;
        }
    }
    return false
}

</script>

<style scoped>

</style>
