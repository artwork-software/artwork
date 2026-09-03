<template>
    <ArtworkBaseModal
        v-if="show"
        modal-size="sm:max-w-4xl"
        :title="$t('Assign project team')"
        :description="$t('Type the name of the users you want to add to the team. The users receive read access to this project. Only the project manager can grant further rights.')"
        @close="closeModal"
    >
        <div class="">
            <SettingsGuideBanner
                variant="static"
                icon="IconShieldLock"
                class="mb-4"
                title="Rights in the project team apply to this project only"
                :paragraphs="[
                    'Write, project management, budget and delete here apply only to this project. House-wide permissions for all projects are managed under user rights.'
                ]"
            />
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
                        class="absolute z-20 mt-1 w-full max-h-60 overflow-auto rounded-xl border border-border-subtle bg-white shadow-xl ring-1 ring-black/5 focus:outline-none"
                    >
                        <!-- Users -->
                        <div
                            v-for="user in department_and_user_search_results.users"
                            :key="`user-${user.id}`"
                            class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-surface-sunken"
                            @click="addUserToProjectTeamArray(user)"
                        >
                            <img
                                :src="user.profile_photo_url"
                                :alt="user.name"
                                class="rounded-full h-8 w-8 object-cover"
                            />
                            <div class="flex flex-col text-sm">
                                <span class="font-medium text-text truncate">
                                    {{ user.first_name }} {{ user.last_name }}
                                </span>
                            </div>
                        </div>

                        <!-- Departments -->
                        <div
                            v-for="department in department_and_user_search_results.departments"
                            :key="`dep-${department.id}`"
                            class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-surface-sunken"
                            @click="addDepartmentToProjectTeamArray(department)"
                        >
                            <TeamIconCollection
                                :iconName="department.svg_name"
                                :alt="department.name"
                                class="rounded-full h-8 w-8 object-cover"
                            />
                            <div class="text-sm font-medium text-text">
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
                    class="rounded-2xl border border-border-subtle bg-white shadow-sm divide-y divide-border-subtle"
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
                            <div class="font-semibold text-text truncate">
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
                            class="flex-shrink-0 rounded-full p-1 text-text-subtle transition hover:bg-surface-sunken hover:text-danger"
                        >
                            <span class="sr-only">{{ $t('Remove user from team') }}</span>
                            <IconCircleX class="h-5 w-5" />
                        </button>
                    </div>
                </section>

                <!-- Departments Section -->
                <section
                    v-if="departments.length > 0"
                    class="rounded-2xl border border-border-subtle bg-white shadow-sm divide-y divide-border-subtle"
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
                            <div class="min-w-0 font-semibold text-text truncate">
                                {{ department.name }}
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="deleteDepartmentFromProjectTeam(department)"
                            class="flex items-center text-text-subtle hover:text-danger transition"
                        >
                            <span class="sr-only">{{ $t('Remove team from the project') }}</span>
                            <IconCircleX class="h-5 w-5" />
                        </button>
                    </div>
                </section>

                <!-- CRM-Kontakte Section -->
                <section v-if="crmContactsEnabled">
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle mb-2">
                        {{ $t('CRM contacts') }}
                    </div>
                    <div class="relative">
                        <BaseInput
                            id="crmContactSearch"
                            class="w-full"
                            :label="$t('Search CRM contacts')"
                            v-model="crm_contact_query"
                            type="text"
                        />
                        <transition
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div
                                v-if="showCrmSearchDropdown"
                                class="absolute z-20 mt-1 w-full max-h-60 overflow-auto rounded-xl border border-border-subtle bg-white shadow-xl ring-1 ring-black/5 focus:outline-none"
                            >
                                <div
                                    v-for="contact in crm_contact_search_results"
                                    :key="`crm-search-${contact.id}`"
                                    class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-surface-sunken"
                                    @click="addCrmContactToProjectTeamArray(contact)"
                                >
                                    <img
                                        :src="contact.profile_photo_url"
                                        :alt="contact.display_name"
                                        class="rounded-full h-8 w-8 object-cover"
                                    />
                                    <div class="flex flex-col text-sm min-w-0">
                                        <span class="font-medium text-text truncate">
                                            {{ contact.display_name }}
                                        </span>
                                        <span v-if="contact.contact_type" class="text-xs text-text-subtle truncate">
                                            {{ contact.contact_type.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <div
                        v-if="crmContacts.length > 0"
                        class="mt-4 rounded-2xl border border-border-subtle bg-white shadow-sm divide-y divide-border-subtle"
                    >
                        <div
                            v-for="contact in crmContacts"
                            :key="`assigned-crm-${contact.id}`"
                            class="flex items-center gap-3 px-4 py-3"
                        >
                            <img
                                class="h-9 w-9 rounded-full object-cover flex-shrink-0"
                                :src="contact.profile_photo_url"
                                alt=""
                            />

                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-text truncate">
                                    {{ contact.display_name }}
                                </div>
                                <div v-if="contact.contact_type" class="text-xs text-text-subtle truncate">
                                    {{ contact.contact_type.name }}
                                </div>
                            </div>

                            <ProjectTeamPermissionsDropdown
                                :user="contact"
                                :project-roles="projectRoles"
                                :can-manage-project-roles="hasAdminRole()"
                                roles-only
                                @toggle-role="addRoleToUser(contact, $event)"
                            />

                            <button
                                type="button"
                                @click="deleteCrmContactFromProjectTeam(contact)"
                                class="flex-shrink-0 rounded-full p-1 text-text-subtle transition hover:bg-surface-sunken hover:text-danger"
                            >
                                <span class="sr-only">{{ $t('Remove CRM contact from team') }}</span>
                                <IconCircleX class="h-5 w-5" />
                            </button>
                        </div>
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
import {IconCircleX} from "@tabler/icons-vue";
import { ref, reactive, watch, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'

// Alte Mixins weiterhin nutzen (Vue 3 erlaubt defineOptions für Options-API features)
import Permissions from '@/Mixins/Permissions.vue'
import IconLib from '@/Mixins/IconLib.vue'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ProjectTeamPermissionsDropdown from '@/Pages/Projects/Components/ProjectTeamPermissionsDropdown.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
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
    assignedCrmContacts: { type: Array, default: () => [] },
    crmContactsEnabled: { type: Boolean, default: false },
})

// Emits
const emit = defineEmits(['closed'])

// State
const department_and_user_query = ref('')
const department_and_user_search_results = reactive({
    users: [],
    departments: [],
})
const crm_contact_query = ref('')
const crm_contact_search_results = ref([])

// useForm für PATCH
const form = useForm({
    assigned_user_ids: {},
    assigned_departments: [],
    assigned_crm_contact_ids: {},
})

const cloneAssignedUsers = (assignedUsers) => {
    return (assignedUsers || []).map(user => ({
        ...user,
        pivot_roles: [...(user.pivot_roles ?? [])],
    }))
}

const cloneAssignedCrmContacts = (assignedCrmContacts) => {
    return (assignedCrmContacts || []).map(contact => ({
        ...contact,
        pivot_roles: [...(contact.pivot_roles ?? [])],
    }))
}

// Lokale Kopien (damit wir nicht direkt Props mutieren)
const users = ref(cloneAssignedUsers(props.assignedUsers))

const departments = ref(props.assignedDepartments.map(d => ({ ...d })))

const crmContacts = ref(cloneAssignedCrmContacts(props.assignedCrmContacts))

// Halte lokale Kopien mit Props synchron, z. B. wenn Daten asynchron geladen werden oder beim Öffnen des Modals
watch(() => props.assignedUsers, (newUsers) => {
    users.value = cloneAssignedUsers(newUsers)
}, { deep: true })

watch(() => props.assignedDepartments, (newDeps) => {
    departments.value = (newDeps || []).map(d => ({ ...d }))
}, { deep: true })

watch(() => props.assignedCrmContacts, (newContacts) => {
    crmContacts.value = cloneAssignedCrmContacts(newContacts)
}, { deep: true })

// Beim Öffnen des Modals auf den neuesten Stand bringen
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        users.value = cloneAssignedUsers(props.assignedUsers)
        departments.value = (props.assignedDepartments || []).map(d => ({ ...d }))
        crmContacts.value = cloneAssignedCrmContacts(props.assignedCrmContacts)
    }
})

