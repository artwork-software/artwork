<template>
    <UserHeader :title="$t('All permission presets')">
        <div class="xl:max-w-screen-xl mt-12 flex flex-col">
            <div class="flex items-center">
                <h2 class="headline1">{{ $t('All permission presets')}}</h2>
                <div class="flex items-center">
                    <button @click="openPermissionPresetModal('create')"
                            type="button"
                            class="rounded-full bg-artwork-buttons-create p-1 mx-1 text-white shadow-sm hover:bg-artwork-buttons-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">
                        <PlusIcon class="h-4 w-4" aria-hidden="true"/>
                    </button>
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                        <span class="hind ml-1 my-auto">{{ $t('Create new permission presets')}}</span>
                    </div>
                </div>
            </div>
            <ul role="list" class="mt-6 w-full">
                <li v-for="(permissionPreset, index) in permission_presets"
                    :key="permissionPreset.id"
                    class="py-2 flex justify-between items-center border-b-2"
                >
                    <span @click="openPermissionPresetModal('edit', permissionPreset)"
                          class="mr-3 sDark cursor-pointer">
                        {{ permissionPreset.name }}
                    </span>
                    <Menu as="div" class="relative z-10">
                        <div>
                            <div class="flex">
                                <MenuButton
                                    class="flex bg-tagBg p-0.5 rounded-full">
                                    <DotsVerticalIcon
                                        class="flex-shrink-0 h-6 w-6 text-menuartwork-buttons-create"
                                        aria-hidden="true"/>
                                </MenuButton>
                                <div v-if="this.$page.props.show_hints && index === 0"
                                     class="absolute flex w-48 ml-9">
                                    <div>
                                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                    </div>
                                    <div class="flex">
                                        <span class="hind ml-1">{{  $t('Edit a permission preset') }}</span>
                                    </div>
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
                                class="w-64 origin-top-right absolute right-0 mr-4 mt-2 shadow-lg bg-primary focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openPermissionPresetModal('edit', permissionPreset)"
                                           :class="[
                                               active ?
                                               'bg-primaryHover text-white' :
                                               'text-secondary',
                                               'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased'
                                           ]"
                                        >
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Edit permission preset')}}
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a @click="openConfirmPermissionPresetDeleteModal(permissionPreset)"
                                           :class="[
                                               active ?
                                               'bg-primaryHover text-white' :
                                               'text-secondary',
                                               'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased'
                                           ]"
                                        >
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            <span>
                                                {{$t('Delete permission preset')}}
                                            </span>
                                        </a>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </li>
            </ul>
        </div>
    </UserHeader>
    <permission-preset-modal
        v-if="showPermissionPresetModal"
        :show="showPermissionPresetModal"
        :available_permissions="available_permissions"
        :mode="this.permissionPresetModalMode"
        :permission_preset="this.permissionPresetModalResource"
        @close="closePermissionPresetModal"
    />
    <confirmation-component
        v-if="showConfirmDeletePermissionPresetModal"
        confirm="Löschen"
        titel="Rechte-Preset löschen?"
        :description="this.confirmDeletePermissionPresetModalDescription"
        @closed="closeConfirmPermissionPresetDeleteModal"
    />
    <success-modal
        v-if="showPermissionPresetSuccessModal"
        title="Erfolg"
        :description="showPermissionPresetSuccessModal"
        button="Schließen"
        @closed="closePermissionPresetSuccessModal"
    />
    <error-component
        v-if="showPermissionPresetErrorModal"
        :titel="$t('Unfortunately an error has occurred')"
        :description="showPermissionPresetErrorModal"
        @closed="closePermissionPresetErrorModal"
    />
</template>

<script>
import {defineComponent} from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import UserTabs from "@/Pages/Users/Components/UserTabs.vue";
import UserHeader from "@/Pages/Users/UserHeader.vue";
import {PlusIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import PermissionPresetModal from "@/Pages/PermissionPresets/Components/PermissionPresetModal.vue";
import {DotsVerticalIcon, PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {router} from "@inertiajs/vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";

export default defineComponent({
    components: {
        ErrorComponent,
        SuccessModal,
        ConfirmationComponent,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        PermissionPresetModal,
        PlusIcon,
        SvgCollection,
        UserHeader,
        UserTabs,
        AppLayout
    },
    props: [
        'permission_presets',
        'available_permissions'
    ],
    data() {
        return {
            showPermissionPresetModal: false,
            permissionPresetModalMode: null,
            permissionPresetModalResource: null,
            showConfirmDeletePermissionPresetModal: false,
            confirmDeletePermissionPresetIdToDelete: null,
            confirmDeletePermissionPresetModalDescription: null
        }
    },
    computed: {
        showPermissionPresetSuccessModal() {
            return this.$page.props.flash.success;
        },
        showPermissionPresetErrorModal() {
            return this.$page.props.flash.error;
        }
    },
    methods: {
        openPermissionPresetModal(modalMode, permissionPreset = null) {
            this.permissionPresetModalMode = modalMode;
            this.permissionPresetModalResource = permissionPreset;
            this.showPermissionPresetModal = true;
        },
        closePermissionPresetModal() {
            this.permissionPresetModalMode = null;
            this.permissionPresetModalResource = null;
            this.showPermissionPresetModal = false;
        },
        openConfirmPermissionPresetDeleteModal(permissionPreset) {
            this.confirmDeletePermissionPresetIdToDelete = permissionPreset.id;
            this.confirmDeletePermissionPresetModalDescription = this.$t('Do you really want to delete the {presetName} rights preset? This cannot be undone.', {presetName: permissionPreset.name})
            this.showConfirmDeletePermissionPresetModal = true;
        },
        closeConfirmPermissionPresetDeleteModal(bool) {
            if (bool) {
                router.delete(
                    route(
                        'permission-presets.destroy',
                        {
                            permission_preset: this.confirmDeletePermissionPresetIdToDelete
                        }
                    )
                );
            }
            this.showConfirmDeletePermissionPresetModal = false;
            this.confirmDeletePermissionPresetIdToDelete = null;
            this.confirmDeletePermissionPresetModalDescription = null;
        },
        closePermissionPresetSuccessModal() {
            this.$page.props.flash.success = null;
        },
        closePermissionPresetErrorModal() {
            this.$page.props.flash.error = null;
        }
    }
})
</script>
