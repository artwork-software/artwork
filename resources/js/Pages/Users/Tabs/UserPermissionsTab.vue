<template>
    <div class="">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-semibold text-zinc-900 tracking-tight">
                {{ $t('User rights') }}
            </h2>
            <p class="mt-1 text-sm text-zinc-600">
                {{ $t('Manage global roles and granular permissions for this user.') }}
            </p>
        </div>

        <!-- Global roles -->
        <section class="rounded-3xl border border-zinc-200 bg-white shadow-sm">
            <button
                type="button"
                class="flex w-full items-center justify-between px-6 py-4 sm:px-8"
                @click="showGlobalRoles = !showGlobalRoles"
            >
                <div class="flex items-center gap-2">
          <span class="text-xs font-medium uppercase tracking-wide text-zinc-700">
            {{ $t('Global roles') }}
          </span>
                    <span
                        class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-medium text-blue-700 ring-1 ring-blue-200"
                    >
            {{ available_roles?.length || 0 }}
          </span>
                </div>
                <SvgCollection :svg-name="showGlobalRoles ? 'arrowUp' : 'arrowDown'" />
            </button>

            <div v-if="showGlobalRoles" class="border-t border-zinc-100 px-6 py-6 sm:px-8 sm:py-8">
                <div class="space-y-4">
                    <div
                        v-for="(role, index) in available_roles"
                        :key="index"
                        class="flex items-start justify-between rounded-2xl border border-zinc-200 bg-zinc-50/50 px-4 py-3"
                    >
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                v-model="userForm.roles"
                                @change="editUser"
                                :value="role.name"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                            />
                            <span>
                <span class="block font-medium capitalize text-zinc-900">{{ $t(role.translation_key) }}</span>
                <span class="block text-xs text-zinc-500">
                  {{ $t(role.tooltipKey) }}
                </span>
              </span>
                        </label>
                        <div class="pl-2">
                            <ToolTipDefault :left="true" :tooltip-text="$t(role.tooltipKey)" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Permissions -->
        <section class="mt-6">
            <div v-if="showUserPermissions" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div
                    v-for="(group, groupName) in groupedPermissions"
                    :key="groupName"
                    v-show="group.shown"
                    class="rounded-3xl border border-zinc-200 bg-white shadow-sm"
                >
                    <!-- Group Header -->
                    <div class="flex items-center justify-between px-6 py-4 sm:px-7">
                        <button
                            type="button"
                            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-zinc-800"
                            @click="toggleGroup(groupName)"
                        >
                            <span>{{ $t(groupName) }}</span>
                            <SvgCollection :svg-name="isGroupOpen(groupName) ? 'arrowUp' : 'arrowDown'" />
                        </button>

                        <button
                            type="button"
                            class="text-xs font-medium text-blue-700 underline underline-offset-2 hover:text-blue-800"
                            @click="checkOrUncheckAllPermissionsOfGroup(group)"
                        >
                            {{
                                hasAnyOfGroupChecked(group)
                                    ? $t('Deselect all')
                                    : $t('Select all')
                            }}
                        </button>
                    </div>

                    <!-- Permissions list -->
                    <div
                        v-if="isGroupOpen(groupName)"
                        class="space-y-0.5 border-t border-zinc-100 px-6 py-4 sm:px-7 sm:py-6"
                    >
                        <div
                            v-for="(permission, i) in group.permissions"
                            :key="i"
                            class="flex items-start justify-between rounded-2xl px-3 py-2 hover:bg-zinc-50"
                        >
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    v-model="userForm.permissions"
                                    @change="editUser"
                                    :value="permission.name"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                                />
                                <span>
                  <span
                      class="block font-medium capitalize"
                      :class="userForm.permissions.includes(permission.name) ? 'text-zinc-900' : 'text-zinc-600'"
                  >
                    {{ $t(permission.translation_key) }}
                  </span>
                  <span class="block text-xs text-zinc-500">
                    {{ $t(permission.tooltipKey) }}
                  </span>
                </span>
                            </label>
                            <ToolTipDefault :left="true" :tooltip-text="$t(permission.tooltipKey)" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger zone -->
            <div class="mt-10 rounded-3xl border border-red-200 bg-red-50 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-red-800">
                            {{ $t('Permanently delete user') }}
                        </h4>
                        <p class="mt-0.5 text-xs text-red-700">
                            {{ $t('This action cannot be undone.') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="openDeleteUserModal"
                        class="rounded-full border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-100"
                    >
                        {{ $t('Delete user') }}
                    </button>
                </div>
            </div>
        </section>

        <!-- Delete user modal -->
        <BaseModal @closed="closeDeleteUserModal" v-if="deletingUser" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="text-2xl font-bold text-zinc-900 my-2">
                    {{ $t('Delete user') }}
                </div>
                <div class="text-sm text-red-700">
                    {{
                        $t('re you sure you want to delete {last_name}, {first_name} from the system?', {
                            last_name: user_to_edit.last_name,
                            first_name: user_to_edit.first_name
                        })
                    }}
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-full bg-red-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                        @click="deleteUser"
                    >
                        {{ $t('Delete') }}
                    </button>
                    <button
                        type="button"
                        class="text-sm font-medium text-zinc-700 underline underline-offset-2 hover:text-zinc-900"
                        @click="closeDeleteUserModal"
                    >
                        {{ $t('No, not really') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import SvgCollection from '@/Layouts/Components/SvgCollection.vue'
import ToolTipDefault from '@/Components/ToolTips/ToolTipDefault.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'

const props = defineProps({
    user_to_edit: { type: Object, required: true },
    permissions: { type: Array, default: () => [] },      // not directly used but kept for API parity
    all_permissions: { type: Object, required: true },
    available_roles: { type: Array, required: true },
})

/* UI state */
const showGlobalRoles = ref(true)
const showUserPermissions = ref(true)
const deletingUser = ref(false)

/* Form */
const userForm = useForm({
    permissions: props.user_to_edit.permissions || [],
    roles: props.user_to_edit.roles || [],
})

/* Page props (for sage toggle) */
const page = usePage()

/* Group open/close state map */
const groupOpen = ref({}) // { [groupName]: boolean }

/* Build grouped permissions (filtered & sorted) */
const groupedPermissions = computed(() => {
    // Build raw groups
    const grouped = {}
    for (const [groupName, perms] of Object.entries(props.all_permissions)) {
        const filtered = (perms || []).filter((p) => {
            if (p.name === 'can view and delete sage100-api-data') {
                return !!page.props?.sageApiEnabled
            }
            return true
        })
        grouped[groupName] = {
            shown: filtered.length > 0,
            permissions: filtered,
        }
        // initialize open state first time
        if (!(groupName in groupOpen.value)) {
            groupOpen.value[groupName] = true
        }
    }

    // Sort groups by permission count (desc)
    return Object.fromEntries(
        Object.entries(grouped).sort(([, a], [, b]) => (b.permissions?.length || 0) - (a.permissions?.length || 0))
    )
})

/* Helpers */
const isGroupOpen = (name) => !!groupOpen.value[name]
const toggleGroup = (name) => (groupOpen.value[name] = !groupOpen.value[name])

const hasAnyOfGroupChecked = (group) =>
    group.permissions.some((p) => userForm.permissions.includes(p.name))

/* Actions */
const checkOrUncheckAllPermissionsOfGroup = (group) => {
    if (hasAnyOfGroupChecked(group)) {
        // remove all
        userForm.permissions = userForm.permissions.filter(
            (permName) => !group.permissions.some((p) => p.name === permName)
        )
    } else {
        // add all (unique)
        const toAdd = group.permissions.map((p) => p.name)
        userForm.permissions = Array.from(new Set([...userForm.permissions, ...toAdd]))
    }
    editUser()
}

const editUser = () => {
    userForm.patch(route('user.update.permissions-and-roles', { user: props.user_to_edit.id }), {
        preserveScroll: true,
    })
}

const openDeleteUserModal = () => (deletingUser.value = true)
const closeDeleteUserModal = () => (deletingUser.value = false)

const deleteUser = () => {
    router.delete(`/users/${props.user_to_edit.id}`)
    closeDeleteUserModal()
}
</script>
