<template>
    <app-layout>
        <div>
            <div>
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <div>
                        <div class="max-w-screen-lg py-4 pl-20 pr-4">
                            <div class="flex">
                                <img class="mt-6 h-16 w-16 rounded-full flex justify-start object-cover"
                                     :src="user_to_edit.profile_photo_url"
                                     alt=""/>
                                <div class="mt-6 flex flex-grow w-full">
                                    <div class="headline1 flex my-auto ml-6">
                                        {{ userForm.first_name }}
                                    </div>
                                    <div class="headline1 flex my-auto ml-2">
                                        {{ userForm.last_name }}
                                    </div>
                                </div>
                            </div>

                            <div class="my-10">
                                <div class="hidden sm:block">
                                    <div class="">
                                        <nav class="-mb-px flex space-x-8 uppercase xxsDark" aria-label="Tabs">
                                            <div v-for="tab in tabs" :key="tab.name" @click="changeTab(tab.id)"
                                                 :class="[tab.current ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent', 'whitespace-nowrap border-b-2 py-2 px-1 cursor-pointer']"
                                                 :aria-current="tab.current ? 'page' : undefined">{{ tab.name }}
                                            </div>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="">
                            <div class="max-w-screen-lg py-4 pl-20 pr-4" v-if="currentTab === 3">
                                <div>
                                    <SwitchGroup as="div" class="flex items-center">
                                        <Switch v-model="userForm.can_master"
                                                :class="[userForm.can_master ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                            <span aria-hidden="true"
                                                  :class="[userForm.can_master ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                                        </Switch>
                                        <SwitchLabel as="span" class="ml-3 text-sm">
                                            <span class="text-gray-900">Als Meister einsetzbar</span>
                                        </SwitchLabel>
                                    </SwitchGroup>
                                </div>

                                <p>
                                    Lege fest ob die Mitarbeiterin / der Mitarbeiter als Meister eingesetzt werden kann.
                                    Du kannst hierfür eigene Stundensätze festlegen.
                                </p>

                                <div>
                                    <div class="headline3 py-4">
                                        Stunden & Vergütung
                                    </div>

                                    <div class="flex">
                                        <input type="number" v-model="userForm.weekly_working_hours" placeholder="h"
                                               class="w-28 shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block"/>
                                        <div class="ml-4 h-10 flex items-center">
                                            h/Woche lt. Vertrag
                                        </div>
                                    </div>
                                    <div class="flex">
                                        <input type="number" v-model="userForm.salary_per_hour" placeholder="€"
                                               class="w-28 shadow-sm placeholder-secondary focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300 border-2 block"/>
                                        <div class="ml-4 h-10 flex items-center">
                                            €/h
                                        </div>
                                    </div>
                                    <div class="py-1">
                            <textarea placeholder="Weitere Informationen (variable Vergütung, Zuschläge, etc.)"
                                      id="salary_description"
                                      v-model="userForm.salary_description"
                                      rows="4"
                                      class="border-gray-300 border-2 resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                </div>


                                <AddButton @click="updateUserConditions"
                                           class=" inline-flex items-center px-12 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                           text="Änderungen speichern" mode="modal"/>
                            </div>




                            <div v-if="currentTab === 5">
                                <div class="max-w-screen-lg pl-20 pr-4">
                                    <h2 class="mb-8 headline2">Nutzerrechte</h2>
                                </div>

                                <div class="bg-userBg py-10">
                                    <div class="max-w-screen-lg py-4 pl-20 pr-4">
                                        <div
                                            class="uppercase mb-3 text-xs columnSubName flex items-center cursor-pointer"
                                            @click="showGlobalRoles = !showGlobalRoles">
                                            globale Rollen
                                            <div class="flex items-center ml-2">
                                                <SvgCollection svg-name="arrowUp"
                                                               v-if="showGlobalRoles"></SvgCollection>
                                                <SvgCollection svg-name="arrowDown"
                                                               v-if="!showGlobalRoles"></SvgCollection>
                                            </div>
                                        </div>
                                        <div class="relative justify-between flex items-center" v-if="showGlobalRoles"
                                             v-for="(role, index) in available_roles" :key=index>
                                            <div class="flex items-center h-7">
                                                <input
                                                    v-model="userForm.roles"
                                                    :value="role.name"
                                                    name="roles" type="checkbox"
                                                    class="focus:outline-none focus:ring-0 ring-offset-0 ring-0 appearance-none outline-0 h-6 w-6 text-success border-gray-300 border-2"/>

                                                <div class="ml-3 text-sm">
                                                    <label for="roles"
                                                           :class="[userForm.roles.indexOf(role.name) > -1 ? 'xsDark' : 'xsLight']">{{
                                                            role.name_de
                                                        }}</label>
                                                </div>
                                            </div>
                                            <div class="justify-end">
                                                <div :data-tooltip-target="role.name">
                                                    <InformationCircleIcon class="h-7 w-7 flex text-gray-400"
                                                                           aria-hidden="true"/>
                                                </div>
                                                <div :id="role.name" role="tooltip"
                                                     v-if="role.name_de === 'Adminrechte'"
                                                     class="inline-block bg-primary absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary bg-primary rounded-lg shadow-md opacity-0 transition-opacity duration-300 tooltip">
                                                    Administratoren haben im gesamten System Lese- und Schreibrechte -
                                                    weitere Einstellungen entfallen
                                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                                </div>
                                                <div :id="role.name" role="tooltip" v-else
                                                     class="inline-block bg-primary absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary bg-primary rounded-lg shadow-md opacity-0 transition-opacity duration-300 tooltip">
                                                    Hier fehlt noch Info Text
                                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="max-w-screen-lg py-4 pl-20 pr-4">
                                    <div v-if="showUserPermissions" class="flex flex-col w-full ">
                                        <div class="w-full mb-3" v-for="(permissions, group) in all_permissions">
                                            <div
                                                class="uppercase my-3 text-xs columnSubName flex items-center cursor-pointer"
                                                @click="permissions.show = !permissions.show">
                                                {{ group }}
                                                <div class="flex items-center ml-2">
                                                    <SvgCollection svg-name="arrowUp"
                                                                   v-if="!permissions.show"></SvgCollection>
                                                    <SvgCollection svg-name="arrowDown"
                                                                   v-if="permissions.show"></SvgCollection>
                                                </div>
                                            </div>
                                            <div v-if="!permissions.show"
                                                 class="relative justify-between flex items-center w-full"
                                                 v-for="(permission, index) in permissions"
                                                 :key=index>
                                                <div class="flex items-center h-7">
                                                    <input
                                                        :key="permission.name"
                                                        v-model="userForm.permissions"
                                                        :value="permission.name"
                                                        name="permissions" type="checkbox"
                                                        class="focus:outline-none focus:ring-0 ring-offset-0 ring-0 appearance-none outline-0 h-6 w-6 text-success border-gray-300 border-2"/>

                                                    <div class="ml-3 text-sm">
                                                        <label for="permissions"
                                                               :class="[userForm.permissions.indexOf(permission.name) > -1 ? 'xsDark' : 'xsLight']">{{
                                                                permission.name_de
                                                            }}</label>
                                                    </div>
                                                </div>
                                                <div class="justify-end">
                                                    <div :data-tooltip-target="permission.name">
                                                        <InformationCircleIcon class="h-7 w-7 flex text-gray-400"
                                                                               aria-hidden="true"/>
                                                    </div>
                                                    <div :id="permission.name" role="tooltip"
                                                         class="inline-block bg-primary absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary bg-primary rounded-lg shadow-md opacity-0 transition-opacity duration-300 tooltip">
                                                        {{ permission.tooltipText }}
                                                        <div class="tooltip-arrow" data-popper-arrow></div>
                                                    </div>
                                                </div>
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
                                    <div class="flex mt-12">
                                        <span @click="openDeleteUserModal()" class="xsLight cursor-pointer">Nutzer*in endgültig löschen</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <jet-validation-errors class="mb-4"/>


                </div>
            </div>
        </div>
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
    </app-layout>
