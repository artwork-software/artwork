<template>
    <UserHeader
        title="All permission presets"
        description="Edit and create permission presets"
    >
        <!-- Header Actions -->
        <template #tabBar>
            <ToolbarHeader
                :icon="IconUsersGroup"
                :title="$t('Permission presets')"
                :description="`${filteredPresets.length} ${$t('Permission presets')}`"
                icon-bg-class="bg-accent-50 text-accent-700"
                v-model="query"
                :search-enabled="true"
                :search-label="$t('Search presets')"
                :search-tooltip="$t('Search')"
            >
                <template #actions>
                    <button class="ui-button-add" @click="openPermissionPresetModal('create')">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('Create new permission presets') }}
                    </button>
                </template>
            </ToolbarHeader>

        </template>

        <!-- List / Empty State -->
        <template #default>
            <div v-if="filteredPresets.length === 0" class="rounded-2xl border border-border-subtle bg-white p-10 text-center">
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full ring-1 ring-inset ring-border-subtle">
                    <IconDotsVertical class="h-6 w-6 text-text-subtle" />
                </div>
                <h3 class="text-base font-semibold text-text">
                    {{ $t('No permission presets found') }}
                </h3>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Create your first preset to reuse permission sets quickly.') }}
                </p>
                <div class="mt-6">
                    <BaseUIButton
                        variant="secondary"
                        hide-icon
                        @click="openPermissionPresetModal('create')"
                    >
                        <span class="flex items-center gap-x-2">{{ $t('Create preset') }}</span>
                    </BaseUIButton>
                </div>
            </div>

            <ul v-else role="list" class="grid gap-3 lg:gap-4">
                <li
                    v-for="(preset, idx) in filteredPresets"
                    :key="preset.id"
                    class="group flex items-center justify-between rounded-2xl bg-white p-4 ring-1 ring-border-subtle transition hover:bg-surface-sunken hover:shadow-sm"
                >
                    <!-- Clickable name -->
                    <button
                        type="button"
                        @click="openPermissionPresetModal('edit', preset)"
                        class="text-left text-text transition hover:text-text"
                    >
                        <span class="block text-sm font-medium leading-6">{{ preset.name }}</span>
                    </button>

                    <!-- Row actions -->
                    <BaseMenu white-menu-background has-no-offset>
                        <BaseMenuItem white-menu-background :icon="IconEdit" @click="openPermissionPresetModal('edit', preset)" title="Edit permission preset"/>
                        <BaseMenuItem white-menu-background :icon="IconTrash" @click="openConfirmPermissionPresetDeleteModal(preset)" title="Delete permission preset"/>
                    </BaseMenu>
                </li>
            </ul>
        </template>
    </UserHeader>

    <!-- Modals -->
    <PermissionPresetModal
        v-if="showPermissionPresetModal"
        :show="showPermissionPresetModal"
        :available_permissions="available_permissions"
        :mode="permissionPresetModalMode"
        :permission_preset="permissionPresetModalResource"
        @close="closePermissionPresetModal"
    />

    <ConfirmationComponent
        v-if="showConfirmDeletePermissionPresetModal"
        confirm="Löschen"
        titel="Rechte-Preset löschen?"
        :description="confirmDeletePermissionPresetModalDescription"
        @closed="closeConfirmPermissionPresetDeleteModal"
    />

    <SuccessModal
        v-if="showPermissionPresetSuccessModal"
        title="Erfolg"
        :description="showPermissionPresetSuccessModal"
        button="Schließen"
        @closed="closePermissionPresetSuccessModal"
    />

    <ErrorComponent
        v-if="showPermissionPresetErrorModal"
        :titel="$t('Unfortunately an error has occurred')"
        :description="showPermissionPresetErrorModal"
        @closed="closePermissionPresetErrorModal"
    />
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import UserHeader from '@/Pages/Users/UserHeader.vue'
import PermissionPresetModal from '@/Pages/PermissionPresets/Components/PermissionPresetModal.vue'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import SuccessModal from '@/Layouts/Components/General/SuccessModal.vue'
import ErrorComponent from '@/Layouts/Components/ErrorComponent.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import SvgCollection from '@/Layouts/Components/SvgCollection.vue'
import {
    Menu, MenuButton, MenuItem, MenuItems
} from '@headlessui/vue'
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {IconCirclePlus, IconDotsVertical, IconEdit, IconSearch, IconTrash, IconUsersGroup, IconX} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";

/* Props */
const props = defineProps({
    permission_presets: { type: Object, required: true },
    available_permissions: { type: Object, required: true }
})

/* i18n helper */
const { proxy } = getCurrentInstance()
const $t = (k, v) => proxy.$t(k, v)

/* Inertia page (for hints / flash) */
const page = usePage()

/* UI State */
const showPermissionPresetModal = ref(false)
const permissionPresetModalMode = ref(null) // 'create' | 'edit'
const permissionPresetModalResource = ref(null)

const showConfirmDeletePermissionPresetModal = ref(false)
const confirmDeletePermissionPresetIdToDelete = ref(null)
const confirmDeletePermissionPresetModalDescription = ref(null)

/* Search */
const query = ref('')

/* Derived */
const filteredPresets = computed(() => {
    if (!query.value) return props.permission_presets
    const q = query.value.toLowerCase().trim()
    return props.permission_presets.filter(p => (p.name || '').toLowerCase().includes(q))
})

/* Flash modals */
const showPermissionPresetSuccessModal = computed(() => page.props?.flash?.success)
const showPermissionPresetErrorModal = computed(() => page.props?.flash?.error)

/* Actions */
function openPermissionPresetModal(mode, preset = null) {
    permissionPresetModalMode.value = mode
    permissionPresetModalResource.value = preset
    showPermissionPresetModal.value = true
}
function closePermissionPresetModal() {
    permissionPresetModalMode.value = null
    permissionPresetModalResource.value = null
    showPermissionPresetModal.value = false
}

function openConfirmPermissionPresetDeleteModal(preset) {
    confirmDeletePermissionPresetIdToDelete.value = preset.id
    confirmDeletePermissionPresetModalDescription.value = $t(
        'Do you really want to delete the {presetName} rights preset? This cannot be undone.',
        { presetName: preset.name }
    )
    showConfirmDeletePermissionPresetModal.value = true
}
function closeConfirmPermissionPresetDeleteModal(confirmed) {
    if (confirmed) {
        router.delete(
            route('permission-presets.destroy', {
                permission_preset: confirmDeletePermissionPresetIdToDelete.value
            })
        )
    }
    showConfirmDeletePermissionPresetModal.value = false
    confirmDeletePermissionPresetIdToDelete.value = null
    confirmDeletePermissionPresetModalDescription.value = null
}

/* Close flash modals */
function closePermissionPresetSuccessModal() {
    page.props.flash.success = null
}
function closePermissionPresetErrorModal() {
    page.props.flash.error = null
}
</script>
