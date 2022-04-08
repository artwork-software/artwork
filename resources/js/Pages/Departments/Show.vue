<template>
    <app-layout title="Teamprofil">
        <div class="max-w-screen-xl my-12 flex flex-row ml-16 mr-40">
            <div class="flex">
                {{ department }}
                <h2 class="font-bold font-lexend text-2xl my-2">Teamprofil</h2>
            </div>
            <div class="flex mr-8">
                <div class="mt-3 -mr-3" v-for="user in department.users">
                    <img class="h-9 w-9 rounded-full"
                         :src="user.profile_photo_url"
                         alt=""/>
                </div>
                <Menu as="div" class="my-auto relative">
                    <div class="flex">
                        <MenuButton
                            class="flex">
                            <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                              aria-hidden="true"/>
                        </MenuButton>
                        <div v-if="$page.props.can.show_hints && index === 0" class="absolute flex w-40 ml-6">
                            <div>
                                <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                            </div>
                            <div class="flex">
                                <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite dein Team</span>
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
                            class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
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
                                    <a @click="deleteAllTeamMembers(department.id)"
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

        </div>
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
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import {DotsVerticalIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import JetButton from "@/Jetstream/Button";
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";

export default {
    name: "Show",
    props: ['department'],
    components: {
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
    },
    data() {
        return {
            showChangeTeamMemberModal: false,
        }
    },
    methods: {
        openChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = true;
        },
        closeChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = false;
        },
        deleteUserFromTeam(index) {
            this.department.users.splice(index, 1);
        },
        editTeam(){
            this.$inertia.patch(route('departments.edit', {department: this.department}));
            this.closeChangeTeamMembersModal();
        },
        deleteAllTeamMembers(){
            this.department.users = [];
            this.$inertia.patch(route('departments.edit', {department: this.department}));
        }
    }
}
</script>

<style scoped>

</style>
