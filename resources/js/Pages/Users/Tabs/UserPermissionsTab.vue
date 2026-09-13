<template>
    <div>
        <!-- Kopf -->
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-text">{{ $t('User rights') }}</h2>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Permissions are grouped by module in the order of the main menu. Click a permission to see what it unlocks.') }}
                </p>
                <!-- Speicherstatus + letzte Änderung -->
                <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    <span v-if="saveState === 'saving'" class="inline-flex items-center gap-1.5 text-text-muted">
                        <PropertyIcon name="IconLoader2" class="size-3.5 animate-spin" /> {{ $t('Saving…') }}
                    </span>
                    <span v-else-if="saveState === 'saved'" class="inline-flex items-center gap-1.5 text-success">
                        <PropertyIcon name="IconCheck" class="size-3.5" /> {{ $t('Saved at {time}', { time: savedAt }) }}
                    </span>
                    <span v-else-if="saveState === 'error'" class="inline-flex items-center gap-1.5 text-danger">
                        <PropertyIcon name="IconAlertTriangle" class="size-3.5" /> {{ $t('Saving failed – please reload the page and try again.') }}
                    </span>
                    <span v-if="lastChange" class="text-text-subtle">
                        {{ $t('Last changed by {name} on {date}', { name: lastChange.causer?.name ?? $t('System'), date: formatDate(lastChange.at) }) }}
                        <button type="button" class="ml-1 underline underline-offset-2 hover:text-text" @click="historyOpen = !historyOpen">
                            {{ historyOpen ? $t('Hide history') : $t('Show history') }}
                        </button>
                    </span>
                </p>
            </div>

            <!-- artwork-Admin -->
            <div class="rounded-2xl border px-4 py-3" :class="isAdmin ? 'border-accent-200 bg-accent-50' : 'border-border-subtle bg-white'">
                <label class="flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        class="mt-1 h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600"
                        :checked="isAdmin"
                        @change="onAdminToggle($event)"
                    />
                    <span>
                        <span class="block text-sm font-semibold text-text">{{ $t('artwork admin') }}</span>
                        <span class="block text-xs text-text-muted">
                            {{ $t('Has every permission in every module, including disabled modules and all settings. Use sparingly.') }}
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <!-- Änderungsverlauf -->
        <div v-if="historyOpen" class="mb-6 rounded-2xl border border-border-subtle bg-white">
            <div class="flex items-center justify-between px-5 py-3">
                <h3 class="text-sm font-semibold text-text">{{ $t('Change history') }}</h3>
                <span class="text-xs text-text-subtle">{{ $t('{count} entries', { count: permission_history.length }) }}</span>
            </div>
            <ul class="divide-y divide-border-subtle border-t border-border-subtle">
                <li v-for="entry in permission_history" :key="entry.id" class="grid gap-1 px-5 py-3 text-sm sm:grid-cols-[180px_1fr]">
                    <div class="text-xs text-text-muted">
                        <span class="block text-text">{{ entry.causer?.name ?? $t('System') }}</span>
                        <span>{{ formatDate(entry.at) }}</span>
                        <span v-if="entry.source" class="block text-text-subtle">{{ $t(sourceLabel(entry.source)) }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="role in entry.roles_added" :key="'ra' + role" class="rounded-md bg-accent-50 px-2 py-0.5 text-xs font-medium text-accent-700">+ {{ $t(role) }}</span>
                        <span v-for="role in entry.roles_removed" :key="'rr' + role" class="rounded-md bg-danger-surface px-2 py-0.5 text-xs font-medium text-danger">− {{ $t(role) }}</span>
                        <span v-for="name in entry.added" :key="'a' + name" class="rounded-md bg-success-surface px-2 py-0.5 text-xs text-success">+ {{ $t(titleOf(name)) }}</span>
                        <span v-for="name in entry.removed" :key="'r' + name" class="rounded-md bg-danger-surface px-2 py-0.5 text-xs text-danger">− {{ $t(titleOf(name)) }}</span>
                    </div>
                </li>
                <li v-if="!permission_history.length" class="px-5 py-4 text-sm text-text-subtle">{{ $t('No changes recorded yet.') }}</li>
            </ul>
        </div>

        <div v-if="isAdmin" class="mb-6 rounded-2xl border border-dashed border-border px-4 py-3 text-sm text-text-muted">
            {{ $t('This person is an artwork admin. Individual permissions below are stored but not needed – they take effect again if the admin role is removed.') }}
        </div>

        <PermissionCatalogEditor
            v-model="permissions"
            :catalog="catalog"
            :presets="permission_presets"
            :people="colleagues"
            :module-settings-url="moduleSettingsUrl"
            @change="savePermissions"
        />

        <!-- Gefahrenzone -->
        <div class="mt-10 rounded-3xl border border-danger-border bg-danger-surface p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-danger">{{ $t('Permanently delete user') }}</h4>
                    <p class="mt-0.5 text-xs text-danger">{{ $t('This action cannot be undone.') }}</p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-danger-border bg-white px-4 py-2 text-sm font-medium text-danger transition hover:bg-danger-surface"
                    @click="deletingUser = true"
                >
                    {{ $t('Delete user') }}
                </button>
            </div>
        </div>

        <!-- Rückfrage: Admin-Rolle vergeben -->
        <ArtworkBaseModal v-if="confirmAdmin" title="Grant artwork admin role?" modal-size="sm:max-w-lg" @close="confirmAdmin = false">
            <p class="text-sm text-text-muted">
                {{ $t('{name} will be able to do everything in artwork: every module, every setting, every person – including granting rights to others. Individual permissions no longer matter.', { name: fullName }) }}
            </p>
            <template #footer>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <BaseUIButton variant="ghost" hide-icon label="Cancel" @click="confirmAdmin = false" />
                    <BaseUIButton variant="primary" hide-icon label="Grant admin role" @click="isAdmin = true; confirmAdmin = false; persist('admin')" />
                </div>
            </template>
        </ArtworkBaseModal>

        <BaseModal v-if="deletingUser" modal-image="/Svgs/Overlays/illu_warning.svg" @closed="deletingUser = false">
            <div class="mx-4">
                <div class="my-2 text-2xl font-bold text-text">{{ $t('Delete user') }}</div>
                <div class="text-sm text-danger">
                    {{ $t('Are you sure you want to delete {last_name}, {first_name} from the system?', { last_name: user_to_edit.last_name, first_name: user_to_edit.first_name }) }}
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-full bg-danger px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-danger focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2"
                        @click="deleteUser"
                    >
                        {{ $t('Delete') }}
                    </button>
                    <button type="button" class="text-sm font-medium text-text-muted underline underline-offset-2 hover:text-text" @click="deletingUser = false">
                        {{ $t('No, not really') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import PermissionCatalogEditor from '@/Artwork/Permissions/PermissionCatalogEditor.vue'

const props = defineProps({
    user_to_edit: { type: Object, required: true },
    catalog: { type: Object, required: true },
    permission_presets: { type: Array, default: () => [] },
    permission_history: { type: Array, default: () => [] },
    colleagues: { type: Array, default: () => [] },
    available_roles: { type: Array, default: () => [] },
})

const permissions = ref([...(props.user_to_edit.permissions || [])])
const isAdmin = ref((props.user_to_edit.roles || []).includes('artwork admin'))
const deletingUser = ref(false)
const confirmAdmin = ref(false)
const historyOpen = ref(false)
const saveState = ref('idle')
const savedAt = ref('')

const fullName = computed(() => `${props.user_to_edit.first_name ?? ''} ${props.user_to_edit.last_name ?? ''}`.trim())
const lastChange = computed(() => props.permission_history[0] ?? null)
const moduleSettingsUrl = computed(() => (typeof route === 'function' ? route('tool.module-settings.index') : null))

const titles = computed(() => {
    const map = {}
    for (const module of props.catalog?.modules ?? []) {
        for (const list of ['tiers', 'extras', 'advanced']) {
            for (const def of module[list] ?? []) map[def.name] = def.title
        }
    }
    return map
})
const titleOf = (name) => titles.value[name] ?? name
const sourceLabel = (source) => ({ admin: 'Admin role', preset: 'Role preset', person: 'Same as person' }[source] ?? source)

const formatDate = (iso) => {
    if (!iso) return ''
    const date = new Date(iso)
    const pad = (n) => String(n).padStart(2, '0')
    return `${pad(date.getDate())}.${pad(date.getMonth() + 1)}.${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

watch(() => props.user_to_edit, (user) => {
    permissions.value = [...(user.permissions || [])]
    isAdmin.value = (user.roles || []).includes('artwork admin')
})

function persist(source = null) {
    saveState.value = 'saving'
    router.patch(
        route('user.update.permissions-and-roles', { user: props.user_to_edit.id }),
        { permissions: permissions.value, roles: isAdmin.value ? ['artwork admin'] : [], source },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['user_to_edit', 'permission_history', 'catalog'],
            onSuccess: () => {
                const now = new Date()
                savedAt.value = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
                saveState.value = 'saved'
            },
            onError: () => { saveState.value = 'error' },
        }
    )
}
const savePermissions = () => persist()

function onAdminToggle(event) {
    const checked = event.target.checked
    if (checked) {
        // Vergabe braucht eine Rückfrage; die Checkbox bleibt bis zur Bestätigung aus
        event.target.checked = false
        confirmAdmin.value = true
        return
    }
    isAdmin.value = false
    persist('admin')
}

function deleteUser() {
    router.delete(`/users/${props.user_to_edit.id}`)
    deletingUser.value = false
}
</script>