</template>

<script>

import {Inertia} from "@inertiajs/inertia";


import {computed, defineComponent} from 'vue'
import JetActionMessage from '@/Jetstream/ActionMessage.vue'
import JetButton from '@/Jetstream/Button.vue'
import JetFormSection from '@/Jetstream/FormSection.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetLabel from '@/Jetstream/Label.vue'
import AppLayout from "@/Layouts/AppLayout";
import DeleteUserForm from "@/Pages/Profile/Partials/DeleteUserForm";
import JetSectionBorder from "@/Jetstream/SectionBorder";
import LogoutOtherBrowserSessionsForm from "@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm";
import TwoFactorAuthenticationForm from "@/Pages/Profile/Partials/TwoFactorAuthenticationForm";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm";
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {
    DotsVerticalIcon,
    PencilAltIcon,
    TrashIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    XIcon,
    CheckIcon, InformationCircleIcon
} from "@heroicons/vue/outline";
import Checkbox from "@/Layouts/Components/Checkbox";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import JetValidationErrors from '@/Jetstream/ValidationErrors.vue'
import AddButton from "@/Layouts/Components/AddButton";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Permissions from "@/mixins/Permissions.vue";
import Availability from "@/Pages/Users/Components/Availability.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {Switch, SwitchGroup, SwitchLabel} from '@headlessui/vue'
import UserShiftPlanFunctionBar from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlanFunctionBar.vue";
import UserShiftPlan from "@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue";

