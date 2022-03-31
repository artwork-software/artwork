<template>
    <app-layout title="Dashboard">
        <div class="py-12">
            <div class="max-w-screen-2xl my-12 flex flex-row mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-1 flex-wrap justify-between">
                    <div class="flex">
                        <div class="w-full flex my-auto">
                            <h2 class="text-2xl">Alle Nutzer*innen</h2>
                            <button @click="openAddUserModal" type="button"
                                    class="flex my-auto ml-6 pr-2 items-center font-bold p-1 border border-transparent rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <PlusSmIcon class="h-5 w-5 " aria-hidden="true"/>
                                User hinzufügen
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="inset-y-0 mr-3 pointer-events-none">
                            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                        </div>
                    </div>
                    <ul role="list" class="mt-4 divide-y divide-gray-200 w-full">
                        <li v-for="person in users.data" :key="person.email" class="py-4 flex justify-between">
                            <div class="flex">
                            <img class="h-14 w-14 rounded-full flex justify-start" :src="person.profile_photo_url"
                                 alt=""/>
                            <div class="ml-3 my-auto w-full justify-start mr-6">
                                <div class="flex my-auto">
                                    <p class="text-lg mr-3 font-semibold text-gray-900">{{ person.last_name }}, {{ person.first_name }} </p>
                                    <p class="ml-1 text-sm font-medium text-gray-900 my-auto"> {{ person.business }},
                                        {{ person.position }}</p>
                                </div>
                            </div>
                            </div>
                            <div class="flex">
                                <div class="mt-3 mr-6" v-for="department in users.data">
                                    <img class="h-8 w-8 rounded-full"
                                         :src="department.profile_photo_url"
                                         alt=""/>
                                </div>
                                <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                  aria-hidden="true"/>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Nutzer*innen einladen Modal -->
        <jet-dialog-modal :show="addingUser" @close="closeAddUserModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-2xl my-2">
                        Nutzer*innen einladen
                    </div>
                    <XIcon @click="closeAddUserModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer" aria-hidden="true" />
                    <div class="text-gray-500">
                        Du kannst mehrere Nutzer*innen mit den gleichen Nutzerrechten und Teamzugehörigkeiten auf einmal
                        einladen.
                    </div>
                    <div class="mt-4">
                        <jet-input type="text" class="mt-1 block w-3/4" placeholder="E-Mail-Adresse"
                                   v-model="emailInput"
                        />
                        <jet-input-error :message="form.error" class="mt-2"/>
                        <jet-button class="mt-2" @click="addEmailToInvitationArray" :disabled="!emailInput">
                            Weitere E-Mail-Adresse hinzufügen
                        </jet-button>
                        <h4 class="mt-2 mb-1" v-show="this.form.user_emails.length >= 1">Bereits eingegebene
                            Emails:</h4>
                        <span v-for="(email,index) in form.user_emails"
                              class="inline-flex mr-1 rounded-full items-center py-0.5 pl-2.5 pr-1 text-sm font-medium bg-indigo-100 text-indigo-700">
                            {{ email }}
                    <button type="button" @click="deleteEmailFromInvitationArray(index)"
                            class="flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:bg-indigo-500 focus:text-white">
                    <span class="sr-only">Email aus Einladung entfernen</span>
                        <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                             <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7"/>
                        </svg>
                    </button>
                    </span>
                        <h2 class="mt-2">Teams:</h2>
                        <span class="flex" v-for="(team,index) in form.departments">
                                <img   class="h-14 w-14 rounded-full flex justify-start" :src="team.logo_url"
                                       alt=""/>
                                <button type="button" @click="deleteTeamFromDepartmentsArray(index)"
                                        class="flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:bg-indigo-500 focus:text-white">
                                    <span class="sr-only">Teamzuweisung entfernen</span>
                                    <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                        <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7"/>
                                    </svg>
                                </button>
                             </span>
                        <Menu as="div" class="relative">
                            <div>
                                <MenuButton
                                    class="mt-2 flex my-auto items-center font-bold p-1 rounded-full shadow-sm text-white bg-black">
                                    <PlusSmIcon class="h-5 w-5 " aria-hidden="true"/>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <MenuItem v-for="team in departments" v-slot="{ active }">
                                        <span>
                                            <img   class="h-6 w-6 rounded-full flex justify-start" :src="team.logo_url"
                                               alt=""/>
                                            {{ team.name }}
                                        </span>
                                    </MenuItem>
                                </MenuItems>
                            </transition>
                        </Menu>
                        <div class="pb-5 my-2 border-gray-200 sm:pb-0">
                            <h3 class="text-xl mt-6 mb-8 leading-6 font-bold text-gray-900">Nutzerrechte definieren</h3>

                            <div class="mb-8">
                                <Checkbox v-for="role in roleCheckboxes" class="justify-between" :item=role></Checkbox>
                            </div>

                        </div>
                        <div v-on:click="showUserPermissions = !showUserPermissions">
                            <h2 class="text-sm flex text-gray-400 mb-2">
                                Nutzer*innen  <ChevronUpIcon v-if="showUserPermissions" class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon> <ChevronDownIcon v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                            </h2>
                        </div>
                        <div v-if="showUserPermissions" class="flex flex-col">
                            <Checkbox v-for="permission in permissionCheckboxes" class="justify-between" :item=permission />
                        </div>
                    </div>
                    <Button :class="[this.form.user_emails.length === 0 ? 'bg-gray-400': 'bg-indigo-900 hover:bg-indigo-700 focus:outline-none']" class="mt-4 inline-flex items-center px-20 py-3 border border-transparent text-base font-bold uppercase shadow-sm text-white " @click="addUser"
                                :disabled="this.form.user_emails.length === 0">
                        Einladen
                    </Button>
                </div>

            </template>




        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

