<template>
    <div>
        <div>
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
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
                        <input type="text" v-model="this.user_to_edit.email"
                               :disabled="!$page.props.is_admin"
                               :class="$page.props.is_admin ? '' : 'bg-gray-100'"
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
                                            <textarea placeholder="Was sollten die anderen artwork-User wissen?"
                                                      v-model="userForm.description" rows="5"
                                                      class="resize-none shadow-sm placeholder-secondary p-4 focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-2 block w-full border-gray-300"/>
                    </div>
                </div>
                <div class="sm:col-span-6 mt-4 flex inline-flex">
                                        <span v-if="userForm.departments.length === 0"
                                              class="text-secondary subpixel-antialiased my-auto mr-4">In keinem Team</span>
                    <span v-else class="flex -mr-3"
                          v-for="(team,index) in userForm.departments">
                                            <TeamIconCollection class="h-10 w-10 rounded-full ring-2 ring-white"
                                                                :iconName="team.svg_name"/>
                                        </span>
                    <Menu as="div" class="my-auto relative ml-5">
                        <div>
                            <MenuButton
                                class="flex bg-tagBg p-0.5 rounded-full">
                                <DotsVerticalIcon
                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
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
        <div class="mt-8">
            <div class="flex">
                <AddButton @click="editUser"
                           class=" inline-flex items-center px-12 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                           text="Einstellungen ändern" mode="modal"/>
            </div>
        </div>
        <div class="">
            <div class="flex mt-6" v-if="$page.props.is_admin">
                <span @click="resetPassword()" class="xsLight cursor-pointer">Passwort zurücksetzen</span>
            </div>
            <div v-if="password_reset_status" class="mb-4 font-medium text-sm text-green-600">
                {{ password_reset_status }}
            </div>
        </div>
    </div>

    <jet-dialog-modal :show="showChangeTeamsModal" @close="closeChangeTeamsModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_team_user.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-3">
                <div class="headline1 my-2">
                    Teamzugehörigkeit
                </div>
                <XIcon @click="closeChangeTeamsModal"
                       class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute text-secondary cursor-pointer"
                       aria-hidden="true"/>
                <div class="mt-4 xsLight">
                    Gib' an in welchen Teams die/der Nutzer*in ist.<br/> Beachte: Sie/Er hat die Berechtigung alle
                    den Teams zugeordneten <br/>Projekte einzusehen.
                </div>
                <div class="mt-8 mb-8">
                        <span v-if="departments.length === 0"
                              class="xsLight flex mb-6 mt-8 my-auto">Bisher sind keine Teams im Tool angelegt.</span>
                    <div v-for="team in departments">
                                        <span class=" flex items-center pr-4 py-2 text-md">
                                            <input :key="team.name" type="checkbox" :value="team" :id="team.id"
                                                   v-model="team.checked"
                                                   @change="teamChecked(team)"
                                                   class="mr-3 ring-offset-0 focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-secondary"/>
                                            <TeamIconCollection class="h-9 w-9 rounded-full ring-2 ring-white"
                                                                :iconName="team.svg_name"/>
                                            <span :class="[team.checked ? 'xsDark' : 'xsLight']"
                                                  class="ml-2">
                                            {{ team.name }}
                                            </span>
                                        </span>
                    </div>
                </div>
                <div class="w-full items-center text-center">
                    <AddButton class="mt-4 bg-buttonBlue hover:buttonHover focus:outline-none inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                               @click="saveNewTeams" text="Speichern" mode="modal"/>
                </div>
            </div>

        </template>

    </jet-dialog-modal>

    <!-- Success Modal -->
    <jet-dialog-modal :show="showSuccessModal" @close="closeSuccessModal">
        <template #content>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Nutzer*in erfolgreich bearbeitet
                </div>
                <XIcon @click="closeSuccessModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="successText">
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
</template>



<script>

import {CheckIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";

export default {
    components: {
        CheckIcon,
        JetDialogModal, XIcon,
        PencilAltIcon,
        JetInputError,
        AddButton,
        DotsVerticalIcon,
        TeamIconCollection,
        TrashIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
    },
    props: [
        'user_to_edit',
        'password_reset_status',
        'departments'
    ],
    data() {
        return {
            showChangeTeamsModal: false,
            showSuccessModal: false,
            userForm: useForm({
                first_name: this.user_to_edit.first_name,
                last_name: this.user_to_edit.last_name,
                business: this.user_to_edit.business,
                email: this.user_to_edit.email,
                position: this.user_to_edit.position,
                departments: this.user_to_edit.departments,
                phone_number: this.user_to_edit.phone_number,
                description: this.user_to_edit.description,
                permissions: this.user_to_edit.permissions,
                roles: this.user_to_edit.roles,
                can_master: this.user_to_edit.can_master,
                weekly_working_hours: this.user_to_edit.weekly_working_hours,
                salary_per_hour: this.user_to_edit.salary_per_hour,
                salary_description: this.user_to_edit.salary_description,
            }),
            resetPasswordForm: this.$inertia.form({
                email: this.user_to_edit.email
            }),
        }
    },
    mounted() {
        this.show = true;
        setTimeout(() => {
            this.show = false;
        }, 1000)
    },
    methods: {
        openChangeTeamsModal() {
            this.departments.forEach((team) => {
                this.userForm.departments.forEach((userTeam) => {
                    if (userTeam.id === team.id) {
                        team.checked = true;
                    }
                })
            })
            this.showChangeTeamsModal = true;
        },
        closeChangeTeamsModal() {
            this.showChangeTeamsModal = false;
        },
        deleteFromAllDepartments() {
            this.userForm.departments = [];
            this.userForm.patch(route('user.update', this.user_to_edit.id));
            this.openSuccessModal();
        },
        editUser() {
            if (this.$page.props.is_admin) {
                this.userForm.email = this.user_to_edit.email;
            }
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
            this.openSuccessModal();
        },
        resetPassword() {
            this.resetPasswordForm.post(route('user.reset.password'));
        },
        saveNewTeams() {
            this.userForm.patch(route('user.update', this.user_to_edit.id));
            this.closeChangeTeamsModal();
            this.openSuccessModal()
        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
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
    }
}
</script>


<style scoped>

</style>