export default defineComponent({
    mixins: [Permissions],
    name: 'Edit',
    components: {
        UserShiftPlan,
        UserShiftPlanFunctionBar,
        Availability,
        SvgCollection,
        AddButton,
        DotsVerticalIcon,
        PencilAltIcon,
        TrashIcon,
        JetActionMessage,
        JetButton,
        JetFormSection,
        JetInput,
        JetInputError,
        JetLabel,
        AppLayout,
        DeleteUserForm,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        UpdateProfileInformationForm,
        JetSecondaryButton,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        Checkbox,
        ChevronDownIcon,
        ChevronUpIcon,
        JetDialogModal,
        XIcon,
        TeamIconCollection,
        JetValidationErrors,
        CheckIcon,
        InformationCircleIcon, Switch, SwitchGroup, SwitchLabel
    },
    props: ['shifts', 'user_to_edit', 'permissions', 'all_permissions', 'departments', 'password_reset_status', 'available_roles', 'calendarData', 'dateToShow', 'vacations', 'daysWithEvents', 'dateValue', 'projects', 'eventTypes', 'rooms'],
    data() {
        return {
            showGlobalRoles: true,
            showUserPermissions: true,
            deletingUser: false,
            teamCheckboxes: [],
            showSuccessModal: false,
            userForm: useForm({
                first_name: this.user_to_edit.first_name,
                last_name: this.user_to_edit.last_name,
                business: this.user_to_edit.business,
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
            tabs: [
                {id: 1, name: 'Einsatzplan', href: '#', current: true},
                {id: 2, name: 'Verfügbarkeit', href: '#', current: false},
                {id: 3, name: 'Konditionen', href: '#', current: false},
                {id: 4, name: 'Persönliche Daten', href: '#', current: false},
                {id: 5, name: 'Nutzerrechte', href: '#', current: false},
            ],
            currentTab: 1,
        }
    },
    methods: {
        changeTab(tabId) {
            this.tabs.forEach((tab) => {
                tab.current = tab.id === tabId;
                this.currentTab = tabId;
            })
        },
        openDeleteUserModal() {
            this.deletingUser = true;
        },
        closeDeleteUserModal() {
            this.deletingUser = false;
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
        },
        deleteUser() {
            Inertia.delete(`/users/${this.user_to_edit.id}`);
            this.closeDeleteUserModal()
        },
        editUser() {
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        deleteTeamFromDepartmentsArray(index) {
            this.userForm.departments.splice(index, 1);
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
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        deleteFromAllDepartments() {
            this.userForm.departments = [];
            this.userForm.patch(route('user.update', this.user_to_edit.id));
            this.openSuccessModal();
        },
    },
})
</script>