const roleCheckboxes = [
    {name: 'Adminrechte', checked: false, roleName: "admin", showIcon: true},
]

const permissionCheckboxes = [
    {name: 'Nutzer*innen einladen', checked: false, permissionName: "invite users", showIcon: true},
    {name: 'Nutzerprofile ansehen', checked: false, permissionName: "view users", showIcon: true},
    {name: 'Nutzerprofile bearbeiten', checked: false, permissionName: "update users", showIcon: true},
    {name: 'Nutzer*innen löschen', checked: false, permissionName: "delete users", showIcon: true}
]
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {DotsVerticalIcon} from '@heroicons/vue/outline'
import {ChevronDownIcon, ChevronUpIcon, PlusSmIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";
import JetButton from '@/Jetstream/Button.vue'
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {InformationCircleIcon, XIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import Checkbox from "@/Layouts/Components/Checkbox";


export default defineComponent({
    components: {
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        InformationCircleIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
        XIcon
    },
    props: ['users','departments'],
    data() {
        return {
            showUserPermissions: true,
            addingUser: false,
            emailInput: "",
            form: useForm({
                user_emails: [],
                permissions:[],
                departments: [],
            }),
        }
    },
    methods: {
        openAddUserModal() {
            this.addingUser = true
        },
        addEmailToInvitationArray() {
            if (this.emailInput.indexOf(' ') === -1) {
                this.form.user_emails.push(this.emailInput);
            }
            this.emailInput = "";
        },
        deleteEmailFromInvitationArray(index) {
            this.form.user_emails.splice(index, 1);
        },
        addUser() {
            if (this.emailInput.length >= 3) {
                this.form.user_emails.push(this.emailInput);
            }
            permissionCheckboxes.forEach((item) =>
            {
                if(item.checked){
                    this.form.permissions.push(item.permissionName);
                }
            })
            console.log('EMAIL ARRAY:' + this.form.user_emails)
            console.log('PERMISSION CHECKED' + this.form.permissions)
            console.log('TEAM ARRAY' + this.form.departments)
            this.form.post(route('invitations.store'), {

            })
            this.closeAddUserModal();
        },
        closeAddUserModal() {
            this.addingUser = false;
            this.emailInput = "";
            this.form.user_emails = [];
            this.form.permissions = [];
            this.form.departments = [];

        },
        deleteTeamFromDepartmentsArray(index){
            this.form.departments.splice(index,1);
        }
    },
    setup() {
        return {
            permissionCheckboxes,
            roleCheckboxes
        }
    }
})
</script>
