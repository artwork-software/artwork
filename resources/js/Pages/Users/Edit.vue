<template>
    <app-layout title="Edit Profile">
        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-3 lg:px-12">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <form @submit.prevent="editUser">
                        {{this.userForm}}
                        <div>
                            <div class="flex">
                                <img class="h-16 w-16 rounded-full flex justify-start" :src="user_to_edit.profile_photo_url"
                                     alt=""/>
                                <p class="text-3xl ml-3 my-auto mr-3 font-bold subpixel-antialiased text-primary">
                                    {{ user_to_edit.first_name }} {{ user_to_edit.last_name }}</p>
                            </div>
                            <div class="pt-4">
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.business" placeholder="Unternehmen"
                                                   class="shadow-sm placeholder-secondary focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300"/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.position" placeholder="Position"
                                                   class="shadow-sm placeholder-secondary focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="this.user_to_edit.email" disabled
                                                   class="shadow-sm placeholder-secondary focus:ring-black bg-gray-100 focus:border-black border-2 block w-full sm:text-sm border-gray-300"/>
                                            <jet-input-error :message="userForm.errors.email" class="mt-2"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.phone_number" placeholder="Telefonnummer"
                                                   class="shadow-sm placeholder-secondary focus:ring-black focus:border-black border-2 block w-full sm:text-sm border-gray-300"/>
                                        </div>
                                    </div>


                                    <div class="sm:col-span-6">
                                        <div class="mt-1">
                                            <textarea placeholder="Was sollten die anderen ArtWork.tool-User wissen?"
                                                      v-model="userForm.description" rows="5"
                                                      class="shadow-sm placeholder-secondary p-4 focus:ring-black focus:border-black border-2 block w-full sm:text-sm border border-gray-300"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6 mt-4 flex inline-flex">
                                        <span v-if="userForm.departments.length === 0" class="text-secondary subpixel-antialiased my-auto ml-2 mr-4" >In keinem Team</span>
                                        <span  v-else class="flex" v-for="(team,index) in userForm.departments">
                                            <!--TODO: :src="team.logo_url" -->
                                            <img class="h-14 w-14 rounded-full flex justify-start"
                                                 src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                                 alt=""/>
                                            <button type="button" @click="deleteTeamFromDepartmentsArray(index)"
                                                    class="flex-shrink-0 ml-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:bg-indigo-500 focus:text-white">
                                            <span class="sr-only">Teamzuweisung entfernen</span>
                                            <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                                <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7"/>
                                            </svg>
                                            </button>
                                        </span>
                                        <!-- TODO: FUNKTIONEN einfügen für beide Befehle  -->
                                        <Menu as="div" class="my-auto relative">
                                            <div>
                                                <MenuButton
                                                    class="flex">
                                                    <DotsVerticalIcon class="mr-3 ml-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                                      aria-hidden="true"/>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems class="origin-top-right absolute p-4 mr-4 mt-2 w-80 shadow-lg bg-primary focus:outline-none">
                                                    <div>

                                                        <MenuItem v-slot="{ active }">

                                                            <a href="#" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <PencilAltIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true" />
                                                                Teamzugehörigkeit bearbeiten
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem v-slot="{ active }">
                                                            <a href="#" :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <TrashIcon class="mr-3 h-5 w-5 text-primaryText group-hover:text-white" aria-hidden="true" />
                                                                Nutzer*in aus allen Teams entfernen
                                                            </a>
                                                        </MenuItem>
                                                    </div>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="pb-5 my-2 border-gray-200 sm:pb-0">
                        <h3 class="text-2xl mt-16 mb-8 leading-6 font-bold text-gray-900">Nutzerrechte</h3>

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
                    <div class="mt-8">
                        <div class="flex">
                            <button @click="editUser"
                                    class=" inline-flex items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            >Einstellungen ändern
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex mt-12">
                <span @click="openDeleteUserModal" class="text-secondary subpixel-antialiased cursor-pointer">Nutzer*in endgültig löschen</span>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>

const roleCheckboxes = [
    {name: 'Adminrechte', checked: false, roleName: "admin", showIcon: true},
]


import {computed, defineComponent} from 'vue'
import JetActionMessage from '@/Jetstream/ActionMessage.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetFormSection from '@/Jetstream/FormSection.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetLabel from '@/Jetstream/Label.vue'
import AppLayout from "@/Layouts/AppLayout";
import DeleteUserForm from "@/Pages/Profile/Partials/DeleteUserForm";
import JetSectionBorder from "@/Jetstream/SectionBorder";
import LogoutOtherBrowserSessionsForm from "@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm";
import TwoFactorAuthenticationForm from "@/Pages/Profile/Partials/TwoFactorAuthenticationForm";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm";
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {DotsVerticalIcon,PencilAltIcon,TrashIcon, ChevronDownIcon,ChevronUpIcon} from "@heroicons/vue/outline";
import Checkbox from "@/Layouts/Components/Checkbox";

export default defineComponent({
    computed: {
        userPermissionCheckboxes() {
            return [
                {
                    name: 'Nutzer*innen einladen',
                    checked: this.userForm.permissions.includes("invite users"),
                    permissionName: "invite users",
                    showIcon: true
                },
                {
                    name: 'Nutzerprofile ansehen',
                    checked: this.userForm.permissions.includes("view users"),
                    permissionName: "view users",
                    showIcon: true
                },
                {
                    name: 'Nutzerprofile bearbeiten',
                    checked: this.userForm.permissions.includes("update users"),
                    permissionName: "update users",
                    showIcon: true
                },
                {
                    name: 'Nutzer*innen löschen',
                    checked: this.userForm.permissions.includes("delete users"),
                    permissionName: "delete users",
                    showIcon: true
                }
            ]
        },
    },
    name: 'Edit',
    components: {
        DotsVerticalIcon,
        PencilAltIcon,
        TrashIcon,
        JetActionMessage,
        JetButton,
        JetFormSection,
        JetInput,
        JetInputError,
        JetLabel,
        AppLayout,
        DeleteUserForm,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        UpdateProfileInformationForm,
        JetSecondaryButton,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Checkbox,
        ChevronDownIcon,
        ChevronUpIcon
    },
    props: ['user_to_edit', 'permissions'],
    data() {
        return {
            showUserPermissions: true,
            userForm: this.$inertia.form({
                first_name: this.user_to_edit.first_name,
                last_name:this.user_to_edit.last_name,
                business: this.user_to_edit.business,
                position: this.user_to_edit.position,
                departments: this.user_to_edit.departments,
                phone_number: this.user_to_edit.phone_number,
                description: this.user_to_edit.description,
                permissions: this.user_to_edit.permissions,
            }),
        }
    },

    methods: {
        openDeleteUserModal(){

        },
        editUser() {
            this.userPermissionCheckboxes.forEach((item) =>
            {
                // HIER STIMMT NOCH WAS NICHT EINE PERMISSION WAR 2 MAL IM BODY
                if(this.userForm.permissions.includes(item.permissionName)){
                    if(!item.checked){
                        this.userForm.permissions.splice(this.userForm.permissions.indexOf(item.permissionName),1);
                    }
                }
                if(item.checked){
                    this.userForm.permissions.push(item.permissionName);
                }
            })
            console.log(this.userForm);
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
        },
        deleteTeamFromDepartmentsArray(index) {
            this.userForm.departments.splice(index, 1);
        },
    },
    setup() {

        return {
            roleCheckboxes
        }
    }
})
</script>
