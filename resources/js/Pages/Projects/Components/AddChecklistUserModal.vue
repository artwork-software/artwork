<template>
    <BaseModal @closed="emitClose" v-if="true" modal-image="/Svgs/Overlays/illu_checklist_team_assign.svg">
            <div class="mx-3">
                <div class="font-bold font-lexend text-primary text-2xl my-2">
                    {{ $t('Assign users') }}
                </div>
                <div class="text-secondary tracking-tight leading-6 sub">
                    {{ $t('Enter the name of the user to whom you want to assign the checklist.') }}
                </div>
                <div class="mt-10">
                    <UserSearch :only-team="checklist?.private" :team-member="project?.users?.map((user) => user.id)" @user-selected="addUserToChecklist"/>
                </div>
                <div v-for="(user,index) in selectedUsers"
                     class="mt-4 font-bold text-primary flex"
                     :key="index">
                    <div class="flex items-center">
                        <img class="h-5 w-5 mr-2 object-cover rounded-full"
                             :src="user.profile_photo_url"
                             alt=""/>
                        {{ user.first_name }} {{ user.last_name }}
                    </div>
                    <button type="button" @click="removeUser(user)">
                        <span class="sr-only">{{ $t('Remove user from checklist') }}</span>
                        <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error text-white bg-artwork-navigation-background rounded-full"/>
                    </button>
                </div>
                <FormButton
                    @click="submitUsers"
                    :text="$t('Assign')"
                    class="mt-8" />
                <!-- <p v-if="error" class="text-red-800 text-xs">{{ error }}</p> -->
            </div>
    </BaseModal>
</template>

<script>
import {XCircleIcon, XIcon} from '@heroicons/vue/outline';
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";

export default {
    name: 'AddChecklistUserModal',
    mixins: [Permissions],
    components: {
        UserSearch,
        BaseModal,
        FormButton,
        XIcon,
        XCircleIcon,
        TeamIconCollection,
        JetDialogModal,
    },
    emits: ['closed'],
    props: [
        'checklist',
        'users',
        'project'
    ],
    data() {
        return {
            selectedUsers: this.users ?? [],
            searchedUsers: [],
            userQuery: null,
            error: null
        }
    },
    methods: {
        addUserToChecklist(user) {
            if (!this.selectedUsers.find((selected) => selected.id === user.id)) {
                this.selectedUsers.push(user);
            }
            this.userQuery = null;
            this.searchedUsers = [];
        },
        removeUser(user) {
            this.selectedUsers.splice(this.selectedUsers.indexOf(user),1);
        },
        async submitUsers() {
            await axios
                .patch(`/checklists/${this.checklist.id}`, {
                    assigned_user_ids: this.selectedUsers.map((user) => user.id)
                })
                .then(response => this.emitClose())
                .catch(error => this.emitClose());
        },
        emitClose() {
            this.$emit('closed')
        },
    },
    watch: {
        userQuery: {
            handler() {
                if (!this.userQuery) {
                    return
                }
                axios.get('/users/search', {params: {query: this.userQuery}
                }).then(response => {
                    this.searchedUsers = response.data
                })
            },
        },
        users: {
            handler() {
                this.selectedUsers = this.users
            },
            deep: true
        },
    },
}
</script>
