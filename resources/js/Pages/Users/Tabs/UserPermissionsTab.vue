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
                        <div class="flex gap-3">
                            <div class="flex h-6 shrink-0 items-center">
                                <div class="group grid size-4 grid-cols-1">
                                    <input v-model="userForm.roles" @change="this.editUser()" :value="role.name" id="comments" aria-describedby="comments-description" name="comments" type="checkbox" checked="" class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto" />
                                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                            <div class="text-sm/6">
                                <label for="comments" class="font-medium text-gray-900 capitalize font-lexend">{{$t(role.translation_key)}}</label>
                                <p id="comments-description" class="text-gray-500 font-lexend text-xs">{{ $t(role.tooltipKey) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="justify-end">
                        <ToolTipDefault :left="true" :tooltip-text="$t(role.tooltipKey)" />
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <div v-if="showUserPermissions" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="w-full mb-3" v-for="(group, groupName) in this.computedGroupedPermissions" v-show="group.shown">
                    <div class="flex items-center justify-between select-none mb-4 mt-3">
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
                    <div class="space-y-3 divide-y divide-gray-200 divide-dashed">
                        <div v-if="typeof group.show === 'undefined' || group.show" class="flex items-start justify-between pb-3" v-for="(permission, index) in group.permissions" :key=index>
                            <div class="flex gap-3">
                                <div class="flex h-6 shrink-0 items-center">
                                    <div class="group grid size-4 grid-cols-1">
                                        <input v-model="userForm.permissions" @change="this.editUser()" :value="permission.name" id="comments" aria-describedby="comments-description" name="comments" type="checkbox" checked="" class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 indeterminate:border-blue-600 indeterminate:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:checked:bg-gray-100 forced-colors:appearance-auto" />
                                        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-gray-950/25" viewBox="0 0 14 14" fill="none">
                                            <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-sm/6">
                                    <label for="comments" class="font-medium capitalize font-lexend" :class="[userForm.permissions.indexOf(permission.name) > -1 ? 'text-gray-900' : 'text-gray-500']">{{ $t(permission.translation_key) }}</label>
                                    <p id="comments-description" class="text-gray-400 font-lexend text-xs">{{ $t(permission.tooltipKey) }}</p>
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
