<template>
    <app-layout>
        <div>
            <div class="max-w-screen-lg py-4 pl-20 pr-4">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <form @submit.prevent="editUser">
                        <div>
                            <div class="flex">
                                <img class="mt-6 h-16 w-16 rounded-full flex justify-start"
                                     :src="user_to_edit.profile_photo_url"
                                     alt=""/>
                                <div class="mt-6 flex flex-grow w-full">
                                    <div class="text-3xl  font-lexend font-black flex my-auto ml-6">
                                        {{userForm.first_name}}
                                    </div>
                                    <div class="text-3xl  font-lexend font-black flex my-auto ml-2">
                                        {{userForm.last_name}}
                                    </div>

                                </div>
                            </div>
                            <div class="pt-4">
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.business" placeholder="Unternehmen"
                                                   class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block w-full "/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.position" placeholder="Position"
                                                   class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="this.user_to_edit.email" :disabled="!$page.props.is_admin" :class="$page.props.is_admin ? '' : 'bg-gray-100'"
                                                   class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                                            <jet-input-error :message="userForm.errors.email" class="mt-2"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <div class="mt-1">
                                            <input type="text" v-model="userForm.phone_number"
                                                   placeholder="Telefonnummer"
                                                   class="shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                                        </div>
                                    </div>


                                    <div class="sm:col-span-6">
                                        <div class="mt-1">
                                            <textarea placeholder="Was sollten die anderen ArtWork.tool-User wissen?"
                                                      v-model="userForm.description" rows="5"
                                                      class="resize-none shadow-sm placeholder-secondary p-4 focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border border-gray-300"/>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6 mt-4 flex inline-flex">
                                        <span v-if="userForm.departments.length === 0"
                                              class="text-secondary subpixel-antialiased my-auto mr-4">In keinem Team</span>
                                        <span v-else class="flex -mr-3" v-for="(team,index) in userForm.departments">
                                            <TeamIconCollection class="h-10 w-10 rounded-full ring-2 ring-white"
                                                                :iconName="team.svg_name"/>
                                        </span>
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
                                                            <a href="#" @click="deleteFromAllDepartments"
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
                    <div class="flex mt-6" v-if="$page.props.is_admin">
                        <span @click="resetPassword()" class="text-secondary subpixel-antialiased cursor-pointer">Passwort zurücksetzen</span>
                    </div>
                    <div v-if="password_reset_status" class="mb-4 font-medium text-sm text-green-600">
                        {{ password_reset_status }}
                    </div>

                    <jet-validation-errors class="mb-4" />

                    <div class="pb-5 my-2 border-gray-200 sm:pb-0">
                        <h2 class="text-xl mt-16 mb-8 leading-6 font-black text-gray-900">Nutzerrechte</h2>

                        <div class="mb-8">

                                <div class="relative flex items-center" v-for="(role, index) in available_roles" :key=index>
                                    <div class="flex items-center h-7">
                                        <input :id="role.name"
                                               v-model="userForm.roles"
                                               :value="role.name"
                                               name="roles" type="checkbox"
                                               class="focus:outline-none focus:ring-0 ring-offset-0 ring-0 appearance-none outline-0 h-6 w-6 text-success border-gray-300 border-2" />
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="roles" class="text-primary">{{ role.name_de }}</label>
                                    </div>
                                </div>

                        </div>

                    </div>
                    <div v-if="showUserPermissions" class="flex flex-col">

                        <div v-for="(permissions, group) in all_permissions">

                            <h3 class="text-secondary mt-3 mb-1">{{group}}</h3>

                            <div class="relative flex items-center" v-for="(permission, index) in permissions" :key=index>
                                <div class="flex items-center h-7">
                                    <input :id="permission.name"
                                           v-model="userForm.permissions"
                                           :value="permission.name"
                                           name="permissions" type="checkbox"
                                           class="focus:outline-none focus:ring-0 ring-offset-0 ring-0 appearance-none outline-0 h-6 w-6 text-success border-gray-300 border-2" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="permissions" class="text-primary">{{ permission.name_de }}</label>
                                </div>
                            </div>
                        </div>

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
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-4">
                    <div class="font-black font-lexend text-primary text-3xl my-2">
                        Nutzer*in löschen
                    </div>
                    <XIcon @click="closeDeleteUserModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error subpixel-antialiased">
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
                <img src="/Svgs/Overlays/illu_team_user.svg" class="-ml-6 -mt-8 mb-4" />
                <div class="mx-3">
                    <div class="font-black font-lexend text-primary mt-10 text-3xl my-2">
                        Teamzugehörigkeit
                    </div>
                    <XIcon @click="closeChangeTeamsModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-4 text-secondary subpixel-antialiased leading-6 sub">
                        Gib' an in welchen Teams die/der Nutzer*in ist.<br/> Beachte: Sie/Er hat die Berechtigung alle
                        den Teams zugeordneten <br/>Projekte einzusehen.
                    </div>
                    <div class="mt-8 mb-8">
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
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        Nutzer*in erfolgreich bearbeitet
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success subpixel-antialiased">
                        Die Änderungen wurden erfolgreich gespeichert.
                    </div>
                    <div class="mt-6">
                        <button class="bg-success focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="closeSuccessModal">
                            <CheckIcon class="h-6 w-6 text-secondaryHover"/>
                        </button>
                    </div>
                </div>

            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>

import {Inertia} from "@inertiajs/inertia";


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
    XIcon,
    CheckIcon
} from "@heroicons/vue/outline";
import Checkbox from "@/Layouts/Components/Checkbox";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import JetValidationErrors from '@/Jetstream/ValidationErrors.vue'

export default defineComponent({
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
        TeamIconCollection,
        JetValidationErrors,
        CheckIcon
    },
    props: ['user_to_edit', 'permissions', 'all_permissions', 'departments', 'password_reset_status', 'available_roles'],
    data() {
        return {
            showUserPermissions: true,
            deletingUser: false,
            showChangeTeamsModal: false,
            teamCheckboxes: [],
            showSuccessModal: false,
            userForm: this.$inertia.form({
                first_name: this.user_to_edit.first_name,
                last_name: this.user_to_edit.last_name,
                business: this.user_to_edit.business,
                position: this.user_to_edit.position,
                departments: this.user_to_edit.departments,
                phone_number: this.user_to_edit.phone_number,
                description: this.user_to_edit.description,
                permissions: this.user_to_edit.permissions,
                roles: this.user_to_edit.roles
            }),
            resetPasswordForm: this.$inertia.form({
              email: this.user_to_edit.email
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
        closeSuccessModal(){
            this.showSuccessModal = false;
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
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
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
            this.closeChangeTeamsModal();
            this.openSuccessModal()
        },
        openSuccessModal(){
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        resetPassword() {
            this.resetPasswordForm.post(route('user.reset.password'));
        },
        deleteFromAllDepartments(){
            this.userForm.departments = [];
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
            this.openSuccessModal();
        }
    },
})
</script>
