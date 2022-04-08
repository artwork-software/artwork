<template>
    <app-layout title="Dashboard">
        <div class="py-4">
            <div class="max-w-screen-xl my-12 flex flex-row ml-16 mr-40">
                <div class="flex flex-1 flex-wrap justify-between">
                    <div class="flex">
                        <div class="w-full flex my-auto">
                            <h2 class="text-2xl">Alle Nutzer*innen</h2>
                            <button @click="openAddUserModal" type="button"
                                    class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                                <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span class="font-nanum tracking-tight text-lg text-secondary ml-1 my-auto">Lade neue Nutzer*innen ein</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="inset-y-0 mr-3 pointer-events-none">
                            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                        </div>
                    </div>
                    <ul role="list" class="mt-6 w-full">
                        <li v-for="(user,index) in users.data" :key="user.email" class="py-6 flex justify-between">
                            <div class="flex">
                                <img class="h-14 w-14 rounded-full flex justify-start" :src="user.profile_photo_url"
                                     alt=""/>
                                <div class="ml-3 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <p class="text-lg mr-3 font-semibold subpixel-antialiased text-primary">
                                            {{ user.last_name }}, {{ user.first_name }} </p>
                                        <p class="ml-1 text-sm font-medium text-primary my-auto"> {{ user.business }},
                                            {{ user.position }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8">
                                    <div class="mt-3 -mr-3" v-for="department in user.departments">
                                        <img class="h-9 w-9 rounded-full ring-2 ring-white"
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
                                        <div class="flex">
                                        <MenuButton
                                            class="flex">
                                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                              aria-hidden="true"/>
                                        </MenuButton>
                                        <div v-if="$page.props.can.show_hints && index === 0" class="absolute flex w-40 ml-6">
                                            <div>
                                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                            </div>
                                            <div class="flex">
                                                <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite einen Nutzer</span>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems
                                            class="origin-top-right absolute right-0 mr-4 mt-2 w-56 shadow-lg bg-primary focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem v-slot="{ active }">
                                                    <a :href="getEditHref(user)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Nutzer*in bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }">
                                                    <a @click="openDeleteUserModal(user)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <TrashIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
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
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Nutzer*innen einladen
                    </div>
                    <XIcon @click="closeAddUserModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Du kannst mehrere Nutzer*innen mit den gleichen Nutzerrechten und Teamzugehörigkeiten auf einmal
                        einladen.
                    </div>
                    <div class="mt-4">
                        <div class="flex mt-8">
                            <div class="relative w-72 mr-4">
                                <input id="email" v-model="emailInput" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="email" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">E-Mail</label>
                            </div>
                            <jet-input-error :message="form.error" class="mt-2"/>
                            <button
                                :class="[emailInput === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', ' mt-2 ml-1 items-center text-sm px-2 py-2 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                @click="addEmailToInvitationArray" :disabled="!emailInput">
                                {{ form.user_emails.length >= 1 ? 'Weitere ' : '' }}E-Mail-Adresse hinzufügen
                            </button>
                        </div>
                        <span v-for="(email,index) in form.user_emails"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            {{ email }}
                    <button type="button" @click="deleteEmailFromInvitationArray(index)">
                    <span class="sr-only">Email aus Einladung entfernen</span>
                        <XCircleIcon
                            class="ml-1 mt-1 h-5 w-5 hover:text-error "/>
                    </button>
                    </span>
                        <span class="flex inline-flex mt-4 -mr-3" v-for="(team,index) in form.departments">
                            <!--TODO: :src="team.logo_url" -->
                                <img class="h-14 w-14 rounded-full flex justify-start ring-2 ring-white"
                                     src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                     alt=""/>
                        </span>
                        <Disclosure @focusout="close()" as="div" class="relative">
                            <div class="flex mt-4 mb-10">
                                <DisclosureButton
                                    class="mt-3 flex my-auto items-center font-bold rounded-full shadow-sm text-white bg-black">
                                    <PlusSmIcon v-if="form.departments.length === 0" class="h-5 w-5" aria-hidden="true"/>
                                    <ChevronDownIcon v-else class="h-5 w-5" aria-hidden="true"/>
                                </DisclosureButton>
                                <div v-if="$page.props.can.show_hints && form.departments.length === 0" class="flex mt-2">
                                    <SvgCollection svgName="arrowLeft" class="mt-2 ml-2"/>
                                    <span class="font-nanum tracking-tight text-lg text-secondary ml-1 my-auto">Teile die Nutzer*innen direkt deinen Teams zu</span>
                                </div>
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
                                        <span class="flex "
                                              :class="[team.checked ? 'text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-md subpixel-antialiased']">
                                            <!--TODO: :src="team.logo_url" -->
                                            <input :key="team.name" v-model="team.checked" type="checkbox"
                                                   @change="teamChecked(team,index)"
                                                   class="mr-3 ring-offset-0 focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-secondary"/>
                                            <img class="h-8 w-8 rounded-full flex justify-start"
                                                 src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
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
                            <h3 class="text-xl mt-6 mb-8 leading-6 font-bold font-lexend text-gray-900">Nutzerrechte
                                definieren</h3>

                            <div class="mb-8">
                                <Checkbox v-for="role in roleCheckboxes" class="justify-between" :item=role
                                          type="role"></Checkbox>
                            </div>

                        </div>
                        <div v-on:click="showUserPermissions = !showUserPermissions">
                            <h2 class="text-sm flex text-gray-400 font-semibold cursor-pointer mb-2">
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
                    <XIcon @click="closeDeleteUserModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Bist du sicher, dass du {{ userToDelete.last_name + "," }} {{ userToDelete.first_name }} aus dem
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
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary font-lexend text-2xl my-2">
                        Nutzer*innen eingeladen
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-success">
                        Die Nutzer*innen haben eine Einladungs-E-Mail erhalten.
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
import {ChevronDownIcon, ChevronUpIcon, PlusSmIcon, XCircleIcon,CheckIcon} from '@heroicons/vue/solid'
import {SearchIcon } from "@heroicons/vue/outline";
import JetButton from '@/Jetstream/Button.vue'
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {InformationCircleIcon, XIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import Checkbox from "@/Layouts/Components/Checkbox";
import SvgCollection from "@/Layouts/Components/SvgCollection";


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
        DisclosurePanel,
        SvgCollection,
        XCircleIcon,
        CheckIcon
    },
    props: ['users', 'departments'],
    data() {
        return {
            showUserPermissions: true,
            addingUser: false,
            deletingUser: false,
            showSuccessModal: false,
            userToDelete: {},
            emailInput: "",
            form: useForm({
                user_emails: [],
                permissions: [],
                departments: [],
            }),
        }
    },
    methods: {
        openSuccessModal() {
            this.showSuccessModal = true;
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
        },
        openDeleteUserModal(user) {
            this.userToDelete = user;
            this.deletingUser = true;
        },
        closeDeleteUserModal() {
            this.userToDelete = {};
            this.deletingUser = false;
        },
        deleteUser() {
            Inertia.delete(`/users/${this.userToDelete.id}`);
            this.closeDeleteUserModal()
        },
        openAddUserModal() {
            this.addingUser = true
        },
        teamChecked(team, index) {
            if (team.checked) {
                this.form.departments.push(team);
            } else {
                this.form.departments.splice(index, 1);
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
            userPermissionCheckboxes.forEach((item) => {
                if (item.checked) {
                    this.form.permissions.push(item.permissionName);
                }
            })
            console.log('EMAIL ARRAY:' + this.form.user_emails)
            console.log('PERMISSION CHECKED' + this.form.permissions)
            console.log('TEAM ARRAY' + this.form.departments)

            this.form.post(route('invitations.store'));
            this.closeAddUserModal();
            this.openSuccessModal();
        },
        closeAddUserModal() {
            this.addingUser = false;
            this.emailInput = "";
            this.form.user_emails = [];
            this.form.permissions = [];
            this.form.departments = [];
            this.departments.forEach((team) => {
                team.checked = false;
            })

        },
        deleteTeamFromDepartmentsArray(team, index) {
            team.checked = false;
            this.form.departments.splice(index, 1);
        },
        getEditHref(user) {
            return route('user.edit', {user: user.id});
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
