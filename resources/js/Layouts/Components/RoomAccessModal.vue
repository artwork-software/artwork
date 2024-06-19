<template>
    <BaseModal @closed="$emit('close')" v-if="show" modal-image="/Svgs/Overlays/illu_room_admin_edit.svg">
            <div class="mx-3">
                <div class="headline1 mt-2 mb-6">
                    {{$t('Access to room')}}
                </div>
                <div class="xsLight">
                    {{ $t('Define who can edit the room and release bookings (room admin), and who can request the room.')}}
                </div>
                <div class="mt-6 relative">
                    <div class="my-auto w-full">
                        <input :placeholder="$t('Type in the names of users')"
                               id="userSearch"
                               v-model="user_query"
                               autocomplete="off"
                               class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                    </div>

                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0">
                        <div v-if="user_search_results.length > 0 && user_query.length > 0"
                             class="absolute z-10 mt-1 w-full max-h-60 bg-artwork-navigation-background shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                            <div class="border-gray-200">
                                <div v-for="(user, index) in user_search_results" :key="index"
                                     class="flex items-center cursor-pointer">
                                    <div class="flex-1 text-sm py-4">
                                        <p @click="addUserToRoom(user)"
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
                    <div v-for="(user,index) in roomUsers"
                         class="mt-4 mr-1 rounded-full items-center font-bold text-primary">
                        <div class="flex items-center">
                            <img class="flex h-11 w-11 rounded-full"
                                 :src="user.profile_photo_url"
                                 alt=""/>
                            <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            <button type="button" @click="deleteUserFromRoom(user)">
                                <span class="sr-only">{{$t('Remove user as room admin')}}</span>
                                <XCircleIcon class="ml-2 h-5 w-5 hover:text-error "/>
                            </button>

                            <input type="checkbox"
                                   v-model="user.is_room_admin"
                                   :value="user.id"
                                   @change="updateUserAccess(user)"
                                   class="ml-8 cursor-pointer h-6 w-6 text-success border-2 border-secondary bg-darkGrayBg focus:border-none"/>
                            <p :class="[user.is_room_admin ? 'text-primary' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-sm subpixel-antialiased align-text-middle">
                                {{ $t('Room admin')}}
                            </p>

                            <input type="checkbox"
                                   v-model="user.can_request_room"
                                   :value="user.id"
                                   @change="updateUserAccess(user)"
                                   class="ml-8 cursor-pointer h-6 w-6 text-success border-2 border-secondary bg-darkGrayBg focus:border-none"/>
                            <p :class="[user.can_request_room ? 'text-primary' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-sm subpixel-antialiased align-text-middle">
                                {{$t('Authorized to request')}}
                            </p>
                        </div>
                        <hr class="my-4 border-silverGray">
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
