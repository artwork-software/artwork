<template>
    <BaseModal @closed="$emit('close')" v-if="show" modal-image="/Svgs/Overlays/illu_room_admin_edit.svg">
        <div class="mx-3">
            <ModalHeader :title="$t('Access to room')"
                         :description="$t('Define who can edit the room and release bookings (room admin), and who can request the room.')"/>
            <UserSearch v-model="user_query"
                        @user-selected="addUserToRoom"
                        :label="$t('Type in the names of users')"/>
            <div class="divide-dashed divide-y divide-gray-200 w-full">
                <div v-for="(user) in room_users" class="grid grid-cols-1 md:grid-cols-4 py-3">
                    <div class="flex items-center col-span-2">
                        <div class="flex items-center">
                            <img class="flex h-11 w-11 rounded-full" :src="user.profile_photo_url" alt=""/>
                            <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                            </span>
                        </div>
                        <button type="button" @click="removeUserFromRoom(user.id)">
                            <span class="sr-only">{{ $t('Remove user as room admin') }}</span>
                            <XCircleIcon class="ml-2 h-5 w-5 hover:text-error "/>
                        </button>
                    </div>
                    <div class="flex items-center gap-x-1">
                        <input :id="user.id + '-cb-is_admin'"
                               type="checkbox"
                               v-model="user.pivot.is_admin"
                               :value="user.id"
                               class="input-checklist"/>
                        <label :for="user.id + '-cb-is_admin'"
                               :class="[user.pivot.is_admin ? 'text-primary' : 'text-secondary', 'subpixel-antialiased']"
                               class="text-sm cursor-pointer">
                            {{ $t('Room admin') }}
                        </label>
                    </div>
                    <div class="flex items-center gap-x-1">
                        <input :id="user.id + '-cb-can_request'"
                               type="checkbox"
                               v-model="user.pivot.can_request"
                               :value="user.id"
                               class="input-checklist"/>
                        <label :for="user.id + '-cb-can_request'"
                               :class="[user.pivot.can_request ? 'text-primary' : 'text-secondary', 'subpixel-antialiased']"
                               class="text-sm cursor-pointer">
                            {{ $t('Authorized to request') }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex justify-center">
                <FormButton @click="updateRoomUsers"
                            :text="$t('Save')"
                            class="mt-8"/>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import {XCircleIcon} from '@heroicons/vue/outline';
import {ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
        show: Boolean,
        room: Object
    }),
    emits = defineEmits(['close']),
    user_query = ref(""),
    user_search_results = ref([]),
    room_users = ref(props.room.users),
    updateRoomUsers = () => {
        router.patch(
            route('room.users.update', {room: props.room.id}),
            room_users.value.map(
                (user) => {
                    return {
                        id: user.id,
                        is_admin: user.pivot.is_admin,
                        can_request: user.pivot.can_request,
                    }
                }
            ),
        );
        emits('close');
    },
    removeUserFromRoom = (userId) => {
        room_users.value = room_users.value.filter((user) => user.id !== userId);
    },
    addUserToRoom = (user) => {
        if (room_users.value.find((roomUser) => user.id === roomUser.id)) {
            return;
        }

        user.pivot = {
            is_admin: false,
            can_request: false,
        };

        room_users.value.push(user);
    }
</script>
