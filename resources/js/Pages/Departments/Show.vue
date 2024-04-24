<template>
    <app-layout>
        <div class="max-w-screen-lg my-12 ml-20 mr-40">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="headline1">{{ $t('Team profile')}}</h2>
                </div>
                <div class="flex mt-12">
                    <Menu as="div" class=" relative">
                        <div>
                            <MenuButton class="flex items-center rounded-full focus:outline-none">
                                <ChevronDownIcon v-if="teamForm.svg_name === ''"
                                                 class="ml-1 flex-shrink-0 mt-1 h-20 w-20 flex my-auto items-center font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                <TeamIconCollection v-else class="h-24 w-24" :iconName="teamForm.svg_name"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="z-40 overflow-y-auto origin-top-right absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <MenuItem v-for="item in iconMenuItems" v-slot="{ active }">
                                    <Link href="#" @click="teamForm.svg_name = item.iconName"
                                          :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TeamIconCollection class="h-16 w-16" :iconName="item.iconName"/>
                                    </Link>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <div class="relative w-full ml-8 mt-8 w-3/4 max-w-xl">
                        <input id="teamName" v-model="teamForm.name" @focusout="editTeam" type="text"
                               class="peer pl-0 h-12 w-full text-xl font-bold focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="teamName"
                               class="absolute left-0 text-sm -top-7 text-gray-600 -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                    </div>
                </div>
                <div class="flex items-center mt-16 mr-8">
                    <div class="mt-3" v-if="teamForm.users.length === 0">
                        <span class="xsLight">{{ $t('No team members added yet')}}</span>
                    </div>
                    <div v-else class="mt-3" v-for="(user, index) in teamForm.users">
                        <UserPopoverTooltip :id="user.id" :user="user" :height="9" :width="9" :class="index !== 0 ? '-ml-3' : ''"/>
                    </div>
                    <Menu as="div" class="my-auto relative ml-4">
                        <div class="flex">
                            <MenuButton
                                class="flex bg-tagBg p-0.5 rounded-full">
                                <DotsVerticalIcon
                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                    aria-hidden="true"/>
                            </MenuButton>
                            <div v-if="this.$page.props.show_hints" class="absolute flex w-48 ml-8">
                                <div>
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                </div>
                                <div class="flex">
                                    <span class="ml-2 hind mt-2">{{ $t('Assemble your team')}}</span>
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
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openChangeTeamMembersModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Edit team')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openDeleteAllTeamMembersModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Remove all team members')}}
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
                <div class="flex mt-12">
                    <span @click="openDeleteTeamModal()" class="xsLight cursor-pointer">{{ $t('Delete team permanently')}}</span>
                </div>
            </div>

        </div>
        <!-- Team löschen Modal -->
        <jet-dialog-modal :show="deletingTeam" @close="closeDeleteTeamModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{ $t('Delete Team')}}
                    </div>
                    <XIcon @click="closeDeleteTeamModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="errorText">
                        {{ $t('Are you sure you want to delete the team { teamName } from the system?', { teamName: department.name })}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteTeam">
                            {{ $t('Delete')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteTeamModal()"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!--Alle Nutzer aus Team löschen Modal -->
        <jet-dialog-modal :show="deletingAllTeamMembers" @close="closeDeleteAllTeamMembersModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('Delete all team members')}}
                    </div>
                    <XIcon @click="closeDeleteAllTeamMembersModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="errorText">
                        {{ $t('Are you sure you want to remove all members of the team { teamName }?', { teamName: department.name })}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteAllTeamMembers">
                            {{ $t('Delete all team members')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteAllTeamMembersModal"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Change TeamMember Modal -->
        <jet-dialog-modal :show="showChangeTeamMemberModal" @close="closeChangeTeamMembersModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_team_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-3">
                    <div class="headline1 my-2">
                        {{ $t('Edit team members')}}
                    </div>
                    <XIcon @click="closeChangeTeamMembersModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="xsLight">
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
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                         text-base ring-1 ring-black ring-opacity-5
                                         overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(user, index) in user_search_results" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addUserToTeamUsersArray(user)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ user.first_name }} {{ user.last_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                        </div>
                        <span v-for="(user,index) in department.users"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full object-cover"
                                     :src="user.profile_photo_url"
                                     alt=""/>
                                <span class="flex ml-4 sDark">
                                {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteUserFromTeam(user)">
                                <span class="sr-only">User aus Team entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                    </div>
                    <div class="w-full items-center text-center">
                        <FormButton
                            @click="editTeam"
                            :text="$t('Save')"
                            class="inline-flex items-center mt-8"
                        />
                    </div>

                </div>

            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>


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

import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, ChevronDownIcon, DotsVerticalIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Inertia} from "@inertiajs/inertia";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import Permissions from "@/mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    mixins: [Permissions],
    name: "Show",
    props: ['department'],
    components: {
        FormButton,
        UserPopoverTooltip,
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        XIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        XCircleIcon,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        CheckIcon,
        ChevronDownIcon,
        UserTooltip
    },
    data() {
        return {
            showChangeTeamMemberModal: false,
            deletingTeam: false,
            showSuccess: false,
            deletingAllTeamMembers: false,
            user_query: "",
            user_search_results: [],
            teamForm: this.$inertia.form({
                _method: 'PUT',
                name: this.department.name,
                svg_name: this.department.svg_name,
                users: this.department.users,
            }),
        }
    },
    methods: {
        openDeleteTeamModal() {
            this.deletingTeam = true;
        },
        closeDeleteTeamModal() {
            this.deletingTeam = false;
        },
        openDeleteAllTeamMembersModal() {
            this.deletingAllTeamMembers = true;
        },
        closeDeleteAllTeamMembersModal() {
            this.deletingAllTeamMembers = false;
        },
        deleteTeam() {
            Inertia.delete(`/departments/${this.department.id}`);
            this.closeDeleteTeamModal()
        },
        openChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = true;
        },
        closeChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = false;
        },
        deleteUserFromTeam(user) {
            this.department.users.splice(this.department.users.indexOf(user), 1);
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        editTeam() {
            this.teamForm.patch(route('departments.edit', {department: this.department.id}));
            this.showSuccessButton();
            this.closeChangeTeamMembersModal();
        },
        deleteAllTeamMembers() {
            this.teamForm.users = [];
            this.teamForm.patch(route('departments.edit', {department: this.department.id}));
            this.closeDeleteAllTeamMembersModal();
        },
        addUserToTeamUsersArray(user) {
            for (let teamUser of this.teamForm.users) {
                //if User is already in Team, do nothing
                if (user.id === teamUser.id) {
                    this.user_query = ""
                    return;
                }
            }
            this.teamForm.users.push(user);
            this.user_query = "";
            this.user_search_results = []
        },
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
            iconMenuItems
        }
    }
}
</script>

<style scoped>

</style>
