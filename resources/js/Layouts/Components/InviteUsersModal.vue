<template>
    <jet-dialog-modal :show="show" @close="closeUserModal(false)">
        <template #content>
            <img src="/Svgs/Overlays/illu_user_invite.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <XIcon @click="closeUserModal(false)"
                       class="h-5 w-5 flex text-secondary cursor-pointer absolute right-0 mr-10"
                       aria-hidden="true"/>
                <div class="mt-8 headline1">
                    Nutzer*innen einladen
                </div>
                <div class="xsLight my-3">
                    Du kannst mehrere Nutzer*innen mit den gleichen Nutzerrechten und Teamzugehörigkeiten auf einmal
                    einladen.
                </div>
                <div class="mt-6">
                    <div class="flex mt-8">
                        <div class="relative w-72 mr-4">
                            <input v-on:keyup.enter=addEmailToInvitationArray id="email" v-model="emailInput"
                                   type="text"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="email"
                                   class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">E-Mail*</label>
                        </div>
                        <jet-input-error :message="form.error" class="mt-2"/>

                        <div class="flex m-2">
                            <button
                                :class="[emailInput === '' ? 'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                @click="addEmailToInvitationArray" :disabled="!emailInput">
                                <CheckIcon class="h-5 w-5"></CheckIcon>
                            </button>
                        </div>

                    </div>
                    <span v-for="(email,index) in form.user_emails"
                          class="flex mt-4 mr-1 rounded-full items-center sDark">
                            {{ email }}
                    <button type="button" @click="deleteEmailFromInvitationArray(index)">
                    <span class="sr-only">Email aus Einladung entfernen</span>
                        <XCircleIcon
                            class="ml-1 mt-1 h-5 w-5 hover:text-error "/>
                    </button>
                    </span>
                    <ul>
                        <li class="errorText" v-for="(error,key) in errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                    <span v-if="form.departments.length === 0" class="flex inline-flex mt-16 pt-1 -mr-3">

                        </span>
                    <span class="flex inline-flex mt-4 -mr-3" v-for="team in form.departments">
                                <TeamIconCollection class="h-14 w-14 rounded-full ring-2 ring-white object-cover"
                                                    :iconName="team.svg_name"/>
                        </span>
                    <Disclosure as="div">
                        <div class="flex mt-4 mb-10">
                            <DisclosureButton>
                                <AddButton text="Zu Teams zuweisen" mode="page"/>
                            </DisclosureButton>
                            <div v-if="$page.props.can.show_hints && form.departments.length === 0" class="flex mt-2">
                                <SvgCollection svgName="arrowLeft" class="mt-2 ml-2"/>
                                <span class="hind ml-1 my-auto">Teile die Nutzer*innen direkt deinen Teams zu</span>
                            </div>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <DisclosurePanel
                                class="origin-top-right absolute overflow-y-auto max-h-48 w-72 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div v-if="departments.length === 0">
                                    <span class="text-secondary p-1 ml-4 flex flex-nowrap">Keine Teams zum Zuweisen vorhanden</span>
                                </div>
                                <div v-for="team in departments">
                                        <span class="flex "
                                              :class="[team.checked ? 'text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-md subpixel-antialiased']">
                                            <input :key="team.name" v-model="team.checked" type="checkbox"
                                                   @change="teamChecked(team)"
                                                   class="mr-3 ring-offset-0 focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-secondary"/>
                                            <TeamIconCollection class="h-9 w-9 rounded-full" :iconName="team.svg_name"/>
                                            <span class="ml-2">
                                            {{ team.name }}
                                            </span>
                                        </span>
                                </div>
                            </DisclosurePanel>
                        </transition>
                    </Disclosure>
                    <div class="pb-5 my-2 border-gray-200 sm:pb-0">
                        <h3 class="mt-6 mb-8 headline2">Nutzerrechte
                            definieren</h3>

                        <div class="mb-8">
                            <div v-for="role in roles">
                                <Checkbox @click="changeRole(role)" :item="role"></Checkbox>
                            </div>
                        </div>

                    </div>
                    <div v-if="!this.form.roles.includes('artwork admin')" class="pb-5 my-2 border-gray-200 sm:pb-0">
                        <div v-on:click="showPresets = !showPresets">
                            <h2 class="flex headline6Light cursor-pointer mb-2">
                                Rechte-Presets
                                <ChevronUpIcon v-if="showPresets"
                                               class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                                <ChevronDownIcon v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                            </h2>
                        </div>

                        <div class="mb-8" v-if="showPresets">
                            <div v-for="preset in presets">
                                <Checkbox @click="usePreset(preset)" :item="preset"></Checkbox>
                            </div>
                        </div>

                    </div>
                    <div v-if="!this.form.roles.includes('artwork admin')">
                        <div v-on:click="showUserPermissions = !showUserPermissions">
                            <h2 class="flex headline6Light cursor-pointer mb-2">
                                Nutzerrechte
                                <ChevronUpIcon v-if="showUserPermissions"
                                               class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronUpIcon>
                                <ChevronDownIcon v-else class=" ml-1 mr-3 flex-shrink-0 mt-1 h-4 w-4"></ChevronDownIcon>
                            </h2>
                        </div>
                        <div v-if="showUserPermissions && this.form.role !== 'admin'"
                             class="flex flex-col">

                            <div v-for="(permissions, group) in all_permissions">

                                <h3 class="headline6Light mb-2 mt-6">{{ group }}</h3>

                                <div class="relative w-full flex items-center"
                                     v-for="(permission, index) in permissions" :key=index>
                                    <Checkbox @click="changePermission(permission)" class="w-full"
                                              :item="permission"></Checkbox>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

                <div class="w-full items-center text-center">
                    <AddButton :class="[form.processing || (form.user_emails.length === 0 && emailInput.length < 3) ?
                    'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none']"
                               class="mt-8 inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                               @click="addUser"
                               :disabled=" form.processing || (form.user_emails.length === 0 && emailInput.length < 3)"
                               text="Einladen" mode="modal"/>
                </div>
            </div>
        </template>

    </jet-dialog-modal>
