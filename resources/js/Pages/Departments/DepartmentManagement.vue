<template>
    <UserHeader :title="$t('All Departments')">
        <div class="">
            <div class="max-w-screen-xl mb-40 my-12 flex flex-row">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto gap-x-4 justify-between">
                        <div class="flex w-full justify-between items-center">
                            <h2 class="headline1 flex">{{ $t('All Departments')}}</h2>
                            <AddButtonBig class="mt-0" @click="openAddTeamModal" :text="$t('Create Team')"/>
                            <div v-if="this.$page.props.show_hints" class="flex mt-1">
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                <span
                                    class="ml-1 my-auto hind">{{  $t('Create new teams') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                 class="cursor-pointer inset-y-0 mr-3">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                            <div v-else class="flex items-center w-full w-64 mr-2">
                                <input v-model="department_query" :placeholder="$t('Search for teams')" type="text" />
                                <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                            </div>
                        </div>
                    </div>
                    <ul role="list" class="mt-5 w-full">
                        <li v-if="department_query.length < 1" v-for="(department,index) in departments"
                            :key="department.id"
                            class="py-5 flex justify-between items-center">
                            <div class="flex">
                                <TeamIconCollection class="h-16 w-16 flex-shrink-0" :iconName=department.svg_name
                                                    alt="TeamIcon"/>
                                <Link :href="getEditHref(department)" class="ml-5 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <p class="sDark">{{ department.name }}</p>
                                    </div>
                                </Link>
                            </div>
                            <div class="flex items-center">
                                <div class="flex items-center mr-8">
                                    <div class="my-auto flex items-center -mr-3" v-for="user in department.users.slice(0,9)">
                                        <UserPopoverTooltip :id="user.id" :user="user" :height="9" :width="9"/>
                                    </div>
                                    <div v-if="department.users.length >= 9" class="my-auto">
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
                                                    class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 origin-top-right shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="user in department.users" v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-artwork-navigation-color/10 text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <img class="h-9 w-9 rounded-full object-cover"
                                                                 :src="user.profile_photo_url"
                                                                 alt=""/>
                                                            <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                                        </Link>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                                <BaseMenu class="-mt-1.5">
                                    <MenuItem v-slot="{ active }">
                                        <a :href="getEditHref(department)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Edit team')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="openDeleteAllTeamMembersModal(department)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Remove all team members')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="openDeleteTeamModal(department)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Delete Team') }}
                                        </a>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </li>
                        <li v-else v-for="(department,index) in department_search_results" :key="department.id"
                            class="py-5 flex justify-between">
                            <div class="flex">
                                <TeamIconCollection class="h-16 w-16 flex-shrink-0" :iconName=department.svg_name
                                                    alt="TeamIcon"/>
                                <Link :href="getEditHref(department)" class="ml-5 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <p class="sDark">{{ department.name }}</p>
                                    </div>
                                </Link>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8">
                                    <div class="my-auto -mr-3" v-for="user in department.users.slice(0,9)">
                                        <UserPopoverTooltip :id="user.id" :user="user" :height="9" :width="9"/>
                                    </div>
                                    <div v-if="department.users.length >= 9" class="my-auto">
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
                                                    class="absolute overflow-y-auto max-h-48 mt-2 w-72 mr-12 rounded-lg origin-top-right shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                    <MenuItem v-for="user in department.users" v-slot="{ active }">
                                                        <Link href="#"
                                                              :class="[active ? 'bg-artwork-navigation-color/10 text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <img class="h-9 w-9 rounded-full object-cover"
                                                                 :src="user.profile_photo_url"
                                                                 alt=""/>
                                                            <span class="ml-4">
                                                                {{ user.first_name }} {{ user.last_name }}
                                                            </span>
                                                        </Link>
                                                    </MenuItem>
                                                </MenuItems>
                                            </transition>
                                        </Menu>
                                    </div>
                                </div>
                                <BaseMenu>
                                    <MenuItem v-slot="{ active }">
                                        <a :href="getEditHref(department)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Edit team')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="openDeleteAllTeamMembersModal(department)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Remove all team members')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="openDeleteTeamModal(department)"
                                           :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Delete Team') }}
                                        </a>
                                    </MenuItem>
                                </BaseMenu>
                            </div>
                        </li>
                    </ul>
                </div>


            </div>
        </div>
        <!-- Team erstellen Modal-->
        <BaseModal @closed="closeAddTeamModal" v-if="addingTeam" modal-image="/Svgs/Overlays/illu_team_new.svg" >
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{ $t('Create New Team')}}
                    </div>
                    <div class="xsLight subpixel-antialiased mt-4">
                        {{ $t('Create a fixed team/department.')}}
                    </div>
                    <div class="mt-12">
                        <div class="flex">
                            <Menu as="div" class=" relative">
                                <div>
                                    <MenuButton :class="[form.svg_name === '' ? 'border border-gray-400' : '']"
                                                class="items-center rounded-full focus:outline-none h-12 w-12">
                                        <label v-if="form.svg_name === ''" class="text-gray-400 text-xs">Icon*</label>
                                        <ChevronDownIcon v-if="form.svg_name === ''"
                                                         class="h-4 w-4 mx-auto items-center rounded-full shadow-sm text-black"></ChevronDownIcon>
                                        <TeamIconCollection class="h-12 w-12" v-if="form.svg_name !== ''"
                                                            :iconName=form.svg_name alt="TeamIcon"/>
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 origin-top-right absolute rounded-lg h-56 w-24 overflow-y-auto mt-2 shadow-lg py-1 bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <MenuItem v-for="item in iconMenuItems" v-slot="{ active }">
                                            <div @click="form.svg_name = item.iconName"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-secondaryHover' : 'text-secondary',
                                                  'group px-3 py-2 text-sm subpixel-antialiased']">
                                                <TeamIconCollection class="h-14 w-14" :iconName=item.iconName
                                                                    alt="TeamIcon"/>
                                            </div>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="name" v-model="form.name" type="text"
                                       class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                       placeholder="placeholder"/>
                                <label for="name"
                                       class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">
                                    {{ $t('Name of the team*')}}</label>
                            </div>
                        </div>
                        <span class="text-red-500 text-xs mt-2" v-if="form.svg_name === ''">
                            Icon auswählen notwendig*
                        </span>
                        <div class="mt-12">
                            <div class="headline2 my-2">
                                {{ $t('Add users')}}
                            </div>
                            <div class="xsLight subpixel-antialiased">
                                {{ $t('Enter the name of the user you want to add to the team.')}}
                            </div>

                            <div class="mt-6 relative">
                                <div class="my-auto w-full">
                                    <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                           class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                           placeholder="placeholder"/>
                                    <label for="userSearch"
                                           class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                                </div>

                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                         class="absolute z-10 mt-1 w-full max-h-60 bg-artwork-navigation-background shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                        <div class="border-gray-200">
                                            <div v-for="(user, index) in user_search_results" :key="index"
                                                 class="flex items-center cursor-pointer">
                                                <div class="flex-1 text-sm py-4">
                                                    <p @click="addUserToAssignedUsersArray(user)"
                                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                        {{ user.first_name }} {{ user.last_name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                        </div>

                        <div class="mt-4">
                            <span v-for="(user,index) in form.assigned_users"
                                  class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full object-cover"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <span class="flex ml-4">
                                    {{ user.first_name }} {{ user.last_name }}
                                </span>
                            </div>
                            <button type="button" @click="deleteUserFromTeam(index)">
                                <span class="sr-only">User aus Team entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error"/>
                            </button>
                            </span>
                        </div>
                        <div class="w-full items-center text-center">
                            <FormButton
                                @click="addTeam"
                                :disabled="this.form.name === '' || this.form.svg_name === ''"
                                :text="$t('Create Team')"/>
                        </div>
                    </div>

                </div>
        </BaseModal>
        <!-- Alle Mitglieder aus Team löschen Modal -->
        <BaseModal @closed="closeDeleteAllTeamMembersModal" v-if="deletingAllTeamMembers" modal-image="/Svgs/Overlays/illu_warning.svg" >
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('Delete all team members')}}
                    </div>
                    <div class="errorText mt-4">
                        {{ $t('Are you sure you want to remove all members of the team { teamName }?', { teamName: teamToDeleteAllMembers.name })}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-artwork-navigation-background focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteAllTeamMembers">
                            {{ $t('Delete')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteAllTeamMembersModal"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
        <!-- Team löschen Modal -->
        <BaseModal @closed="closeDeleteTeamModal" v-if="deletingTeam" modal-image="/Svgs/Overlays/illu_warning.svg" >
                <div class="mx-4 bg-secondaryHover">
                    <div class="headline1 mt-6 my-2">
                        {{ $t('Delete Team')}}
                    </div>
                    <div class="errorText">
                        {{ $t('Are you sure you want to delete the team { teamName } from the system?', { teamName: teamToDelete.name })}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-artwork-navigation-background focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteTeam">
                            {{ $t('Delete all team members')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteTeamModal"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
        <!-- Success Modal -->
        <SuccessModal
            :show="showSuccess"
            @closed="closeSuccessModal"
            :title="this.successHeading"
            :description="$t('The changes have been saved successfully.')"
            :button="$t('Close')"
        />
    </UserHeader>
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DotsVerticalIcon,
    ChevronDownIcon,
    InformationCircleIcon,
    XIcon,
    PencilAltIcon,
    TrashIcon
} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, XCircleIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";

import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems
} from '@headlessui/vue'
import Button from "@/Jetstream/Button.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import JetSecondaryButton from "@/Jetstream/SecondaryButton.vue";
import Checkbox from "@/Layouts/Components/Checkbox.vue";
import {useForm} from "@inertiajs/vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Link} from "@inertiajs/vue3";
import {router} from "@inertiajs/vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserHeader from "@/Pages/Users/UserHeader.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import AddButtonBig from "@/Layouts/Components/General/Buttons/AddButtonBig.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

const iconMenuItems = [
    {iconName: 'icon_ausstellung'},
    {iconName: 'icon_ausstellung_foto'},
    {iconName: 'icon_bildung_bibliothek'},
    {iconName: 'icon_bildung_kulturell'},
    {iconName: 'icon_dienst_abend'},
    {iconName: 'icon_dienst_kasse'},
    {iconName: 'icon_dienst_reinigung'},
    {iconName: 'icon_dienst_sicherheit'},
    {iconName: 'icon_dramaturgie'},
    {iconName: 'icon_dramaturgie_kurator'},
    {iconName: 'icon_dramaturgie_tanz'},
    {iconName: 'icon_einhorn'},
    {iconName: 'icon_festival'},
    {iconName: 'icon_kommunikation_marketing'},
    {iconName: 'icon_kommunikation_vertrieb'},
    {iconName: 'icon_orga_finanzen'},
    {iconName: 'icon_orga_kuenstlerischesbuero'},
    {iconName: 'icon_orga_leitung'},
    {iconName: 'icon_orga_personal'},
    {iconName: 'icon_orga_sekretariat'},
    {iconName: 'icon_orga_verwaltung'},
    {iconName: 'icon_technik'},
    {iconName: 'icon_technik_audiovideo'},
    {iconName: 'icon_technik_buehne'},
    {iconName: 'icon_technik_haus'},
    {iconName: 'icon_technik_licht'},
    {iconName: 'icon_technik_veranstaltung'},
    {iconName: 'icon_vermietung'},
]

export default defineComponent({
    mixins: [Permissions],
    components: {
        BaseModal,
        BaseMenu,
        FormButton,
        AddButtonBig,
        SuccessModal,
        UserPopoverTooltip,
        UserHeader,
        TeamIconCollection,
        UserTooltip,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        CheckIcon,
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
        XCircleIcon,
        Link,
        InputComponent
    },
    props: ['departments', 'users'],
    created() {
        Echo.private('departments')
            .listen('DepartmentUpdated', () => {
                router.reload({only: ['departments']})
            });
    },
    methods: {
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.department_query = '';
        },
        openAddTeamModal() {
            this.addingTeam = true;
        },
        closeAddTeamModal() {
            this.addingTeam = false;
            this.form.assigned_users = [];
            this.form.name = "";
            this.form.svg_name = "";
        },
        showSuccessModal(type) {
            if (type === 'add') {
                this.successHeading = this.$t('Team successfully created')
            } else if (type === 'delete') {
                this.successHeading = this.$t('Team successfully deleted')
            } else {
                this.successHeading = this.$t('Team successfully processed')
            }
            this.showSuccess = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccess = false;
        },
        addUserToAssignedUsersArray(user) {
            for (let assigned_user of this.form.assigned_users) {
                if (user.id === assigned_user.id) {
                    this.user_query = ""
                    return;
                }
            }

            this.form.assigned_users.push(user);
            this.user_query = ""
        },
        deleteUserFromTeam(index) {
            this.form.assigned_users.splice(index, 1);
        },
        addTeam() {
            this.form.post(route('departments.store'))
            this.closeAddTeamModal();
            this.showSuccessModal('add');
        },
        openDeleteAllTeamMembersModal(team) {
            this.teamToDeleteAllMembers = team;
            this.deletingAllTeamMembers = true;
        },
        closeDeleteAllTeamMembersModal() {
            this.deletingAllTeamMembers = false;
            this.teamToDeleteAllMembers = null;
        },
        openDeleteTeamModal(team) {
            this.teamToDelete = team;
            this.deletingTeam = true;
        },
        closeDeleteTeamModal() {
            this.deletingTeam = false;
            this.teamToDelete = null;
        },
        deleteTeam() {
            router.delete(`/departments/${this.teamToDelete.id}`);
            this.closeDeleteTeamModal();
            this.showSuccessModal('delete');
        },
        deleteAllTeamMembers() {
            this.deleteMembersForm.patch(route('departments.remove.members', {department: this.teamToDeleteAllMembers.id}));
            this.closeDeleteAllTeamMembersModal();
        },
        getEditHref(department) {
            return route('departments.show', {department: department.id});
        }
    },
    watch: {
        department_query: {
            handler() {
                if (this.department_query.length > 0) {
                    axios.get('/departments/search', {
                        params: {query: this.department_query}
                    }).then(response => {
                        this.department_search_results = response.data
                    })
                }
            },
            deep: true
        },
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
    data() {
        return {
            addingTeam: false,
            deletingTeam: false,
            teamToDelete: null,
            teamToDeleteAllMembers: null,
            deletingAllTeamMembers: false,
            showSuccess: false,
            showSearchbar: false,
            successHeading: '',
            form: useForm({
                svg_name: "",
                name: "",
                assigned_users: [],
            }),
            department_query: "",
            department_search_results: [],
            user_query: "",
            user_search_results: [],
            deleteMembersForm: this.$inertia.form({
                _method: 'PUT',
                users: [],
            }),
        }
    },
    setup() {
        return {
            iconMenuItems
        }
    }
})
</script>
