<template>
    <div>
        <div>
            <h2 class="mb-8 headline2">{{ $t('User rights')}}</h2>
        </div>

        <div class="bg-userBg py-10">
            <div>
                <div
                    class="uppercase mb-3 text-xs columnSubName flex items-center cursor-pointer"
                    @click="showGlobalRoles = !showGlobalRoles">
                    {{ $t('Global roles')}}
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
                             class="inline-block bg-primary absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary rounded-lg shadow-md opacity-0 transition-opacity duration-300 tooltip">
                            {{ $t('Administrators have read and write access to the entire system No further settings are required')}}
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                        <div :id="role.name" role="tooltip" v-else
                             class="inline-block bg-primary absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary rounded-lg shadow-md opacity-0 transition-opacity duration-300 tooltip">
                            Hier fehlt noch Info Text
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div v-if="showUserPermissions" class="flex flex-col w-full ">
                <div class="w-full mb-3"
                     v-for="(group, groupName) in this.computedGroupedPermissions"
                     v-show="group.shown"
                >
                    <div
                        class="uppercase my-3 text-xs columnSubName flex items-center cursor-pointer"
                        @click="group.show = typeof group.show === 'undefined' ? false : !group.show">
                        {{ groupName }}
                        <div class="flex items-center ml-2">
                            <SvgCollection svg-name="arrowUp"
                                           v-if="typeof group.show === 'undefined' ? true : group.show"
                            />
                            <SvgCollection svg-name="arrowDown"
                                           v-else
                            />
                        </div>
                    </div>
                    <div v-if="typeof group.show === 'undefined' || group.show"
                         class="relative justify-between flex items-center w-full"
                         v-for="(permission, index) in group.permissions"
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
                                       :class="[userForm.permissions.indexOf(permission.name) > -1 ? 'xsDark' : 'xsLight']">{{ permission.name_de }}</label>
                            </div>
                        </div>
                        <div class="justify-end">
                            <div :data-tooltip-target="permission.name">
                                <InformationCircleIcon class="h-7 w-7 flex text-gray-400"
                                                       aria-hidden="true"/>
                            </div>
                            <div :id="permission.name" role="tooltip"
                                 class="inline-block bg-primary absolute invisible z-10 py-2 px-3 text-sm font-medium text-secondary rounded-lg shadow-md opacity-0 transition-opacity duration-300 tooltip">
                                {{ permission.tooltipText }}
                                <div class="tooltip-arrow" data-popper-arrow></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-8">
                <div class="flex">
                    <FormButton
                        @click="editUser"
                        text="Einstellungen ändern"
                        />
                </div>
            </div>
            <div class="flex mt-12">
                <span @click="openDeleteUserModal()" class="xsLight cursor-pointer">{{ $t('Permanently delete user')}}</span>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <SuccessModal
        :show="showSuccessModal"
        @closed="closeSuccessModal"
        :title="$t('User successfully edited')"
        :description="$t('The changes have been saved successfully.')"
        :button="$t('Close')"
    />
    <!-- Nutzer*in löschen Modal -->
    <jet-dialog-modal :show="deletingUser" @close="closeDeleteUserModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Delete user')}}
                </div>
                <XIcon @click="closeDeleteUserModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="errorText">
                    {{ $t('re you sure you want to delete {last_name}, {first_name} from the system?', {last_name: user_to_edit.last_name, first_name: user_to_edit.first_name})}}
                </div>
                <div class="flex justify-between mt-6">
                    <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="deleteUser">
                        {{ $t('Delete') }}
                    </button>
                    <div class="flex my-auto">
                            <span @click="closeDeleteUserModal()"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                    </div>
                </div>
            </div>

        </template>

    </jet-dialog-modal>
</template>

<script>

import {
    CheckIcon,
    DotsVerticalIcon,
    InformationCircleIcon,
    PencilAltIcon,
    TrashIcon,
    XIcon
} from "@heroicons/vue/outline";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {Inertia} from "@inertiajs/inertia";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import {reactive} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    components: {
        FormButton,
        SuccessModal,
        JetDialogModal, CheckIcon,
        XIcon,
        PencilAltIcon,
        JetInputError,
        DotsVerticalIcon,
        TeamIconCollection,
        TrashIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        InformationCircleIcon,
        SvgCollection
    },
    props: [
        'user_to_edit',
        'permissions',
        'all_permissions',
        'available_roles',
    ],
    data() {
        return {
            showGlobalRoles: true,
            showUserPermissions: true,
            showSuccessModal: false,
            deletingUser: false,
            userForm: useForm({
                permissions: this.user_to_edit.permissions,
                roles: this.user_to_edit.roles,
            }),
        }
    },
    computed: {
        computedGroupedPermissions() {
            let groupedPermissions = {};

            for (const [group, permissions] of Object.entries(this.all_permissions)) {
                groupedPermissions[group] = {
                    shown: true,
                    permissions: []
                };

                permissions.forEach((permission) => {
                    //permissions depending on specific logic to be displayed
                    if (permission.name === 'can view and delete sage100-api-data') {
                        //this permission is only added when sage api is enabled
                        if (this.$page.props.sageApiEnabled) {
                            groupedPermissions[group].permissions.push(permission);
                        }
                        return;
                    }

                    //other permissions are pushed anytime
                    groupedPermissions[group].permissions.push(permission);
                });

                //groups are only shown when there are permissions to display
                groupedPermissions[group].shown = groupedPermissions[group].permissions.length > 0;
            }

            return reactive(groupedPermissions);
        }
    },
    methods: {
        editUser() {
            this.userForm.patch(route('user.update', {user: this.user_to_edit.id}));
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
        },
        openDeleteUserModal() {
            this.deletingUser = true;
        },
        closeDeleteUserModal() {
            this.deletingUser = false;
        },
        deleteUser() {
            Inertia.delete(`/users/${this.user_to_edit.id}`);
            this.closeDeleteUserModal()
        },
    }
}
</script>
