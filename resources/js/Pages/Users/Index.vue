<template>
    <app-layout title="Dashboard">
        <div class="py-4">
            <div class="max-w-screen-2xl my-12 flex flex-row mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-1 flex-wrap justify-between">
                    <div class="flex">
                        <div class="w-full flex my-auto">
                            <h2 class="text-2xl">Alle Nutzer*innen</h2>
                            <button @click="openAddUserModal" type="button"
                                    class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="inset-y-0 mr-3 pointer-events-none">
                            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                        </div>
                    </div>
                    <ul role="list" class="mt-6 w-full">
                        <li v-for="user in users.data" :key="user.email" class="py-6 flex justify-between">
                            <div class="flex">
                            <img class="h-14 w-14 rounded-full flex justify-start" :src="user.profile_photo_url"
                                 alt=""/>
                            <div class="ml-3 my-auto w-full justify-start mr-6">
                                <div class="flex my-auto">
                                    <p class="text-lg mr-3 font-semibold subpixel-antialiased text-primary">{{ user.last_name }}, {{ user.first_name }} </p>
                                    <p class="ml-1 text-sm font-medium text-primary my-auto"> {{ user.business }},
                                        {{ user.position }}</p>
                                </div>
                            </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8">
                                <div class="mt-3 -mr-3" v-for="department in user.departments">
                                    <img class="h-9 w-9 rounded-full"
                                         src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                         alt=""/>
                                    <!-- TODO: DEPARTMENT FOTO EINBAUEN und was wenn zu viele Departments
                                    <img class="h-8 w-8 rounded-full"
                                         :src="department.profile_photo_url"
                                         alt=""/>
                                         -->
                                </div>
                                </div>
                                <Menu as="div" class="my-auto relative">
                                    <div>
                                        <MenuButton
                                            class="flex">
                                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                              aria-hidden="true"/>
                                        </MenuButton>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems class="origin-top-right absolute right-0 mr-4 mt-2 w-56 shadow-lg bg-primary focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem v-slot="{ active }">
                                                    <a :href="getEditHref(user)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true" />
                                                        Nutzer*in bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a @click="openDeleteUserModal(user)" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <TrashIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true" />
                                                        Nutzer*in löschen
                                                    </a>
                                                </MenuItem>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>
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
                    <div class="font-bold text-primary text-2xl my-2">
                        Nutzer*innen einladen
                    </div>
                    <XIcon @click="closeAddUserModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer" aria-hidden="true" />
                    <div class="text-secondary">
                        Du kannst mehrere Nutzer*innen mit den gleichen Nutzerrechten und Teamzugehörigkeiten auf einmal
                        einladen.
                    </div>
                    <div class="mt-4">
                        <jet-input type="text" class="mt-1 block w-3/4" placeholder="E-Mail-Adresse"
                                   v-model="emailInput"
                        />
                        <jet-input-error :message="form.error" class="mt-2"/>
                        <button :class="[emailInput === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', ' mt-2 inline-flex items-center px-4 py-2 border border-transparent uppercase shadow-sm text-secondaryHover']" @click="addEmailToInvitationArray" :disabled="!emailInput">
                            {{form.user_emails.length >= 1 ? 'Weitere ' : '' }}E-Mail-Adresse hinzufügen
                        </button>
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
                        <h2 class="mt-2">Teams zuweisen:</h2>
                        <span class="flex inline-flex -mr-3" v-for="(team,index) in form.departments">
                            <!--TODO: :src="team.logo_url" -->
                                <img   class="h-14 w-14 rounded-full flex justify-start" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                       alt=""/>
                        </span>
                        <Disclosure @focusout="close()" as="div" class="relative">
                            <div>
                                <DisclosureButton
                                    class="mt-2 flex my-auto items-center font-bold p-1 rounded-full shadow-sm text-white bg-black">
                                    <PlusSmIcon class="h-6 w-6" aria-hidden="true"/>
                                </DisclosureButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <DisclosurePanel
                                    class="origin-top-right absolute overflow-y-auto max-h-48  mt-2 w-72 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <div v-if="departments.length === 0">
                                        <span class="text-secondary p-1 ml-4 flex flex-nowrap">Keine Teams zum Zuweisen vorhanden</span>
                                    </div>
                                    <div v-for="team in departments">
                                        <span class="flex " :class="[team.checked ? 'text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-md subpixel-antialiased']">
                                            <!--TODO: :src="team.logo_url" -->
                                            <input :key="team.name" v-model="team.checked" type="checkbox" @change="teamChecked(team,index)"
                                                   class="mr-3 ring-offset-0 focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-secondary"/>
                                            <img class="h-8 w-8 rounded-full flex justify-start" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                               alt=""/>
                                            <span class="ml-2">
                                            {{ team.name }}
                                            </span>
                                        </span>
                                    </div>
                                </DisclosurePanel>
                            </transition>
                        </Disclosure>
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
                            <Checkbox v-for="permission in userPermissionCheckboxes" class="justify-between" :item=permission />
                        </div>
                    </div>

                    <button :class="[form.user_emails.length === 0 ?
                    'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-4 inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="addUser" :disabled="form.user_emails.length === 0">
                        Einladen
                    </button>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Nutzer*in löschen Modal -->
        <jet-dialog-modal :show="deletingUser" @close="closeDeleteUserModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Nutzer*in löschen
                    </div>
                    <XIcon @click="closeDeleteUserModal" class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer" aria-hidden="true" />
                    <div class="text-error">
                        Bist du sicher, dass du {{userToDelete.last_name + "," }} {{ userToDelete.first_name}} aus dem System löschen möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteUser">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()" class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>