// --- computed ---
const page = usePage()
const authUserId = computed(() => page.props.auth.user.id)

const showCrmSearchDropdown = computed(() => {
    return crm_contact_query.value.length > 0 && crm_contact_search_results.value.length > 0
})

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
        // Neue Teammitglieder starten mit Schreibrecht (Spiegel des DB-Defaults von project_user.can_write)
        pivot_can_write: userToAdd.pivot_can_write ?? true,
        // Neue Teammitglieder starten mit ihren im Arbeitsprofil hinterlegten Standard-Projektrollen
        pivot_roles: [...(userToAdd.default_project_role_ids ?? userToAdd.pivot_roles ?? [])],
    })

    department_and_user_query.value = ''
}

const deleteUserFromProjectTeam = (user) => {
    users.value = users.value.filter(u => u.id !== user.id)
}

const addCrmContactToProjectTeamArray = (contactToAdd) => {
    if (crmContacts.value.some(c => c.id === contactToAdd.id)) {
        crm_contact_query.value = ''
        return
    }

    crmContacts.value.push({
        ...contactToAdd,
        pivot_roles: [...(contactToAdd.pivot_roles ?? [])],
    })

    crm_contact_query.value = ''
}

const deleteCrmContactFromProjectTeam = (contact) => {
    crmContacts.value = crmContacts.value.filter(c => c.id !== contact.id)
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

    form.assigned_crm_contact_ids = {}
    if (props.crmContactsEnabled) {
        crmContacts.value.forEach(contact => {
            form.assigned_crm_contact_ids[contact.id] = {
                roles: contact.pivot_roles,
            }
        })
    }

    // Ohne aktives Setting den Key gar nicht mitsenden — das Backend lässt
    // bestehende CRM-Verknüpfungen dann unangetastet
    form.transform((data) => {
        if (!props.crmContactsEnabled) {
            const { assigned_crm_contact_ids, ...rest } = data
            return rest
        }
        return data
    }).patch(route('projects.update_team', { project: props.projectId }))
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

watch(
    crm_contact_query,
    async (val) => {
        if (!val || val.length === 0) {
            crm_contact_search_results.value = []
            return
        }

        try {
            const response = await axios.get(route('crm.contacts.search'), {
                params: { search: val },
            })
            crm_contact_search_results.value = response.data || []
        } catch (e) {
            crm_contact_search_results.value = []
        }
    },
    { deep: false }
)
</script>