</template>
<script>

import Permissions from "@/mixins/Permissions.vue";

const presets = [
    {
        name: 'Standard User',
        name_de: 'Standard-User',
        tooltipText: '',
        showIcon: false
    },
    {
        name: 'Vertrags- & Dokumentenadmin',
        name_de: 'Vertrags- & Dokumentenadmin',
        tooltipText: '',
        showIcon: false
    },
    {
        name: 'Budgetadmin',
        name_de: 'Budgetadmin',
        tooltipText: '',
        showIcon: false
    },
    {
        name: 'Disponent*in',
        name_de: 'Disponent*in',
        tooltipText: '',
        showIcon: false
    },
    {
        name: 'Finanzierungsquellenadmin',
        name_de: 'Finanzierungsquellenadmin',
        tooltipText: '',
        showIcon: false,
    }
];

import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon} from "@heroicons/vue/outline";
import {ChevronDownIcon, ChevronUpIcon, XCircleIcon, CheckIcon} from '@heroicons/vue/solid'
import Checkbox from "@/Layouts/Components/Checkbox.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {Disclosure, DisclosureButton, DisclosurePanel} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    name: "InviteUsersModal",
    mixins: [Permissions],
    components: {
        JetDialogModal,
        AddButton,
        JetInputError,
        XIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
        CheckIcon,
        XCircleIcon,
        TeamIconCollection,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        SvgCollection
    },
    props: {
        show: Boolean,
        closeModal: Function,
        all_permissions: Array,
        departments: Array,
        roles: Array
    },
    data() {
        return {
            showUserPermissions: true,
            addingUser: false,
            deletingUser: false,
            showSuccessModal: false,
            userToDelete: {},
            emailInput: "",
            showSearchbar: false,
            user_query: "",
            user_search_results: [],
            showPresets:true,
            form: useForm({
                user_emails: [],
                permissions: [],
                departments: [],
                roles: [],
            }),
            showGlobalRoles: true
        }
    },
    computed: {
        errors() {
            return this.$page.props.errors;
        }
    },
    methods: {
        closeUserModal(bool){
            this.uncheckRolesAndPermissions();
            this.addingUser = false;
            this.emailInput = "";
            this.form.user_emails = [];
            this.form.permissions = [];
            this.form.departments = [];
            this.form.roles = [];
            this.departments.forEach((team) => {
                team.checked = false;
            })
            this.closeModal(bool);
        },
        addEmailToInvitationArray() {
            if (this.emailInput.indexOf(' ') === -1) {
                this.form.user_emails.push(this.emailInput);
            }
            this.emailInput = "";
        },
        deleteEmailFromInvitationArray(index) {
            this.form.user_emails.splice(index, 1);
        },
        teamChecked(team) {
            if (team.checked) {
                this.form.departments.push(team);
            } else {
                const spliceIndex = this.form.departments.findIndex(teamToSplice => {
                    return team.id === teamToSplice.id
                })
                this.form.departments.splice(spliceIndex, 1);
            }
        },
        changeRole(role) {
            if (!role.checked) {
                this.form.roles.push(role.name);
                role.checked = true;
            } else {
                if (this.form.roles.includes(role.name)) {
                    this.form.roles = this.form.roles.filter(permissionName => permissionName !== role.name);
                    role.checked = false;
                }
            }

        },
        changePermission(permission) {
            if (!permission.checked) {
                this.form.permissions.push(permission.name);
                permission.checked = true;
            } else {
                if (this.form.permissions.includes(permission.name)) {
                    this.form.permissions = this.form.permissions.filter(permissionName => permissionName !== permission.name);
                    permission.checked = false;
                }
            }

        },
        usePreset(preset) {
            // Get the preset permissions
            let presetPermissions = [];

            switch (preset.name) {
                case 'Standard User':
                    presetPermissions = ['view projects', 'create and edit own project', 'request room occupancy','can see and download contract modules'];
                    break;
                case 'Vertrags- & Dokumentenadmin':
                    presetPermissions = ['view edit upload contracts', 'can see, edit and delete project contracts and docs'];
                    break;
                case 'Budgetadmin':
                    presetPermissions = ['access project budgets', 'can add and remove verified states'];
                    break;
                case 'Disponent*in':
                    presetPermissions = ['admin rooms', 'create, delete and update rooms'];
                    break;
                case 'Finanzierungsquellenadmin':
                    presetPermissions = ['view edit add money_sources', 'can edit and delete money sources'];
                    break;
                default:
                    // Invalid preset name
                    return;
            }

            // Update the permissions based on the preset
            Object.values(this.all_permissions).forEach((permissions) => {
                permissions.forEach((permission) => {
                    if (presetPermissions.includes(permission.name)) {
                            permission.checked = !preset.checked;
                            if(permission.checked){
                                this.form.permissions.push(permission.name);
                            }else{
                                if (this.form.permissions.includes(permission.name)) {
                                    this.form.permissions = this.form.permissions.filter(permissionName => permissionName !== permission.name);
                                }
                            }
                    }
                });
            });
        },
        addUser() {
            if (this.emailInput.length >= 3) {
                this.form.user_emails.push(this.emailInput);
                this.emailInput = '';
            }
            this.form.post(route('invitations.store'), {
                onSuccess: () => {
                    this.closeUserModal(true);
                    this.emailInput = "";
                    this.form.user_emails = [];
                    this.form.permissions = [];
                    this.form.departments = [];
                    this.form.role = '';
                    this.departments.forEach((team) => {
                        team.checked = false;
                    })
                }
            });
        },
        uncheckRolesAndPermissions() {
            this.roles.forEach((role) => {
                role.checked = false;
            })
            this.all_permissions.Projekte.forEach((permission) => {
                permission.checked = false;
            })
            this.all_permissions.Raumbelegungen.forEach((permission) => {
                permission.checked = false;
            })
            this.all_permissions.Systemeinstellungen.forEach((permission) => {
                permission.checked = false;
            })
        },
    },
    setup() {

        return {
            presets
        }
    }
}
</script>

<style scoped>

</style>
