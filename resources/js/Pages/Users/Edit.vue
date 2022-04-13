<template>
    <app-layout title="Edit Profile">
        <div>
            <div class="max-w-screen-lg py-4 pl-20 pr-4">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    {{user_to_edit}}
                    <form @submit.prevent="editUser">
                        <div>
                            <div class="flex">
                                <img class="mt-6 h-16 w-16 rounded-full flex justify-start"
                                     :src="user_to_edit.profile_photo_url"
                                     alt=""/>
                                <div class="mt-6 flex flex-grow w-full">
                                    <div class="relative flex w-5/12 ml-6 mr-4">
                                        <input id="first_name" v-model="userForm.first_name" type="text"
                                               class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                               placeholder="placeholder"/>
                                        <label for="first_name"
                                               class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                                    </div>
                                    <div class="relative flex-grow w-6/12">
                                        <input id="last_name" v-model="userForm.last_name" type="text"
                                               class="peer pl-0 h-16 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-xl font-bold text-primary placeholder-secondary placeholder-transparent"
                                               placeholder="placeholder"/>
                                        <label for="last_name"
                                               class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Nachname</label>
                                    </div>
                                </div>
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
                                            <!-- TODO: EMAIL NUR NICHT DISABLED WENN ROLLE VOM ANGEMELDETEM NUTZER ADMIN IST -->
                                            <input type="text" v-model="this.user_to_edit.email" disabled
                                                   class="shadow-sm placeholder-secondary focus:ring-black bg-gray-100 focus:border-black border-2 block w-full sm:text-sm border-gray-300"/>
                                            <jet-input-error :message="userForm.errors.email" class="mt-2"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.phone_number"
                                                   placeholder="Telefonnummer"
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
                                        <span v-if="userForm.departments.length === 0"
                                              class="text-secondary subpixel-antialiased my-auto ml-2 mr-4">In keinem Team</span>
                                        <span v-else class="flex -mr-3" v-for="(team,index) in userForm.departments">
                                            <TeamIconCollection class="h-10 w-10 rounded-full ring-2 ring-white"
                                                                :iconName="team.svg_name"/>
                                        </span>
                                        <!-- TODO: FUNKTIONEN einfügen für aus allen Teams entfernen Befehle  -->
                                        <Menu as="div" class="my-auto relative">
                                            <div>
                                                <MenuButton
                                                    class="flex">
                                                    <DotsVerticalIcon
                                                        class="mr-3 ml-6 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                        aria-hidden="true"/>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems
                                                    class="origin-top-right absolute p-4 mr-4 mt-2 w-80 shadow-lg bg-primary focus:outline-none">
                                                    <div>

                                                        <MenuItem v-slot="{ active }">

                                                            <a href="#" @click="openChangeTeamsModal"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <PencilAltIcon
                                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                    aria-hidden="true"/>
                                                                Teamzugehörigkeit bearbeiten
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem v-slot="{ active }">
                                                            <a href="#"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <TrashIcon
                                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                    aria-hidden="true"/>
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
                            Nutzer*innen
                            <ChevronUpIcon v-if="showUserPermissions"
                                           class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                            <ChevronDownIcon v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                        </h2>
                    </div>
                    <div v-if="showUserPermissions" class="flex flex-col">
                        <Checkbox v-for="permission in userPermissionCheckboxes" class="justify-between"
                                  :item=permission />
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
                    <span @click="openDeleteUserModal()" class="text-secondary subpixel-antialiased cursor-pointer">Nutzer*in endgültig löschen</span>
                </div>
            </div>
        </div>
        <!-- Nutzer*in löschen Modal -->
        <jet-dialog-modal :show="deletingUser" @close="closeDeleteUserModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Nutzer*in löschen
                    </div>
                    <XIcon @click="closeDeleteUserModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Bist du sicher, dass du {{ user_to_edit.last_name + "," }} {{ user_to_edit.first_name }} aus dem
                        System löschen möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteUser">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Change Teams Modal -->
        <jet-dialog-modal :show="showChangeTeamsModal" @close="closeChangeTeamsModal">
            <template #content>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Teamzugehörigkeit
                    </div>
                    <XIcon @click="closeChangeTeamsModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Gib' an in welchen Teams die/der Nutzer*in ist.<br/> Beachte: Sie/Er hat die Berechtigung alle
                        den Teams zugeordneten <br/>Projekte einzusehen.
                    </div>
                    <div class="mt-4">
                        <span v-if="departments.length === 0"
                              class="text-secondary flex mb-6 mt-8 subpixel-antialiased my-auto">Bisher sind keine Teams im Tool angelegt.</span>
                        <div v-for="team in departments">
                                        <span class=" flex items-center pr-4 py-2 text-md subpixel-antialiased">
                                            <input :key="team.name" type="checkbox" :value="team" :id="team.id" v-model="team.checked"
                                                   @change="teamChecked(team)"
                                                   class="mr-3 ring-offset-0 focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-secondary"/>
                                            <TeamIconCollection class="h-9 w-9 rounded-full ring-2 ring-white"
                                                                :iconName="team.svg_name"/>
                                            <span :class="[team.checked ? 'text-primary font-black' : 'text-secondary']"
                                                  class="ml-2">
                                            {{ team.name }}
                                            </span>
                                        </span>
                        </div>
                    </div>
                    <button class="mt-4 bg-primary hover:bg-primaryHover focus:outline-none inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="saveNewTeams">
                        Speichern
                    </button>
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
import {
    DotsVerticalIcon,
    PencilAltIcon,
    TrashIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    XIcon
} from "@heroicons/vue/outline";
import Checkbox from "@/Layouts/Components/Checkbox";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";

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
        ChevronUpIcon,
        JetDialogModal,
        XIcon,
        TeamIconCollection
    },
    props: ['user_to_edit', 'permissions', 'departments'],
    data() {
        return {
            showUserPermissions: true,
            deletingUser: false,
            showChangeTeamsModal: false,
            teamCheckboxes: [],
            userForm: this.$inertia.form({
                first_name: this.user_to_edit.first_name,
                last_name: this.user_to_edit.last_name,
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
        openDeleteUserModal() {
            this.deletingUser = true;
        },
        closeDeleteUserModal() {
            this.deletingUser = false;
        },
        openChangeTeamsModal() {
            this.departments.forEach((team) =>{
                this.userForm.departments.forEach((userTeam) => {
                    if(userTeam.id === team.id) {
                        team.checked = true;
                    }})
                })
            this.showChangeTeamsModal = true;
        },
        closeChangeTeamsModal() {
            this.showChangeTeamsModal = false;
        },
        deleteUser() {
            Inertia.delete(`/users/${this.user_to_edit.id}`);
            this.closeDeleteUserModal()
        },
        editUser() {
            this.userPermissionCheckboxes.forEach((item) => {
                if (item.checked) {
                    if (this.userForm.permissions.includes(item.permissionName)) {
                        // do nothing if already in permissions array
                    } else {
                        this.userForm.permissions.push(item.permissionName);
                    }
                } else {
                    if (this.userForm.permissions.includes(item.permissionName)) {
                        this.userForm.permissions.splice(this.userForm.permissions.indexOf(item.permissionName), 1);
                    }
                }
            })
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
        },
        deleteTeamFromDepartmentsArray(index) {
            this.userForm.departments.splice(index, 1);
        },
        teamChecked(team) {
            if (team.checked) {
                this.userForm.departments.push(team);
            } else {
                const spliceIndex = this.userForm.departments.findIndex(teamToSplice => {
                    return team.id === teamToSplice.id
                })
                this.userForm.departments.splice(spliceIndex, 1);
            }
        },
        saveNewTeams() {
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
        }
    },
    setup() {

        return {
            roleCheckboxes
        }
    }
})
</script>