import {Inertia} from "@inertiajs/inertia";

const roleCheckboxes = [
    {name: 'Adminrechte', checked: false, roleName: "admin", showIcon: true},
]

const userPermissionCheckboxes = [
    {name: 'Nutzer*innen einladen', checked: false, permissionName: "invite users", showIcon: true},
    {name: 'Nutzerprofile ansehen', checked: false, permissionName: "view users", showIcon: true},
    {name: 'Nutzerprofile bearbeiten', checked: false, permissionName: "update users", showIcon: true},
    {name: 'Nutzer*innen löschen', checked: false, permissionName: "delete users", showIcon: true}
]

import {defineComponent} from 'vue'
import {Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import AppLayout from '@/Layouts/AppLayout.vue'
import {DotsVerticalIcon, PencilAltIcon, TrashIcon} from '@heroicons/vue/outline'
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
        XIcon,
        PencilAltIcon,
        TrashIcon,
        Disclosure,
        DisclosureButton,
        DisclosurePanel
    },
    props: ['users','departments'],
    data() {
        return {
            showUserPermissions: true,
            addingUser: false,
            deletingUser: false,
            userToDelete:{},
            emailInput: "",
            form: useForm({
                user_emails: [],
                permissions:[],
                departments: [],
            }),
        }
    },
    methods: {
        openDeleteUserModal(user) {
            this.userToDelete = user;
            this.deletingUser = true;
        },
        closeDeleteUserModal(){
            this.userToDelete = {};
            this.deletingUser = false;
        },
        deleteUser(){
            Inertia.delete(`/users/${this.userToDelete.id}`);
            this.closeDeleteUserModal()
        },
        openAddUserModal() {
            this.addingUser = true
        },
        teamChecked(team,index){
            if(team.checked){
                this.form.departments.push(team);
            }else{
                this.form.departments.splice(index,1);
            }
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
            userPermissionCheckboxes.forEach((item) =>
            {
                if(item.checked){
                    this.form.permissions.push(item.permissionName);
                }
            })
            console.log('EMAIL ARRAY:' + this.form.user_emails)
            console.log('PERMISSION CHECKED' + this.form.permissions)
            console.log('TEAM ARRAY' + this.form.departments)

            this.form.post(route('invitations.store'));
            this.closeAddUserModal();
        },
        closeAddUserModal() {
            this.addingUser = false;
            this.emailInput = "";
            this.form.user_emails = [];
            this.form.permissions = [];
            this.form.departments = [];
            this.departments.forEach((team) =>{
                team.checked = false;
            })

        },
        deleteTeamFromDepartmentsArray(team,index){
            team.checked = false;
            this.form.departments.splice(index,1);
        },
        getEditHref(user){
            return route('user.edit',{ user: user.id});
        }
    },
    setup() {
        return {
            userPermissionCheckboxes,
            roleCheckboxes
        }
    }
})
</script>
