<template>
    <ArtworkBaseModal
        v-if="show"
        modal-size="sm:max-w-4xl"
        :title="$t('Assign project team')"
        :description="$t('Type the name of the users you want to add to the team. The users receive read access to this project. Only the project manager can grant further rights.')"
        @close="closeModal"
    >
        <div class="">
            <!-- Suche -->
            <div class="relative">
                <BaseInput
                    id="departmentSearch"
                    class="w-full"
                    :label="$t('Name')"
                    v-model="department_and_user_query"
                    type="text"
                />

                <!-- Autocomplete Dropdown -->
                <transition
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showSearchDropdown"
                        class="absolute z-20 mt-1 w-full max-h-60 overflow-auto rounded-xl border border-zinc-200 bg-white shadow-xl ring-1 ring-black/5 focus:outline-none"
                    >
                        <!-- Users -->
                        <div
                            v-for="user in department_and_user_search_results.users"
                            :key="`user-${user.id}`"
                            class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-zinc-50"
                            @click="addUserToProjectTeamArray(user)"
                        >
                            <img
                                :src="user.profile_photo_url"
                                :alt="user.name"
                                class="rounded-full h-8 w-8 object-cover"
                            />
                            <div class="flex flex-col text-sm">
                                <span class="font-medium text-zinc-900 truncate">
                                    {{ user.first_name }} {{ user.last_name }}
                                </span>
                            </div>
                        </div>

                        <!-- Departments -->
                        <div
                            v-for="department in department_and_user_search_results.departments"
                            :key="`dep-${department.id}`"
                            class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-zinc-50"
                            @click="addDepartmentToProjectTeamArray(department)"
                        >
                            <TeamIconCollection
                                :iconName="department.svg_name"
                                :alt="department.name"
                                class="rounded-full h-8 w-8 object-cover"
                            />
                            <div class="text-sm font-medium text-zinc-900">
                                {{ department.name }}
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Zuweisungen -->
            <div class="mt-8 space-y-6">
                <!-- Users Section -->
                <section
                    v-if="users.length > 0"
                    class="rounded-2xl border border-zinc-200 bg-white shadow-sm divide-y divide-zinc-100"
                >
                    <div
                        v-for="user in users"
                        :key="`assigned-user-${user.id}`"
                        class="flex items-center gap-3 px-4 py-3"
                    >
                        <img
                            class="h-9 w-9 rounded-full object-cover flex-shrink-0"
                            :src="user.profile_photo_url"
                            alt=""
                        />

                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-zinc-900 truncate">
                                {{ user.first_name }} {{ user.last_name }}
                            </div>
                        </div>

                        <ProjectTeamPermissionsDropdown
                            v-if="checkUserAuth(user)"
                            :user="user"
                            :project-roles="projectRoles"
                            :can-manage-project-roles="hasAdminRole()"
                            @update-permission="updateUserPermission(user, $event)"
                            @toggle-role="addRoleToUser(user, $event)"
                        />

                        <button
                            type="button"
                            @click="deleteUserFromProjectTeam(user)"
                            class="flex-shrink-0 rounded-full p-1 text-zinc-400 transition hover:bg-zinc-100 hover:text-error"
                        >
                            <span class="sr-only">{{ $t('Remove user from team') }}</span>
                            <XCircleIcon class="h-5 w-5" />
                        </button>
                    </div>
                </section>

                <!-- Departments Section -->
                <section
                    v-if="departments.length > 0"
                    class="rounded-2xl border border-zinc-200 bg-white shadow-sm divide-y divide-zinc-100"
                >
                    <div
                        v-for="department in departments"
                        :key="`assigned-dep-${department.id}`"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div class="flex items-center gap-4 min-w-0">
                            <TeamIconCollection
                                :iconName="department.svg_name"
                                :alt="department.name"
                                class="h-9 w-9 rounded-full object-cover flex-shrink-0"
                            />
                            <div class="min-w-0 font-semibold text-zinc-900 truncate">
                                {{ department.name }}
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="deleteDepartmentFromProjectTeam(department)"
                            class="flex items-center text-zinc-400 hover:text-error transition"
                        >
                            <span class="sr-only">{{ $t('Remove team from the project') }}</span>
                            <XCircleIcon class="h-5 w-5" />
                        </button>
                    </div>
                </section>
            </div>

            <!-- Save Button -->
            <div class="mt-8 flex justify-end">
                <BaseUIButton
                    :label="$t('Save')"
                    is-add-button
                    :disabled="form.processing"
                    @click="editProjectTeam"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'

// Alte Mixins weiterhin nutzen (Vue 3 erlaubt defineOptions für Options-API features)
import Permissions from '@/Mixins/Permissions.vue'
import IconLib from '@/Mixins/IconLib.vue'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ProjectTeamPermissionsDropdown from '@/Pages/Projects/Components/ProjectTeamPermissionsDropdown.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import { XCircleIcon } from '@heroicons/vue/solid'
import {is} from "laravel-permission-to-vuejs";

defineOptions({
    name: 'ProjectEditTeamModal',
    mixins: [Permissions, IconLib],
})

