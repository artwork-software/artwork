<template>
    <app-layout title="Teamprofil">
        <div class="max-w-screen-lg my-12 ml-20 mr-40">
            <div class="flex-wrap">
            <div class="flex">
                <h2 class="font-bold font-lexend text-2xl">Teamprofil</h2>
            </div>
            <div class="flex mt-12">
                <Menu as="div" class=" relative">
                    <div>
                        <MenuButton class="flex items-center rounded-full focus:outline-none">
                            <ChevronDownIcon v-if="teamForm.svg_name === ''"
                                             class="ml-1 flex-shrink-0 mt-1 h-20 w-20 flex my-auto items-center font-semibold rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                            <TeamIconCollection v-else class="h-24 w-24" :iconName="teamForm.svg_name" />
                        </MenuButton>
                    </div>
                    <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="z-40 origin-top-right absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                            <MenuItem v-for="item in iconMenuItems"  v-slot="{ active }">
                                <Link href="#" @click="teamForm.svg_name = item.iconName"
                                      :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <TeamIconCollection class="h-16 w-16" :iconName="item.iconName" />
                                </Link>
                            </MenuItem>
                        </MenuItems>
                    </transition>
                </Menu>
                <div class="relative w-full ml-8 mt-8 w-3/4 max-w-xl">
                    <input id="teamName" v-model="teamForm.name" type="text" class="peer pl-0 h-12 w-full text-xl font-bold focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                    <label for="teamName" class="absolute left-0 text-base -top-7 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                </div>
            </div>
            <div class="flex items-center mt-16 mr-8">
                <div v-if="teamForm.users.length === 0">
                    <span class="text-secondary subpixel-antialiased cursor-pointer">Noch keine Team-Mitglieder hinzugefügt</span>
                </div>
                <div v-else class="mt-3 -mr-3" v-for="user in teamForm.users">
                    <img class="h-9 w-9 rounded-full"
                         :src="user.profile_photo_url"
                         alt=""/>
                </div>
                <Menu as="div" class="my-auto relative">
                    <div class="flex mt-3">
                        <MenuButton
                            class="flex ml-6">
                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                              aria-hidden="true"/>
                        </MenuButton>
                        <div v-if="$page.props.can.show_hints" class="absolute flex w-48 ml-12">
                            <div>
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                            </div>
                            <div class="flex">
                                <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Stelle dein Team zusammen</span>
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
                                        Team bearbeiten
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a @click="deleteAllTeamMembers()"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        Alle Teammitglieder entfernen
                                    </a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
                <div class="pt-12">
                    <div class="mt-4 grid grid-cols-1 gap-y-4 gap-x-4 items-center sm:grid-cols-6">
                        <button v-if="!showSuccess" @click="editTeam"
                                class="sm:col-span-3 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        >Änderungen speichern
                        </button>
                        <button v-else type="submit"
                                class=" sm:col-span-3 items-center py-1.5 border bg-success focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                        >
                            <CheckIcon class="h-10 w-9 inline-block text-secondaryHover"/>
                        </button>
                    </div>
                </div>
                <div class="flex mt-12">
                    <span @click="openDeleteTeamModal()" class="text-secondary subpixel-antialiased cursor-pointer">Team endgültig löschen</span>
                </div>
            </div>

        </div>
        <!-- Nutzer*in löschen Modal -->
        <jet-dialog-modal :show="deletingTeam" @close="closeDeleteTeamModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Team löschen
                    </div>
                    <XIcon @click="closeDeleteTeamModal" class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer" aria-hidden="true" />
                    <div class="text-error">
                        Bist du sicher, dass du {{department.name}} aus dem System löschen möchtest?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteTeam">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteTeamModal()" class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>

            </template>

        </jet-dialog-modal>
        <!-- Change TeamMember Modal -->
        <jet-dialog-modal :show="showChangeTeamMemberModal" @close="closeChangeTeamMembersModal">
            <template #content>
                <div class="mx-3">
                    <div class="font-bold font-lexend text-primary text-2xl my-2">
                        Teammitglieder bearbeiten
                    </div>
                    <XIcon @click="closeChangeTeamMembersModal"
                           class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Tippe den Namen der Nutzer*innen ein, die du zum Team hinzufügen möchtest.
                    </div>
                    <!-- TODO: Volltextsuche nach allen users hier -->
                    <div class="mt-4">
                        <div class="flex">
                        </div>
                        <span v-for="(user,index) in department.users"
                              class="flex mt-4 mr-1 rounded-full items-center font-bold text-primary">
                            <div class="flex items-center">
                                <img class="flex h-11 w-11 rounded-full"
                                    :src="user.profile_photo_url"
                                    alt=""/>
                                <span class="flex ml-4">
                                {{ user.first_name }} {{ user.last_name }}
                                    </span>
                            </div>
                            <button type="button" @click="deleteUserFromTeam(index)">
                                <span class="sr-only">User aus Team entfernen</span>
                                <XCircleIcon class="ml-2 mt-1 h-5 w-5 hover:text-error "/>
                            </button>
                        </span>
                    </div>
                    <button @click="editTeam"
                            class=" inline-flex mt-8 items-center px-12 py-3 border bg-primary hover:bg-primaryHover focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                    >Speichern
                    </button>

                </div>

            </template>

        </jet-dialog-modal>
    </app-layout>
</template>

<script>

const iconMenuItems = [
    {iconName: 'departmentImagePlaceholder'},
    {iconName: 'teamIconTech'},
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

export default {
    name: "Show",
    props: ['department'],
    components: {
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
        ChevronDownIcon
    },
    data() {
        return {
            showChangeTeamMemberModal: false,
            deletingTeam: false,
            showSuccess: false,
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
        closeDeleteTeamModal(){
            this.deletingTeam = false;
        },
        deleteTeam(){
            Inertia.delete(`/departments/${this.department.id}`);
            this.closeDeleteUserModal()
        },
        openChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = true;
        },
        closeChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = false;
        },
        deleteUserFromTeam(index) {
            this.department.users.splice(index, 1);
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        editTeam(){
            this.teamForm.patch(route('departments.edit',{department: this.department.id}));
            this.showSuccessButton();
            this.closeChangeTeamMembersModal();
        },
        deleteAllTeamMembers(){
            this.teamForm.users = [];
            this.teamForm.patch(route('departments.edit',{department: this.department.id}));
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
