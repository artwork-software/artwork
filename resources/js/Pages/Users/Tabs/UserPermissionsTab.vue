<template>
    <div>
        <div>
            <h2 class="mb-8 headline2">{{ $t('User rights')}}</h2>
        </div>

        <div class="bg-lightBackgroundGray py-10 px-10 -mx-5">
            <div>
                <div class="uppercase mb-3 text-xs columnSubName flex items-center cursor-pointer" @click="showGlobalRoles = !showGlobalRoles">
                    {{ $t('Global roles')}}
                    <div class="flex items-center ml-2">
                        <SvgCollection svg-name="arrowUp" v-if="showGlobalRoles"></SvgCollection>
                        <SvgCollection svg-name="arrowDown" v-if="!showGlobalRoles"></SvgCollection>
                    </div>
                </div>
                <div class="relative justify-between flex items-center" v-if="showGlobalRoles" v-for="(role, index) in available_roles" :key=index>
                    <div class="flex items-center h-7">
                        <input
                            v-model="userForm.roles"
                            :value="role.name"
                            name="roles"
                            type="checkbox"
                            class="input-checklist"
                            @change="this.editUser()"
                        />

                        <div class="ml-3 text-sm font-lexend capitalize">
                            <label for="roles" :class="[userForm.roles.indexOf(role.name) > -1 ? 'text-gray-800' : 'text-gray-400']">{{$t(role.translation_key)}}</label>
                        </div>
                    </div>
                    <div class="justify-end">
                        <ToolTipDefault :left="true" :tooltip-text="$t(role.tooltipKey)" />
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div v-if="showUserPermissions" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="w-full mb-3" v-for="(group, groupName) in this.computedGroupedPermissions" v-show="group.shown">
                    <div class="flex items-center justify-between select-none mb-2 mt-3">
                        <div class="flex items-center gap-x-2 xxsLight !font-bold uppercase" @click="group.show = typeof group.show === 'undefined' ? false : !group.show">
                            {{ $t(groupName) }}
                            <div class="flex items-center ml-2">
                                <SvgCollection svg-name="arrowUp" v-if="typeof group.show === 'undefined' ? true : group.show"/>
                                <SvgCollection svg-name="arrowDown" v-else/>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs underline text-artwork-buttons-create cursor-pointer" @click="checkOrUncheckAllPermissionsOfGroup(group)">
                                {{ group.permissions.some(permission => this.userForm.permissions.includes(permission.name)) ? $t('Deselect all') : $t('Select all') }}
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2 divide-y divide-gray-200 divide-dashed">
                        <div v-if="typeof group.show === 'undefined' || group.show" class="flex items-start justify-between pt-2" v-for="(permission, index) in group.permissions" :key=index>
                            <div class="flex">
                                <input
                                    :key="permission.name"
                                    v-model="userForm.permissions"
                                    :value="permission.name"
                                    name="permissions"
                                    type="checkbox"
                                    class="input-checklist"
                                    @change="this.editUser()"
                                />

                                <div class="ml-3 text-sm font-lexend">
                                    <label for="permissions"
                                           :class="[userForm.permissions.indexOf(permission.name) > -1 ? 'text-gray-800' : 'text-gray-400']">{{ $t(permission.translation_key) }}</label>
                                </div>
                            </div>
                            <div>
                                <ToolTipDefault :left="true" :tooltip-text="$t(permission.tooltipKey)"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex mt-12">
                <span @click="openDeleteUserModal()" class="font-lexend text-sm cursor-pointer text-red-500">{{ $t('Permanently delete user')}}</span>
            </div>
        </div>
    </div>

    <!-- Nutzer*in löschen Modal -->
    <BaseModal @closed="closeDeleteUserModal" v-if="deletingUser" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="headline1 my-2">
                {{ $t('Delete user')}}
            </div>
            <div class="errorText">
                {{ $t('re you sure you want to delete {last_name}, {first_name} from the system?', {last_name: user_to_edit.last_name, first_name: user_to_edit.first_name})}}
            </div>
            <div class="flex justify-between mt-6">
                <button class="bg-artwork-navigation-background focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent text-base font-bold uppercase shadow-sm text-secondaryHover" @click="deleteUser">
                    {{ $t('Delete') }}
                </button>
                <div class="flex my-auto">
                    <span @click="closeDeleteUserModal()" class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                </div>
            </div>
        </div>
    </BaseModal>
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
import {useForm} from "@inertiajs/vue3";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {router} from "@inertiajs/vue3";
import {reactive} from "vue";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    components: {
        BaseModal,
        ToolTipDefault,
        JetDialogModal,
        CheckIcon,
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
                    if (permission.name === 'can view and delete sage100-api-data') {
                        if (this.$page.props.sageApiEnabled) {
                            groupedPermissions[group].permissions.push(permission);
                        }
                        return;
                    }

                    groupedPermissions[group].permissions.push(permission);
                });

                groupedPermissions[group].shown = groupedPermissions[group].permissions.length > 0;
            }

            // Sortiere Gruppen nach Anzahl der Permissions
            const sortedGroupedPermissions = Object.fromEntries(
                Object.entries(groupedPermissions)
                    .sort(([, a], [, b]) => b.permissions.length - a.permissions.length)
            );

            return reactive(sortedGroupedPermissions);
        }
    },
    methods: {
        checkOrUncheckAllPermissionsOfGroup(group) {
            if (group.permissions.some(permission => this.userForm.permissions.includes(permission.name))) {
                // Entferne alle Berechtigungen der Gruppe
                this.userForm.permissions = this.userForm.permissions.filter(permission =>
                    !group.permissions.some(p => p.name === permission)
                );
            } else {
                // Füge alle Berechtigungen der Gruppe hinzu
                this.userForm.permissions = [...new Set([...this.userForm.permissions, ...group.permissions.map(p => p.name)])];
            }

            this.editUser();
        },
        editUser() {
            this.userForm.patch(
                route('user.update.permissions-and-roles', {user: this.user_to_edit.id}),
                {
                    preserveScroll: true
                }
            );
        },
        openDeleteUserModal() {
            this.deletingUser = true;
        },
        closeDeleteUserModal() {
            this.deletingUser = false;
        },
        deleteUser() {
            router.delete(`/users/${this.user_to_edit.id}`);
            this.closeDeleteUserModal()
        },
    }
}
</script>
