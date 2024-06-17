<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-3">
                <div class="font-black font-lexend text-primary text-3xl my-6">
                    {{ $t('Manage release')}}
                </div>
                <div class="xsLight">
                    {{ $t('Type the name of the users you want to give access to the source. You can only select users who are authorized to edit a funding source. Authorized users automatically have access to the source.')}}
                </div>
                <div class="mb-2 mt-6">
                    <div class="relative w-full">
                        <div class="w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   :placeholder="$t('Search for users')"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                 class="absolute z-20 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUserToMoneySourceUserArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 w-full border-1 border-b pb-3 mt-4" v-for="user in assignedUsers">
                        <div class="flex col-span-2">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <span class="flex ml-4">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteUserFromMoneySourceUserArray(user)">
                                <span class="sr-only">{{ $t('Remove user from team')}}</span>
                                <IconCircleX stroke-width="1.5" class="ml-3 text-artwork-buttons-create h-5 w-5 hover:text-error "/>
                            </button>
                        </div>
                        <div class="col-span-3 flex">
                            <div class="flex items-center justify-between" v-if="user.pivot">
                                <div class="flex">
                                    <input v-model="user.pivot.competent"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.pivot.competent ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">{{ $t('Responsible')}}</p>
                                </div>
                                <div class="flex ml-8">
                                    <input v-model="user.pivot.write_access"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.pivot.write_access ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">{{ $t('Write and delete permission')}}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between" v-else>
                                <div class="flex">
                                    <input v-model="user.competent"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.competent ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">{{ $t('Responsible')}}</p>
                                </div>
                                <div class="flex ml-8">
                                    <input v-model="user.write_access"
                                           type="checkbox"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.write_access ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">{{ $t('Write and delete permission')}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-full items-center text-center">
                    <FormButton @click="editMoneySourceUsers" :text="$t('Save')"
                    />
                </div>
            </div>
    </BaseModal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";


import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import Dropdown from "@/Jetstream/Dropdown.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'AddBudgetTemplateComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        Dropdown,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        InputComponent,
        XCircleIcon
    },

    data() {
        return {
            user_search_results: [],
            user_query: '',
            assignedUsers: this.moneySource.users ? this.moneySource.users : [],
        }
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/money_source_search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },


    props: ['moneySource'],

    emits: ['closed'],

    methods: {
        openModal() {
        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
        editMoneySourceUsers() {
            let users = {};
            this.assignedUsers.forEach(user => {
                if(user.pivot){
                    users[user.id] = {
                        user_id: user.id,
                        competent: user.pivot.competent,
                        write_access: user.pivot.write_access
                    };
                }else{
                    users[user.id] = {
                        user_id: user.id,
                        competent: user.competent,
                        write_access: user.write_access
                    };
                }

            })
            this.$inertia.patch(route('money_sources.update_users',{moneySource: this.moneySource.id}),{
                users: users,
            });
            this.closeModal(true);
        },
        deleteUserFromMoneySourceUserArray(user) {
            if (this.assignedUsers.includes(user)) {
                this.assignedUsers.splice(this.assignedUsers.indexOf(user), 1);
            }
        },
        addUserToMoneySourceUserArray(userToAdd) {
            for (let assignedUser of this.assignedUsers) {
                if (userToAdd.id === assignedUser.id) {
                    this.user_query = ""
                    return;
                }
            }
            this.assignedUsers.push(userToAdd);
            this.user_query = ""
        },
    },
}
</script>

<style scoped></style>
