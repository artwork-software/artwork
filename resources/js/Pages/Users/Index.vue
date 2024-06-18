<template>
    <UserHeader>
        <div class="">
            <div class="max-w-screen-xl my-12 flex flex-row">
                <div class="flex flex-1 flex-wrap justify-between">
                    <div class="flex">
                        <div class="w-full flex my-auto items-center">
                            <Listbox as="div" class="flex" v-model="selectedFilter">
                                <ListboxButton
                                    class="bg-white w-full relative cursor-pointer focus:outline-none">
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
                                        class="absolute w-80 z-10 mt-12 bg-artwork-navigation-background shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="filter in displayFilters"
                                                       :key="filter.name"
                                                       :value="filter"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'xsLight', 'block truncate']">
                                                        {{ filter.name }}
                                                    </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <div class="flex" v-if="this.$can('can manage workers') || this.hasAdminRole()">
                                <button @click="openSelectAddUsersModal = true" type="button"
                                        class="rounded-full bg-artwork-buttons-create p-1 mr-1 text-white shadow-sm hover:bg-artwork-buttons-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create">
                                    <PlusIcon class="h-4 w-4" aria-hidden="true"/>
                                </button>
                                <div v-if="this.$page.props.show_hints" class="flex mt-1">
                                    <div  class="mt-1 ml-2">
                                        <SvgCollection svgName="arrowLeft"/>
                                    </div>
                                    <span class="hind ml-1 my-auto">{{ $t('Invite new users')}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                             class="cursor-pointer inset-y-0 mr-3">
                            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                        </div>
                        <div v-else class="flex items-center w-64 mr-2">
                            <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                   placeholder="Suche nach User*innen"
                                   class="h-10 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                        </div>
                    </div>
                    <ul role="list" class="mt-6 w-full">
                        <li v-if="user_search_results.length < 1" v-for="(user,index) in userObjectsToShow"
                            :key="user.email" class="py-6 flex justify-between">
                            <div class="flex">
                                <img class="h-14 w-14 rounded-full object-cover flex-shrink-0 flex justify-start"
                                     :src="user.profile_photo_url ?? user.profile_image"
                                     alt=""/>
                                <div class="ml-3 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <Link :href="checkLink(user) "
                                              class="mr-3 sDark">
                                            {{ user.display_name ?? user.provider_name }}
                                            <span v-if="user.position || user.business">, </span>
                                        </Link>
                                        <p class="ml-1 xxsDarkBold my-auto">
                                            <span v-if="user.business">{{ user.business }}, </span>
                                            <span v-if="user.position">{{ user.position }}</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="flex mr-8 items-center" v-if="selectedFilter.type === 'users'">
                                    <div class="-mr-3" v-for="department in user.departments?.slice(0,2)">
                                        <TeamIconCollection :data-tooltip-target="department.id"
                                                            class="h-10 w-10 rounded-full ring-2 ring-white"
                                                            :iconName="department.svg_name"/>
                                        <div :id="department.id" role="tooltip"
                                             class="inline-block absolute invisible py-2 px-3 bg-artwork-navigation-background rounded-lg shadow-sm opacity-0 transition-opacity duration-300 xsWhiteBold tooltip">
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
                                                    class="absolute overflow-y-auto rounded-lg max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="department in user.departments"
                                                              v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-artwork-navigation-color/10 text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
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
                                <BaseMenu  v-if="hasAdminRole()">
                                    <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                        <a :href="checkLink(user)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Edit Profile')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                        <a @click="openDeleteUserModal(user)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            <span v-if="user.type === 'user'">
                                                            {{ $t('Delete user')}}
                                                        </span>
                                            <span v-else-if="user.type === 'freelancer'">
                                                            {{ $t('Delete freelancer')}}
                                                        </span>
                                            <span v-else-if="user.type === 'service_provider'">
                                                            {{ $t('Delete service provider')}}
                                                        </span>
                                        </a>
                                    </MenuItem>
                                </BaseMenu>
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
                                             class="inline-block absolute invisible py-2 px-3 bg-artwork-navigation-background rounded-lg shadow-sm opacity-0 transition-opacity duration-300 xsWhiteBold tooltip">
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
                                                    class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right rounded-lg shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="department in user.departments"
                                                              v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-artwork-navigation-color/10 text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
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
                                <BaseMenu v-if="hasAdminRole()">
                                    <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                        <a :href="getEditHref(user)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Edit Profile')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                        <a @click="openDeleteUserModal(user)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Delete user')}}
                                        </a>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Nutzer*in löschen Modal -->
        <BaseModal @closed="closeDeleteUserModal" v-if="deletingUser" modal-image="/Svgs/Overlays/illu_warning.svg">
                <div class="mx-4">
                    <div class="headline1 my-2">
                        <span v-if="userToDelete.type === 'user'">
                            {{ $t('Delete user')}}
                        </span>
                        <span v-else-if="userToDelete.type === 'freelancer'">
                            {{ $t('Delete freelancer')}}
                        </span>
                        <span v-else-if="userToDelete.type === 'service_provider'">
                            {{ $t('Delete service provider')}}
                        </span>
                    </div>
                    <div class="errorText">
                        <span v-if="userToDelete.type === 'user' || userToDelete.type === 'freelancer'">
                            {{ $t('Are you sure you want to delete {last_name}, {first_name} from the system?', { last_name: userToDelete.last_name, first_name: userToDelete.first_name}) }}
                        </span>
                        <span v-else-if="userToDelete.type === 'service_provider'">
                            {{ $t('Are you sure you want to delete { serviceProvider } from the system?', { serviceProvider: userToDelete.provider_name }) }}
                        </span>
                    </div>
                    <div class="flex justify-between mt-6">
                        <FormButton :text="$t('Delete')" @click="deleteUser" />
                        <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
        <!-- Success Modal -->
        <SuccessModal
            :open="showSuccessModal"
            @closed="closeSuccessModal"
            :title="$t('Users invited')"
            :description="$t('The users have received an invitation email.')"
            button="Okay"
            />
    </UserHeader>

    <!-- Nutzer*innen einladen Modal -->
    <invite-users-modal
        :show="this.addingUser"
        :closeModal="closeAddUserModal"
        :all_permissions="all_permissions"
        :departments="departments"
        :roles="roles"
        :permission_presets="permission_presets"
        :users="users"
        :invited-users="invitedUsers"
    />

    <AddUsersModal v-if="openSelectAddUsersModal" @closeModal="openSelectAddUsersModal = false"
                   @openUserModal="addingUser = true"/>
</template>

<script>


import {router} from "@inertiajs/vue3";


import {defineComponent} from 'vue'
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
import Checkbox from "@/Layouts/Components/Checkbox.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Link} from "@inertiajs/vue3";
import FlowbiteModal from "@/Flowbite/FlowbiteModal.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import InviteUsersModal from "@/Layouts/Components/InviteUsersModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserHeader from "@/Pages/Users/UserHeader.vue";
import AddUsersModal from "@/Pages/Users/Components/AddUsersModal.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        BaseModal,
        BaseMenu,
        FormButton,
        SuccessModal,
        AddUsersModal,
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
        InviteUsersModal,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        PlusIcon
    },
    props: [
        'users',
        'departments',
        'all_permissions',
        'roles',
        'freelancers',
        'serviceProviders',
        'permission_presets',
        'invitedUsers',
    ],
    data() {
        return {
            showUserPermissions: true,
            addingUser: false,
            deletingUser: false,
            showSuccessModal: false,
            userToDelete: null,
            showSearchbar: false,
            user_query: "",
            user_search_results: [],
            displayFilters: [
                {
                    'name': this.$t('All users'),
                    'type': 'users'
                },
                {
                    'name': this.$t('All freelancers'),
                    'type': 'freelancer'
                },
                {
                    'name': this.$t('All service providers'),
                    'type': 'service_provider'
                },
                {
                    'name': this.$t('All available users'),
                    'type': 'all'
                }
            ],
            selectedFilter: {'name': this.$t('All users'), 'type': 'users'},
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
        checkLink(user) {
            if (user.type === 'freelancer') {
                return route('freelancer.show', {freelancer: user.id});
            }
            if (user.type === 'service_provider') {
                return route('service_provider.show', {serviceProvider: user.id});
            }
            if (user.user === 'user') {
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
            this.userToDelete = null;
            this.deletingUser = false;
        },
        deleteUser() {
            let desiredRoute = null;

            switch (this.userToDelete.type) {
                case 'user':
                    desiredRoute = route('user.destroy', {user: this.userToDelete.id});
                    break;
                case 'freelancer':
                    desiredRoute = route('freelancer.destroy', {freelancer: this.userToDelete.id});
                    break;
                case 'service_provider':
                    desiredRoute = route('service_provider.destroy', {serviceProvider: this.userToDelete.id});
                    break;
            }

            if (desiredRoute) {
                router.delete(
                    desiredRoute,
                    {
                        onSuccess: () => this.closeDeleteUserModal()
                    }
                );
            }
        },
        closeAddUserModal(bool) {
            this.addingUser = false;
            if (bool) {
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
                        this.user_search_results.forEach((user) => {
                            user.name = user.first_name + ' ' + user.last_name;
                        })
                    })
                } else {
                    this.user_search_results = [];
                }
            },
            deep: true
        }
    },
})
</script>
