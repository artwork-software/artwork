<template>
    <ArtworkBaseModal
        :title="propertyGroup ? $t('Edit property group') : $t('New property group')"
        :description="$t('Organize properties into groups.')"
        @close="$emit('close')"
    >
        <div class="space-y-4 mt-4">
            <div class="flex items-center gap-x-3">
                <IconSelector
                    :current-icon="form.icon"
                    @update:modelValue="(icon) => form.icon = icon"
                />
                <div class="w-full">
                    <BaseInput id="group_name" v-model="form.name" :label="$t('Name')" :error="form.errors.name" />
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.is_confidential" class="rounded border-gray-300 text-indigo-600" />
                <span class="text-sm text-gray-700">{{ $t('Confidential') }}</span>
            </label>

            <!-- Permissions section when confidential -->
            <div v-if="form.is_confidential" class="rounded-xl border border-gray-200 bg-white p-4">
                <div class="mb-3 text-sm font-bold text-gray-900">
                    {{ $t('Manage access') }}
                </div>

                <div class="relative">
                    <BaseInput
                        :label="$t('Search for teams and/or users')"
                        v-model="searchQuery"
                        id="searchUsersAndDepartments"
                        @input="searchUsersAndDepartments"
                    />

                    <div
                        v-if="hasSearchResults"
                        class="absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded-lg border border-gray-200 bg-white text-sm shadow-lg"
                    >
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="(user, idx) in searchResults.users"
                                :key="'u_' + idx"
                                class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                @click="addUser(user)"
                            >
                                <img :src="user.profile_photo_url" :alt="user.first_name" class="h-8 w-8 rounded-full object-cover" />
                                <span class="truncate">{{ user.first_name }} {{ user.last_name }}</span>
                            </div>
                            <div
                                v-for="(department, idx) in searchResults.departments"
                                :key="'d_' + idx"
                                class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                @click="addDepartment(department)"
                            >
                                <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-8 w-8" />
                                <span class="truncate">{{ department.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned users/departments list -->
                <div v-if="permissions.length > 0" class="mt-4 space-y-2">
                    <div
                        v-for="(perm, idx) in permissions"
                        :key="perm.permissionable_type + '_' + perm.permissionable_id"
                        class="flex items-center justify-between rounded-lg border-b border-gray-100 pb-2"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <img
                                v-if="perm._display.type === 'user'"
                                class="h-10 w-10 rounded-full object-cover flex-shrink-0"
                                :src="perm._display.photo"
                                alt=""
                            />
                            <TeamIconCollection
                                v-else
                                :iconName="perm._display.svg_name"
                                :alt="perm._display.name"
                                class="h-10 w-10 flex-shrink-0"
                            />
                            <span class="text-sm font-medium text-gray-800 truncate">{{ perm._display.name }}</span>
                        </div>
                        <div class="flex items-center gap-4 flex-shrink-0">
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" v-model="permissions[idx].can_view" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-xs text-gray-600">{{ $t('Can view') }}</span>
                            </label>
                            <label class="flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" v-model="permissions[idx].can_edit" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-xs text-gray-600">{{ $t('Can edit') }}</span>
                            </label>
                            <button type="button" @click="removePermission(idx)" class="text-gray-400 hover:text-red-500">
                                <IconX class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button class="ui-button-cancel" @click="$emit('close')">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" @click="submit" :disabled="form.processing">
                    {{ propertyGroup ? $t('Save') : $t('Create') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import IconSelector from '@/Components/Icon/IconSelector.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import { IconX } from '@tabler/icons-vue'

const props = defineProps({
    propertyGroup: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const USER_TYPE = 'Artwork\\Modules\\User\\Models\\User'
const DEPARTMENT_TYPE = 'Artwork\\Modules\\Department\\Models\\Department'

const form = useForm({
    name: props.propertyGroup?.name ?? '',
    icon: props.propertyGroup?.icon ?? '',
    is_confidential: props.propertyGroup?.is_confidential ?? false,
})

// Initialize permissions from existing data
const permissions = ref(buildInitialPermissions())

function buildInitialPermissions() {
    if (!props.propertyGroup?.permissions) return []
    return props.propertyGroup.permissions.map((p) => ({
        permissionable_type: p.permissionable_type,
        permissionable_id: p.permissionable_id,
        can_view: p.can_view ?? false,
        can_edit: p.can_edit ?? false,
        _display: buildDisplayFromPermission(p),
    }))
}

function buildDisplayFromPermission(p) {
    const entity = p.permissionable
    if (p.permissionable_type === USER_TYPE) {
        return {
            type: 'user',
            name: entity ? `${entity.first_name} ${entity.last_name}` : `User #${p.permissionable_id}`,
            photo: entity?.profile_photo_url ?? '',
        }
    }
    return {
        type: 'department',
        name: entity?.name ?? `Department #${p.permissionable_id}`,
        svg_name: entity?.svg_name ?? '',
    }
}

// Search
const searchQuery = ref('')
const searchResults = reactive({ users: [], departments: [] })

const hasSearchResults = computed(() => searchResults.users.length > 0 || searchResults.departments.length > 0)

async function searchUsersAndDepartments() {
    const q = searchQuery.value?.trim()
    if (!q) {
        searchResults.users = []
        searchResults.departments = []
        return
    }
    try {
        const { data } = await axios.get(route('users_departments.search'), { params: { query: q } })
        searchResults.users = data.users ?? []
        searchResults.departments = data.departments ?? []
    } catch {
        searchResults.users = []
        searchResults.departments = []
    }
}

function addUser(user) {
    if (permissions.value.some((p) => p.permissionable_type === USER_TYPE && p.permissionable_id === user.id)) return
    permissions.value.push({
        permissionable_type: USER_TYPE,
        permissionable_id: user.id,
        can_view: true,
        can_edit: false,
        _display: {
            type: 'user',
            name: `${user.first_name} ${user.last_name}`,
            photo: user.profile_photo_url,
        },
    })
    resetSearch()
}

function addDepartment(department) {
    if (permissions.value.some((p) => p.permissionable_type === DEPARTMENT_TYPE && p.permissionable_id === department.id)) return
    permissions.value.push({
        permissionable_type: DEPARTMENT_TYPE,
        permissionable_id: department.id,
        can_view: true,
        can_edit: false,
        _display: {
            type: 'department',
            name: department.name,
            svg_name: department.svg_name,
        },
    })
    resetSearch()
}

function removePermission(idx) {
    permissions.value.splice(idx, 1)
}

function resetSearch() {
    searchQuery.value = ''
    searchResults.users = []
    searchResults.departments = []
}

const submit = () => {
    const permissionsData = form.is_confidential
        ? permissions.value.map(({ _display, ...rest }) => rest)
        : []

    const payload = {
        ...form.data(),
        permissions: permissionsData,
    }

    if (props.propertyGroup) {
        form.transform(() => payload).patch(route('crm.groups.update', props.propertyGroup.id), {
            onSuccess: () => emit('close'),
        })
    } else {
        form.transform(() => payload).post(route('crm.groups.store'), {
            onSuccess: () => emit('close'),
        })
    }
}
</script>
