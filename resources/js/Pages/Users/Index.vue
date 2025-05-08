<template>
    <UserHeader title="Users" description="Invite new users or edit an existing user">
        <template #tabBar>
            <div class="flex items-center gap-x-4">
                <div class="flex items-center">
                    <div v-if="!showSearchbar" @click="openSearchbar" class="cursor-pointer inset-y-0">
                        <SearchIcon class="size-7 !text-artwork-buttons-context" aria-hidden="true" stroke-width="1.5"/>
                    </div>
                    <div v-else class="flex items-center w-64 mr-2">
                        <input ref="searchBarInput" id="userSearch" v-model="user_query" type="text" autocomplete="off"
                               placeholder="Suche nach User*innen"
                               class="h-10 sDark inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                    </div>
                </div>
                <BaseMenu show-sort-icon dots-size="h-7 w-7" has-no-offset dots-color="!text-gray-900" menu-width="w-72">
                    <div class="flex items-center justify-end py-1">
                        <span class="pr-4 pt-0.5 xxsLight cursor-pointer text-right w-full"
                              @click="this.resetSort()">
                            {{ $t('Reset') }}
                        </span>
                    </div>
                    <MenuItem v-for="userSortEnumName in userSortEnumNames" v-slot="{ active }">
                        <div @click="this.sortBy = userSortEnumName; this.applyFiltersAndSort()" :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased']">
                            {{ getSortEnumTranslation(userSortEnumName) }}
                            <IconCheck v-if="this.getUserSortBySetting() === userSortEnumName" class="w-5 h-5"/>
                        </div>
                    </MenuItem>
                </BaseMenu>
                <div class="w-full">
                    <BaseCardButton text="Invite new users" class="w-max" @click="addingUser = true" />
                </div>
            </div>
        </template>
        <template #default>
            <div class="">
                <div class="flex flex-row w-full">
                    <div class="flex flex-1 flex-wrap justify-end w-full">

                        <ul role="list" class="mt-6 w-full">
                            <li v-if="user_search_results.length < 1" v-for="(user,index) in users"
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
                                    <div class="flex mr-8 items-center">
                                        <div class="-mr-3" v-for="department in user.departments?.slice(0,2)">
                                            <TeamIconCollection :data-tooltip-target="department.id"
                                                                class="h-10 w-10 min-w-10 min-h-10 rounded-full ring-2 ring-white"
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
                                                            class="ml-1 flex-shrink-0 min-w-10 min-h-10 h-10 w-10 flex my-auto items-center ring-2 ring-white font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                                    </MenuButton>
                                                </div>
                                                <transition enter-active-class="transition-enter-active"
                                                            enter-from-class="transition-enter-from"
                                                            enter-to-class="transition-enter-to"
                                                            leave-active-class="transition-leave-active"
                                                            leave-from-class="transition-leave-from"
                                                            leave-to-class="transition-leave-to">
                                                    <MenuItems
                                                        class="absolute overflow-y-auto rounded-lg z-30 max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
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
                                    <BaseMenu v-if="hasAdminRole()" has-no-offset>
                                        <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                            <a :href="checkLink(user)"
                                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <PencilAltIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                {{ $t('Edit Profile') }}
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }" v-if="hasAdminRole()">
                                            <a @click="openDeleteUserModal(user)"
                                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                <span v-if="user.type === 'user'">
                                                            {{ $t('Delete user') }}
                                                        </span>
                                                <span v-else-if="user.type === 'freelancer'">
                                                            {{ $t('Delete freelancer') }}
                                                        </span>
                                                <span v-else-if="user.type === 'service_provider'">
                                                            {{ $t('Delete service provider') }}
                                                        </span>
                                            </a>
                                        </MenuItem>
                                    </BaseMenu>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>

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

    <!-- Nutzer*in löschen Modal -->
    <BaseModal @closed="closeDeleteUserModal" v-if="deletingUser" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="headline1 my-2">
                        <span v-if="userToDelete.type === 'user'">
                            {{ $t('Delete user') }}
                        </span>
                <span v-else-if="userToDelete.type === 'freelancer'">
                            {{ $t('Delete freelancer') }}
                        </span>
                <span v-else-if="userToDelete.type === 'service_provider'">
                            {{ $t('Delete service provider') }}
                        </span>
            </div>
            <div class="errorText">
                        <span v-if="userToDelete.type === 'user' || userToDelete.type === 'freelancer'">
                            {{
                                $t('Are you sure you want to delete {last_name}, {first_name} from the system?', {
                                    last_name: userToDelete.last_name,
                                    first_name: userToDelete.first_name
                                })
                            }}
                        </span>
                <span v-else-if="userToDelete.type === 'service_provider'">
                            {{
                        $t('Are you sure you want to delete { serviceProvider } from the system?', {serviceProvider: userToDelete.provider_name})
                    }}
                        </span>
            </div>
            <div class="flex justify-between mt-6">
                <FormButton :text="$t('Delete')" @click="deleteUser"/>
                <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()"
                                  class="xsLight cursor-pointer">{{ $t('No, not really') }}</span>
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