// Props
const props = defineProps({
    show: { type: Boolean, required: true },
    assignedUsers: { type: Array, required: true },
    assignedDepartments: { type: Array, required: true },
    userIsProjectManager: { type: Boolean, required: true },
    userIsProjectCreator: { type: Boolean, default: false },
    projectId: { type: [Number, String], required: true },
    projectRoles: { type: Array, required: true },
})

// Emits
const emit = defineEmits(['closed'])

// State
const department_and_user_query = ref('')
const department_and_user_search_results = reactive({
    users: [],
    departments: [],
})

// useForm für PATCH
const form = useForm({
    assigned_user_ids: {},
    assigned_departments: [],
})

const cloneAssignedUsers = (assignedUsers) => {
    return (assignedUsers || []).map(user => ({
        ...user,
        pivot_roles: [...(user.pivot_roles ?? [])],
    }))
}

// Lokale Kopien (damit wir nicht direkt Props mutieren)
const users = ref(cloneAssignedUsers(props.assignedUsers))

const departments = ref(props.assignedDepartments.map(d => ({ ...d })))

// Halte lokale Kopien mit Props synchron, z. B. wenn Daten asynchron geladen werden oder beim Öffnen des Modals
watch(() => props.assignedUsers, (newUsers) => {
    users.value = cloneAssignedUsers(newUsers)
}, { deep: true })

watch(() => props.assignedDepartments, (newDeps) => {
    departments.value = (newDeps || []).map(d => ({ ...d }))
}, { deep: true })

// Beim Öffnen des Modals auf den neuesten Stand bringen
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        users.value = cloneAssignedUsers(props.assignedUsers)
        departments.value = (props.assignedDepartments || []).map(d => ({ ...d }))
    }
})

// --- computed ---
const page = usePage()
const authUserId = computed(() => page.props.auth.user.id)

const showSearchDropdown = computed(() => {
    return (
        department_and_user_query.value.length > 0 &&
        (
            (department_and_user_search_results.users && department_and_user_search_results.users.length > 0) ||
            (department_and_user_search_results.departments && department_and_user_search_results.departments.length > 0)
        )
    )
})

// --- methods (Composition API style as const fns) ---

const closeModal = (bool) => {
    emit('closed', bool)
}

const addDepartmentToProjectTeamArray = (departmentToAdd) => {
    // check duplicate
    if (departments.value.some(dep => dep.id === departmentToAdd.id)) {
        department_and_user_query.value = ''
        return
    }

    departments.value.push(departmentToAdd)
    department_and_user_query.value = ''
}

const deleteDepartmentFromProjectTeam = (department) => {
    departments.value = departments.value.filter(d => d.id !== department.id)
}

const addUserToProjectTeamArray = (userToAdd) => {
    if (users.value.some(u => u.id === userToAdd.id)) {
        department_and_user_query.value = ''
        return
    }

    users.value.push({
        ...userToAdd,
        // Neue Teammitglieder starten mit ihren im Arbeitsprofil hinterlegten Standard-Projektrollen
        pivot_roles: [...(userToAdd.default_project_role_ids ?? userToAdd.pivot_roles ?? [])],
    })

    department_and_user_query.value = ''
}

const deleteUserFromProjectTeam = (user) => {
    users.value = users.value.filter(u => u.id !== user.id)
}

const updateUserPermission = (user, {permission, value}) => {
    user[permission] = value
}

const editProjectTeam = () => {
    form.assigned_user_ids = {}
    users.value.forEach(user => {
        form.assigned_user_ids[user.id] = {
            access_budget: user.pivot_access_budget,
            is_manager: user.pivot_is_manager,
            can_write: user.pivot_can_write,
            delete_permission: user.pivot_delete_permission,
            roles: user.pivot_roles,
        }
    })

    form.assigned_departments = [...departments.value]

    form.patch(route('projects.update_team', { project: props.projectId }))
    closeModal(true)
}

const checkUserAuth = (user) => {
    if (props.userIsProjectManager) return true
    if (props.userIsProjectCreator) return true
    if (authUserId.value === user.id && user.project_management) return true
    if(is('artwork admin')) return true
    // hasAdminRole kommt vom Mixin Permissions
    return typeof (/* @ts-ignore */ hasAdminRole) === 'function'
        ? /* @ts-ignore */ hasAdminRole()
        : false
}

const addRoleToUser = (user, role) => {
    if (!user.pivot_roles) {
        user.pivot_roles = []
    }

    const idx = user.pivot_roles.indexOf(role.id)
    if (idx !== -1) {
        user.pivot_roles.splice(idx, 1)
        return
    }

    user.pivot_roles.push(role.id)
}

// --- watcher ---

watch(
    department_and_user_query,
    async (val) => {
        if (!val || val.length === 0) {
            department_and_user_search_results.users = []
            department_and_user_search_results.departments = []
            return
        }

        try {
            const response = await axios.get(route('users_departments.search'), {
                params: { query: val },
            })
            department_and_user_search_results.users = response.data.users || []
            department_and_user_search_results.departments = response.data.departments || []
        } catch (e) {
            // fallback: leeren, aber nicht crashen
            department_and_user_search_results.users = []
            department_and_user_search_results.departments = []
        }
    },
    { deep: false }
)
</script>
