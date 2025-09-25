<template>
    <UserHeader
        title="All permission presets"
        description="Edit and create permission presets"
    >
        <!-- Header Actions -->
        <template #tabBar>
            <div class="flex items-center justify-end gap-3">
                <!-- Search -->
                <div class="relative">
                    <input
                        v-model="query"
                        :placeholder="$t('Search presets')"
                        type="text"
                        class="h-10 w-64 rounded-xl border border-zinc-300 bg-white px-10 text-sm text-zinc-900 placeholder:text-zinc-400 outline-none ring-0 transition focus:border-zinc-400 focus:bg-zinc-50"
                    />
                    <SearchIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" />
                    <button
                        v-if="query"
                        @click="query = ''"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700"
                        aria-label="Clear"
                    >
                        <XIcon class="h-4 w-4" />
                    </button>
                </div>

                <!-- Create -->
                <BaseCardButton
                    text="Create new permission presets"
                    @click="openPermissionPresetModal('create')"
                >
                    <component :is="IconPlus" class="size-6" />
                </BaseCardButton>
            </div>
        </template>

        <!-- List / Empty State -->
        <template #default>
            <div v-if="filteredPresets.length === 0" class="rounded-2xl border border-zinc-200 bg-white p-10 text-center">
                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full ring-1 ring-inset ring-zinc-200">
                    <DotsVerticalIcon class="h-6 w-6 text-zinc-400" />
                </div>
                <h3 class="text-base font-semibold text-zinc-900">
                    {{ $t('No permission presets found') }}
                </h3>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t('Create your first preset to reuse permission sets quickly.') }}
                </p>
                <div class="mt-6">
                    <BaseCardButton
                        text="Create preset"
                        @click="openPermissionPresetModal('create')"
                    />
                </div>
            </div>

            <ul v-else role="list" class="grid gap-3 lg:gap-4">
                <li
                    v-for="(preset, idx) in filteredPresets"
                    :key="preset.id"
                    class="group flex items-center justify-between rounded-2xl bg-white p-4 ring-1 ring-zinc-200 transition hover:bg-zinc-50 hover:shadow-sm"
                >
                    <!-- Clickable name -->
                    <button
                        type="button"
                        @click="openPermissionPresetModal('edit', preset)"
                        class="text-left text-zinc-900 transition hover:text-zinc-950"
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
import BaseCardButton from '@/Artwork/Buttons/BaseCardButton.vue'
import SvgCollection from '@/Layouts/Components/SvgCollection.vue'
import {
    Menu, MenuButton, MenuItem, MenuItems
} from '@headlessui/vue'
import {
    DotsVerticalIcon, PencilAltIcon, TrashIcon, SearchIcon, XIcon
} from '@heroicons/vue/outline'
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {IconEdit, IconPlus, IconTrash} from "@tabler/icons-vue";

/* Props */
const props = defineProps({
    permission_presets: { type: Array, required: true },
    available_permissions: { type: Array, required: true }
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
