<template>
    <app-layout>
        <div class="py-4">
            <div class="max-w-screen-lg my-12 flex flex-row ml-12 mr-40">
                <div class="flex flex-1 flex-wrap justify-between">
                    <div class="flex">
                        <div class="w-full flex my-auto">
                            <h2 class="headline1">Alle Nutzer*innen</h2>
                            <AddButton @click="openAddUserModal" text="Nutzer einladen"
                                       mode="page" class="-mt-0.5"/>
                            <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span class="hind ml-1 my-auto">Lade neue Nutzer*innen ein</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                             class="cursor-pointer inset-y-0 mr-3">
                            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                        </div>
                        <div v-else class="flex items-center w-full w-64 mr-2">
                            <inputComponent v-model="user_query" placeholder="Suche nach Nutzer*innen" />
                            <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                        </div>
                    </div>
                    <ul role="list" class="mt-6 w-full">
                        <li v-if="user_query.length < 1" v-for="(user,index) in users" :key="user.email"
                            class="py-6 flex justify-between">
                            <div class="flex">
                                <img class="h-14 w-14 rounded-full object-cover flex-shrink-0 flex justify-start"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <div class="ml-3 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <Link :href="getEditHref(user)"
                                              class="mr-3 sDark">
                                            {{ user.last_name }}, {{ user.first_name }}
                                        </Link>
                                        <p class="ml-1 xxsDarkBold my-auto"> {{ user.business }},
                                            {{ user.position }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8 items-center">
                                    <div class="-mr-3" v-for="department in user.departments.slice(0,2)">
                                        <TeamIconCollection :data-tooltip-target="department.id"
                                                            class="h-10 w-10 rounded-full ring-2 ring-white"
                                                            :iconName="department.svg_name"/>
                                        <div :id="department.id" role="tooltip"
                                             class="inline-block absolute invisible z-10 py-2 px-3 bg-primary rounded-lg shadow-sm opacity-0 transition-opacity duration-300 xsWhiteBold tooltip">
                                            {{ department.name }}
                                            <div class="tooltip-arrow" data-popper-arrow></div>
                                        </div>
                                    </div>
                                    <div v-if="user.departments.length >= 3" class="my-auto">
                                        <Menu as="div" class="relative">
                                            <div>
                                                <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                    <ChevronDownIcon
                                                        class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems
                                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="department in user.departments"
                                                              v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <TeamIconCollection class="h-10 w-10 rounded-full"
                                                                                :iconName="department.svg_name"/>
                                                            <span class="ml-4">
                                                                {{ department.name }}
                                                            </span>
                                                        </Link>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                                <Menu as="div" class="my-auto relative">
                                    <div>
                                        <div class="flex">
                                            <MenuButton
                                                class="flex">
                                                <DotsVerticalIcon
                                                    class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                    aria-hidden="true"/>
                                            </MenuButton>
                                            <div v-if="$page.props.can.show_hints && index === 0"
                                                 class="absolute flex w-40 ml-6">
                                                <div>
                                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                                </div>
                                                <div class="flex">
                                                    <span
                                                        class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite einen Nutzer</span>
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
                                                        Profil bearbeiten
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
                        <li v-else v-for="(user,index) in user_search_results" :key="user.email"
                            class="py-6 flex justify-between">
                            <div class="flex">
                                <img class="h-14 w-14 rounded-full object-cover flex-shrink-0 flex justify-start"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <div class="ml-3 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <Link :href="getEditHref(user)"
                                              class="mr-3 sDark">
                                            {{ user.last_name }}, {{ user.first_name }}
                                        </Link>
                                        <p class="ml-1 xxsDarkBold my-auto"> {{ user.business }},
                                            {{ user.position }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8 items-center">
                                    <div class="-mr-3" v-for="department in user.departments.slice(0,2)">
                                        <TeamIconCollection :data-tooltip-target="department.id"
                                                            class="h-10 w-10 rounded-full ring-2 ring-white"
                                                            :iconName="department.svg_name"/>
                                        <div :id="department.id" role="tooltip"
                                             class="inline-block absolute invisible z-10 py-2 px-3 bg-primary rounded-lg shadow-sm opacity-0 transition-opacity duration-300 xsWhiteBold tooltip">
                                            {{ department.name }}
                                            <div class="tooltip-arrow" data-popper-arrow></div>
                                        </div>
                                    </div>
                                    <div v-if="user.departments.length >= 3" class="my-auto">
                                        <Menu as="div" class="relative">
                                            <div>
                                                <MenuButton class="flex items-center rounded-full focus:outline-none">
                                                    <ChevronDownIcon
                                                        class="ml-1 flex-shrink-0 h-9 w-9 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems
                                                    class="z-40 absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="department in user.departments"
                                                              v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <TeamIconCollection class="h-10 w-10 rounded-full"
                                                                                :iconName="department.svg_name"/>
                                                            <span class="ml-4">
                                                                {{ department.name }}
                                                            </span>
                                                        </Link>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                                <Menu as="div" class="my-auto relative">
                                    <div>
                                        <div class="flex">
                                            <MenuButton
                                                class="flex">
                                                <DotsVerticalIcon
                                                    class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                    aria-hidden="true"/>
                                            </MenuButton>
                                            <div v-if="$page.props.can.show_hints && index === 0"
                                                 class="absolute flex w-40 ml-6">
                                                <div>
                                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                                </div>
                                                <div class="flex">
                                                    <span
                                                        class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite einen Nutzer</span>
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
                                                        Profil bearbeiten
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
        <!-- Nutzer*in löschen Modal -->
        <jet-dialog-modal :show="deletingUser" @close="closeDeleteUserModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="headline1 my-2">
                        Nutzer*in löschen
                    </div>
                    <XIcon @click="closeDeleteUserModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="errorText">
                        Bist du sicher, dass du {{ userToDelete.last_name + "," }} {{ userToDelete.first_name }} aus dem
                        System löschen möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <AddButton text="Löschen" mode="modal" class="px-20 py-3"
                                @click="deleteUser" />
                        <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()"
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Success Modal -->
        <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_success.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="headline1 my-2">
                        Nutzer*innen eingeladen
                    </div>
                    <XIcon @click="closeSuccessModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="successText">
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

    <!-- Nutzer*innen einladen Modal -->
    <invite-users-modal
        :show="this.addingUser"
        :closeModal="closeAddUserModal"
        :all_permissions="all_permissions"
        :departments="departments"
    />
</template>

<script>


import {Inertia} from "@inertiajs/inertia";

const roleCheckboxes = [
    {name: 'Standarduser', roleName: "user", tooltipText: "Hier fehlt noch info text", showIcon: true, checked: false},
    {
        name: 'Adminrechte',
        roleName: "admin",
        tooltipText: "Administratoren haben im gesamten System Lese- und Schreibrechte - weitere Einstellungen entfallen",
        showIcon: true,
        checked: false
    },

]

import {defineComponent} from 'vue'
import {ref} from 'vue'
import AddButton from "@/Layouts/Components/AddButton";
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    RadioGroup,
    RadioGroupDescription,
    RadioGroupLabel,
    RadioGroupOption
} from "@headlessui/vue";
import AppLayout from '@/Layouts/AppLayout.vue'
import {DotsVerticalIcon, PencilAltIcon, TrashIcon} from '@heroicons/vue/outline'
import {ChevronDownIcon, ChevronUpIcon, PlusSmIcon, XCircleIcon, CheckIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";
import JetButton from '@/Jetstream/Button.vue'
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {InformationCircleIcon, XIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/inertia-vue3";
import Checkbox from "@/Layouts/Components/Checkbox";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link} from "@inertiajs/inertia-vue3";
import FlowbiteModal from "@/Flowbite/FlowbiteModal";
import InputComponent from "@/Layouts/Components/InputComponent";
import InviteUsersModal from "@/Layouts/Components/InviteUsersModal.vue";

export default defineComponent({
    components: {
        AddButton,
        FlowbiteModal,
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
        CheckIcon,
        TeamIconCollection,
        RadioGroup,
        RadioGroupDescription,
        RadioGroupLabel,
        RadioGroupOption,
        Link,
        InputComponent,
        InviteUsersModal
    },
    props: ['users', 'departments', 'all_permissions'],
    data() {
        return {
            showUserPermissions: true,
            addingUser: false,
            deletingUser: false,
            showSuccessModal: false,
            userToDelete: {},
            showSearchbar: false,
            user_query: "",
            user_search_results: [],
        }
    },
    methods: {
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.user_query = ''
        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
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
        closeAddUserModal(bool) {
            this.addingUser = false;
            this.emailInput = "";
            this.form.user_emails = [];
            this.form.permissions = [];
            this.form.departments = [];
            this.form.role = '';
            this.departments.forEach((team) => {
                team.checked = false;
            })
            if(bool){
                this.openSuccessModal();
            }
        },
        getEditHref(user) {
            return route('user.edit', {user: user.id});
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
        }
    },
    setup() {

        return {
            roleCheckboxes
        }
    }
})
</script>
