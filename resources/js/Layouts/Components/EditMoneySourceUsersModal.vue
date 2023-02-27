<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_team.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-3">
                <div class="font-black font-lexend text-primary text-3xl my-2">
                    Freigabe verwalten
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="xsLight">
                    Tippe den Namen der Nutzer*innen, denen du Zugriff zur Quelle erteilen möchtest.
                </div>
                <div class="mb-2">
                    <div class="relative w-full">
                        <div class="w-full">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   placeholder="Wer ist zuständig?"
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
                        <span v-for="user in assignedUsers"
                              class="flex justify-between mt-4 mr-1 items-center font-bold text-primary border-1 border-b pb-3">
                            <div class="flex items-center w-64">
                                <div class="flex items-center">
                                    <img class="flex h-11 w-11 rounded-full"
                                         :src="user.profile_photo_url"
                                         alt=""/>
                                    <span class="flex ml-4">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                                </div>
                                <button type="button" @click="deleteUserFromMoneySourceUserArray(user)">
                                    <span class="sr-only">User aus Team entfernen</span>
                                    <XCircleIcon class="ml-3 text-buttonBlue h-5 w-5 hover:text-error "/>
                                </button>
                            </div>
                            <div class="flex justify-between items-center my-1.5 h-5 w-80">
                                <div class="flex items-center justify-between" v-if="checkUserAuth(user)">
                                   <div class="flex">
                                        <input v-model="user.can_write"
                                               type="checkbox"
                                               class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                    <p :class="[user.can_write ? 'text-primary font-black' : 'text-secondary']"
                                       class="ml-4 my-auto text-sm">Schreibrecht</p>
                                   </div>
                                    <Dropdown :open="user.openedMenu" align="right" width="60" class="text-right">
                                        <template #trigger>
                                            <span class="inline-flex">
                                                <button @click="user.openedMenu = !user.openedMenu" type="button"
                                                        class="text-sm flex items-center ml-14 my-auto text-primary font-semibold focus:outline-none transition">
                                                    Weitere Rechte
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <div class="w-44 p-4">
                                                <div class="flex">
                                                    <input v-model="user.access_budget"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p
                                                    class=" ml-4 my-auto text-sm text-secondary">Budgetzugriff</p>
                                                </div>
                                                <div class="flex mt-4" v-if="user.project_management">
                                                    <input v-model="user.is_manager"
                                                           type="checkbox"
                                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                                <p
                                                    class="ml-4 my-auto text-sm text-secondary">Projektleitung</p>
                                                </div>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </span>
                </div>
                <div class="w-full items-center text-center">
                    <AddButton @click="editMoneySourceUsers" text="Speichern" mode="modal"
                               class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold tracking-wider text-lg shadow-sm text-secondaryHover"
                    />
                </div>
            </div>
        </template>

    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";


import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import AddButton from "@/Layouts/Components/AddButton.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/inertia-vue3";
import Dropdown from "@/Jetstream/Dropdown.vue";

export default {
    name: 'AddBudgetTemplateComponent',

    components: {
        Dropdown,
        AddButton,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        InputComponent,
        XCircleIcon
    },

    data() {
        return {
            templateName: '',
            user_search_results: [],
            user_query: '',
            usersToAdd: this.moneySource.users,
            assignedUsers: this.moneySource.users ? this.moneySource.users : [],
            form: useForm({
                assigned_user_ids: {},
            }),
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


    props: ['moneySource'],

    emits: ['closed'],

    methods: {
        openModal() {
        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
        editMoneySourceUsers() {
            this.form.assigned_user_ids = {};
            this.assignedUsers.forEach(user => {
                this.form.assigned_user_ids[user.id] = {
                    access_budget: user.access_budget,
                    is_manager: user.is_manager,
                    can_write: user.can_write
                };
            })
            this.form.patch(route('money_sources.update_users',{moneySource: this.moneySource.id}));
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
