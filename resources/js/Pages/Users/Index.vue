<template>
    <UserHeader>
        <div class="">
            <div class="max-w-screen-xl my-12 flex flex-row">
                <div class="flex flex-1 flex-wrap justify-between">
                    <div class="flex">
                        <div class="w-full flex my-auto items-center">
                            <Listbox as="div" class="flex" v-model="selectedFilter">
                                <ListboxButton
                                    class="bg-white w-full relative py-2 cursor-pointer focus:outline-none">
                                    <div class="flex items-center my-auto">
                                        <h2 class="headline1">
                                            {{ selectedFilter.name }}</h2>
                                        <span
                                            class="inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5" aria-hidden="true"/>
                                         </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-80 z-10 mt-12 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="filter in displayFilters"
                                                       :key="filter.name"
                                                       :value="filter"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'xsLight', 'block truncate']">
                                                        {{ filter.name }}
                                                    </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <button @click="openSelectAddUsersModal = true" type="button" class="rounded-full bg-gray-600 p-1 mr-1 text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">
                                <PlusIcon class="h-4 w-4" aria-hidden="true" />
                            </button>
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
                        <li v-if="user_query.length < 1" v-for="(user,index) in userObjectsToShow" :key="user.email" class="py-6 flex justify-between">
                            <div class="flex">
                                <img class="h-14 w-14 rounded-full object-cover flex-shrink-0 flex justify-start"
                                     :src="user.profile_photo_url ?? user.profile_image"
                                     alt=""/>
                                <div class="ml-3 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <Link :href="checkLink(user) " v-if="$role('artwork admin')"
                                              class="mr-3 sDark">
                                            {{ user.display_name ?? user.provider_name }}
                                            <span v-if="user.position || user.business">, </span>
                                        </Link>
                                        <p class="ml-1 xxsDarkBold my-auto" >
                                            <span v-if="user.business">{{ user.business }}, </span>
                                            <span v-if="user.position">{{ user.position }}</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8 items-center"  v-if="selectedFilter.type === 'users'">
                                    <div class="-mr-3" v-for="department in user.departments?.slice(0,2)">
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
                                                class="flex bg-tagBg p-0.5 rounded-full">
                                                <DotsVerticalIcon
                                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                                    aria-hidden="true"/>
                                            </MenuButton>
                                            <div v-if="$page.props.can.show_hints && index === 0"
                                                 class="absolute flex w-40 ml-9">
                                                <div>
                                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                                </div>
                                                <div class="flex">
                                                    <span
                                                        class="hind ml-1">Bearbeite einen Nutzer</span>
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
                                                <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                                    <a :href="checkLink(user)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Profil bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }" v-if="hasAdminRole() && user.type === 'user'">
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
                                        <Link :href="getEditHref(user)" v-if="hasAdminRole()"
                                              class="mr-3 sDark">
                                            {{ user.name }}
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
                                                class="flex bg-tagBg p-0.5 rounded-full">
                                                <DotsVerticalIcon
                                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                                    aria-hidden="true"/>
                                            </MenuButton>
                                            <div v-if="$page.props.can.show_hints && index === 0"
                                                 class="absolute flex w-40 ml-6">
                                                <div>
                                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                                </div>
                                                <div class="flex">
                                                    <span
                                                        class="hind ml-2 text-secondary tracking-tight text-lg">Bearbeite einen Nutzer</span>
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
                                                <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                                    <a :href="getEditHref(user)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Profil bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
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
    </UserHeader>

    <!-- Nutzer*innen einladen Modal -->
    <invite-users-modal
        :show="this.addingUser"
        :closeModal="closeAddUserModal"
        :all_permissions="all_permissions"
        :departments="departments"
        :roles="roles"
    />

    <AddUsersModal v-if="openSelectAddUsersModal" @closeModal="openSelectAddUsersModal = false" @openUserModal="addingUser = true" />
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
    RadioGroupOption,
    Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
} from "@headlessui/vue";
import {DotsVerticalIcon, PencilAltIcon, TrashIcon} from '@heroicons/vue/outline'
import {ChevronDownIcon, ChevronUpIcon, PlusSmIcon, XCircleIcon, CheckIcon, PlusIcon} from '@heroicons/vue/solid'
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
import Permissions from "@/mixins/Permissions.vue";
import UserHeader from "@/Pages/Users/UserHeader.vue";
import AddUsersModal from "@/Pages/Users/Components/AddUsersModal.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        AddUsersModal,
        AddButton,
        FlowbiteModal,
        UserHeader,
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
        InviteUsersModal, Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions, PlusIcon
    },
    props: ['users', 'departments', 'all_permissions', 'roles', 'freelancers', 'serviceProviders'],
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
            displayFilters: [{
                'name': 'Alle Nutzer*innen',
                'type': 'users'
            }, {'name': 'Alle Freelancer*innen', 'type': 'freelancer'}, {
                'name': 'Alle Dienstleister*innen',
                'type': 'service_provider'
            }, {'name': 'Alle verfügbaren Nutzer*innen', 'type': 'all'}],
            selectedFilter: {'name': 'Alle Nutzer*innen', 'type': 'users'},
            openSelectAddUsersModal: false
        }
    },
    computed: {
        userObjectsToShow: function () {
            if (this.selectedFilter.type === 'users') {
                return this.users
            } else if (this.selectedFilter.type === 'freelancer') {
                return this.freelancers;
            } else if (this.selectedFilter.type === 'service_provider') {
                return this.serviceProviders;
            } else if (this.selectedFilter.type === 'all') {
                return this.users.concat(this.freelancers, this.serviceProviders)
            }
        },
    },
    methods: {
        checkLink(user){
            if(user.type === 'freelancer'){
                return route('freelancer.show', {freelancer: user.id});
            }
            if( user.type === 'service_provider'){
                return route('service_provider.show', {serviceProvider: user.id});
            }
            if(user.user === 'user'){
                return route('user.edit.shiftplan', {user: user.id});
            }

            return route('user.edit.shiftplan', {user: user.id});
        },

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
            if(bool){
                this.openSuccessModal();
            }
        },
        getEditHref(user) {
            return route('user.edit.shiftplan', {user: user.id});
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