</template>

<script>
import {Link, router} from "@inertiajs/vue3";
import {defineComponent} from 'vue'
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    RadioGroup,
    RadioGroupDescription,
    RadioGroupLabel,
    RadioGroupOption
} from "@headlessui/vue";
import {
    DotsVerticalIcon,
    InformationCircleIcon,
    PencilAltIcon,
    SearchIcon,
    TrashIcon,
    XIcon
} from '@heroicons/vue/outline'
import {CheckIcon, ChevronDownIcon, ChevronUpIcon, PlusIcon, PlusSmIcon, XCircleIcon} from '@heroicons/vue/solid'
import JetButton from '@/Jetstream/Button.vue'
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import Checkbox from "@/Layouts/Components/Checkbox.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
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
import {IconCheck} from "@tabler/icons-vue";
import debounce from "lodash.debounce";
import {useSortEnumTranslation} from "@/Composeables/SortEnumTranslation.js";
import BaseCardButton from "@/Artwork/Buttons/BaseCardButton.vue";

const {getSortEnumTranslation} = useSortEnumTranslation();

export default defineComponent({
    mixins: [Permissions],
    components: {
        BaseCardButton,
        IconCheck,
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
        'userSortEnumNames',
        'userUserManagementSetting'
    ],
    data() {
        return {
            showUserPermissions: true,
            addingUser: false,
            deletingUser: false,
            showSuccessModal: false,
            userToDelete: null,
            showSearchbar: route().params.query?.length > 0,
            user_query: route().params.query?.length > 0 ? route().params.query : '',
            user_search_results: [],
            openSelectAddUsersModal: false,
            sortBy: this.userUserManagementSetting?.sort_by === null ? undefined : this.userUserManagementSetting?.sort_by,
        }
    },
    methods: {
        getSortEnumTranslation,
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
        },
        applyFiltersAndSort() {
            router.get(
                route().current(),
                {
                    query: this.user_query,
                    sort: this.sortBy,
                    saveFilterAndSort: 1
                }, {
                    preserveState: true
                }
            );
        },
        getUserSortBySetting() {
            return this.getUserUserManagementSetting()?.sort_by;
        },
        getUserUserManagementSetting() {
            return this.userUserManagementSetting;
        },
        resetSort() {
            this.sortBy = undefined;
            this.applyFiltersAndSort();
        },
        reloadUsersDebounced: debounce(function () {
            this.applyFiltersAndSort();
        }, 1000),
        openSearchbar(){
            this.showSearchbar = !this.showSearchbar;
            this.$nextTick(() => {
                if (this.showSearchbar) {
                    this.$refs.searchBarInput.focus();
                }
            });
        },
    },
    watch: {
        user_query: {
            handler() {
                this.reloadUsersDebounced();
            }
        }
    },
})
</script>
