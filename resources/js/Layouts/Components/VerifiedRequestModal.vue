<template>
    <ArtworkBaseModal @close="$emit('close')" v-if="show" :title="verifiedTexts.title" :description="verifiedTexts.description">
        <div class="mx-4">
            <div class="mb-2">
                <div class="relative w-full">
                    <div class="w-full" v-if="showUserAdd">
                        <BaseInput
                            id="userSearch"
                            v-model="userQuery"
                            :label="$t('Who should verify your calculation?')"
                        />
                    </div>
                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0">
                        <div v-if="filteredSearchResults.length > 0 && userQuery.length > 0"
                             class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                    text-base ring-1 ring-black ring-opacity-5
                                    overflow-auto focus:outline-none sm:text-sm">
                            <div class="border-gray-200">
                                <div v-for="(user, index) in filteredSearchResults" :key="index"
                                     class="flex items-center cursor-pointer">
                                    <div class="flex-1 text-sm py-4">
                                        <p @click="addUser(user)"
                                           class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                            {{ user.first_name }} {{ user.last_name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
                <div v-if="selectedUser" class="mt-2 mb-4 flex items-center">
                    <span class="flex mr-5 rounded-full items-center font-bold text-primary">
                        <div class="flex items-center">
                            <img class="flex h-11 w-11 rounded-full object-cover"
                                 :src="selectedUser.profile_photo_url" alt=""/>
                            <span class="flex ml-4 sDark">
                                {{ selectedUser.first_name }} {{ selectedUser.last_name }}
                            </span>
                            <button type="button" @click="removeUser">
                                <span class="sr-only">{{ $t('Remove user from money source') }}</span>
                                <PropertyIcon name="IconX" stroke-width="1.5"
                                       class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-artwork-buttons-create text-white border-0 "/>
                            </button>
                        </div>
                    </span>
                </div>
            </div>
            <div class="mt-6 flex justify-center">
                <button class="focus:outline-none my-auto inline-flex items-center px-10 py-3 border border-transparent
                            text-xs font-bold uppercase shadow-sm text-white rounded-full bg-artwork-buttons-create"
                        @click="submit">
                    {{ $t('Request verification') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import axios from "axios";

export default {
    name: 'VerifiedRequestModal',
    components: {
        ArtworkBaseModal,
        ModalHeader,
        BaseInput,
        PropertyIcon,
    },
    props: {
        show: {
            type: Boolean,
            default: false,
        },
        verifiedTexts: {
            type: Object,
            default: () => ({
                title: '',
                positionTitle: '',
                description: '',
            }),
        },
        budgetAccess: {
            type: Array,
            default: () => [],
        },
        projectMembers: {
            type: Array,
            default: () => [],
        },
    },
    emits: ['close', 'submit'],
    data() {
        return {
            userQuery: '',
            userSearchResults: [],
            selectedUser: null,
            showUserAdd: true,
        };
    },
    computed: {
        budgetAccessIds() {
            return this.budgetAccess?.map(u => u.id ?? u) ?? [];
        },
        filteredSearchResults() {
            return this.userSearchResults.filter(user => {
                return this.budgetAccessIds.includes(user.id) || this.projectMembers.includes(user.id);
            });
        },
    },
    watch: {
        userQuery(val) {
            if (val.length > 0) {
                axios.get('/users/search', {
                    params: { query: val }
                }).then(response => {
                    this.userSearchResults = response.data;
                });
            } else {
                this.userSearchResults = [];
            }
        },
        show(val) {
            if (!val) {
                this.reset();
            }
        },
    },
    methods: {
        addUser(user) {
            this.selectedUser = user;
            this.userQuery = '';
            this.showUserAdd = false;
        },
        removeUser() {
            this.selectedUser = null;
            this.showUserAdd = true;
        },
        submit() {
            if (!this.selectedUser) return;
            this.$emit('submit', this.selectedUser);
        },
        reset() {
            this.userQuery = '';
            this.userSearchResults = [];
            this.selectedUser = null;
            this.showUserAdd = true;
        },
    },
};
</script>
