<template>
    <UserHeader title="Users" description="Invite new users or edit an existing user">
        <!-- Topbar / Tabbar Slot -->
        <template #tabBar>

            <ToolbarHeader
                :icon="IconUsers"
                title="Users"
                icon-bg-class="bg-amber-600/10 text-amber-700"
                v-model="user_query"
                :description="users?.length ? `${users.length} ${$t('Users')}` : ''"
                :search-enabled="true"
                :search-label="$t('Search for Users')"
                :search-tooltip="$t('Search')"
            >
                <template #actions>
                    <BaseMenu show-sort-icon dots-size="size-5" has-no-offset dots-color="!text-zinc-900" menu-width="w-72" classes="ui-button" menu-button-text="Sort">
                        <div class="flex items-center justify-between py-1">
                            <span
                                class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-900 cursor-pointer"
                                @click="resetSort()"
                            >
                              {{ $t('Reset') }}
                            </span>
                        </div>
                        <MenuItem v-for="userSortEnumName in userSortEnumNames" :key="userSortEnumName" v-slot="{ active }">
                            <div
                                @click="sortBy = userSortEnumName; applyFiltersAndSort()"
                                :class="[
                                active ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-600',
                                'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm rounded-lg'
                              ]"
                            >
                                {{ getSortEnumTranslation(userSortEnumName) }}
                                <IconCheck v-if="getUserSortBySetting() === userSortEnumName" class="size-5 text-blue-600" />
                            </div>
                        </MenuItem>
                    </BaseMenu>

                    <button class="ui-button-add"  @click="addingUser = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('Invite new users') }}
                    </button>
                </template>
            </ToolbarHeader>
        </template>

        <!-- Main list -->
        <template #default>
            <BaseTable
                :rows="users"
                :columns="cols"
                row-key="email"
                v-model:page="page"
                empty-title="Keine Personen"
                empty-message="Derzeit sind keine Einträge vorhanden."
            >

                <!-- Name (Avatar + Name + Email) -->
                <template #cell-name="{ row }">
                    <Link class="flex items-center" :href="checkLink(row)">
                        <div class="size-11 shrink-0">
                            <img :src="row.profile_photo_url" alt="" class="size-11 rounded-full object-cover" />
                        </div>
                        <div class="ml-4">
                            <div class="font-medium text-gray-900">{{ row.first_name }} {{ row.last_name }}</div>
                            <div class="mt-1 text-gray-500">{{ row.email }}</div>
                        </div>
                    </Link>
                </template>

                <!-- Title + Department -->
                <template #cell-teams="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="flex -space-x-3">
                            <div
                                v-for="department in row.departments?.slice(0,2)"
                                :key="department.id"
                                class="relative"
                                v-tooltip.top="{ value: department.name, appendTo: 'body', class: 'aw-tooltip', position: 'top' }"
                            >
                                <TeamIconCollection
                                    class="size-10 min-w-10 min-h-10 rounded-full ring-2 ring-white"
                                    :iconName="department.svg_name"
                                />
                            </div>
                        </div>

                        <div v-if="row.departments?.length >= 3" class="relative">
                            <Menu as="div" class="relative">
                                <MenuButton
                                    class="flex size-10 items-center justify-center rounded-full bg-zinc-900 text-white ring-2 ring-white hover:bg-zinc-800"
                                >
                                    <ChevronDownIcon class="size-5" />
                                </MenuButton>
                                <transition
                                    enter-active-class="transition duration-100 ease-out"
                                    enter-from-class="opacity-0 translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition duration-75 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 translate-y-1"
                                >
                                    <MenuItems
                                        class="absolute right-0 z-30 mt-2 max-h-48 w-72 overflow-y-auto rounded-xl border border-zinc-200 bg-white py-1 shadow-lg focus:outline-none"
                                    >
                                        <MenuItem
                                            v-for="department in row.departments"
                                            :key="`${row.id}-${department.id}`"
                                            v-slot="{ active }"
                                        >
                                            <div
                                                :class="[
                                            active ? 'bg-zinc-100' : '',
                                            'flex items-center px-3 py-2 text-sm text-zinc-700'
                                          ]"
                                            >
                                                <TeamIconCollection class="size-8 rounded-full" :iconName="department.svg_name" />
                                                <span class="ml-3">{{ department.name }}</span>
                                            </div>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                </template>


                <!-- Actions -->
                <template #row-actions="{ row }">
                    <!-- Right: Actions -->
                    <BaseMenu v-if="hasAdminRole()" has-no-offset white-menu-background>
                        <BaseMenuItem :icon="IconEdit" title="Edit Profile" white-menu-background as-link :link="checkLink(row)" />
                        <BaseMenuItem :icon="IconTrash" v-if="row.type === 'user'" title="Delete user" white-menu-background @click="openDeleteUserModal(row)" />
                        <BaseMenuItem :icon="IconTrash" v-if="row.type === 'freelancer'" title="Delete freelancer" white-menu-background @click="openDeleteUserModal(row)" />
                        <BaseMenuItem :icon="IconTrash" v-if="row.type === 'service_provider'" title="Delete provider" white-menu-background @click="openDeleteUserModal(row)" />
                    </BaseMenu>
                </template>
            </BaseTable>

        </template>
    </UserHeader>

    <!-- Invite Modal -->
    <invite-users-modal
        v-if="addingUser"
        :closeModal="closeAddUserModal"
        :all_permissions="all_permissions"
        :departments="departments"
        :roles="roles"
        :permission_presets="permission_presets"
        :users="users"
        :invited-users="invitedUsers"
    />

    <!-- Delete Modal -->
    <BaseModal @closed="closeDeleteUserModal" v-if="deletingUser" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="text-2xl font-bold text-zinc-900 my-2">
        <span v-if="userToDelete?.type === 'user'">
          {{ $t('Delete user') }}
        </span>
                <span v-else-if="userToDelete?.type === 'freelancer'">
          {{ $t('Delete freelancer') }}
        </span>
                <span v-else-if="userToDelete?.type === 'service_provider'">
          {{ $t('Delete service provider') }}
        </span>
            </div>

            <div class="text-sm text-red-600">
        <span v-if="userToDelete?.type === 'user' || userToDelete?.type === 'freelancer'">
          {{
                $t('Are you sure you want to delete {last_name}, {first_name} from the system?', {
                    last_name: userToDelete?.last_name,
                    first_name: userToDelete?.first_name
                })
            }}
        </span>
                <span v-else-if="userToDelete?.type === 'service_provider'">
          {{
                        $t('Are you sure you want to delete { serviceProvider } from the system?', {
                            serviceProvider: userToDelete?.provider_name
                        })
                    }}
        </span>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <BaseUIButton :label="$t('Delete')" is-delete-button @click="deleteUser" />
                <button @click="closeDeleteUserModal" class="text-sm text-zinc-500 hover:text-zinc-800">
                    {{ $t('No, not really') }}
                </button>
            </div>
        </div>
    </BaseModal>

    <!-- Success -->
    <SuccessModal
        :open="showSuccessModal"
        @closed="closeSuccessModal"
        :title="$t('Users invited')"
        :description="$t('The users have received an invitation email.')"
        button="Okay"
    />
</template>

<script setup lang="ts">
import {ref, nextTick, watch} from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    Menu, MenuButton, MenuItem, MenuItems,
} from '@headlessui/vue'
import {
    SearchIcon, TrashIcon, XIcon, PencilAltIcon, ChevronDownIcon,
} from '@heroicons/vue/outline'
import {IconCheck, IconCirclePlus, IconEdit, IconGeometry, IconTrash, IconUsers} from '@tabler/icons-vue'
import debounce from 'lodash.debounce'
import InviteUsersModal from '@/Layouts/Components/InviteUsersModal.vue'
import SuccessModal from '@/Layouts/Components/General/SuccessModal.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import BaseCardButton from '@/Artwork/Buttons/BaseCardButton.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import UserHeader from '@/Pages/Users/UserHeader.vue'
import { is } from 'laravel-permission-to-vuejs'
import { useSortEnumTranslation } from '@/Composeables/SortEnumTranslation.js'
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";

const props = defineProps({
    users: Array,
    departments: Array,
    all_permissions: Object,
    roles: Array,
    freelancers: Array,
    serviceProviders: Array,
    permission_presets: Array,
    invitedUsers: Array,
    userSortEnumNames: Array,
    userUserManagementSetting: Object,
})

const { getSortEnumTranslation } = useSortEnumTranslation()

/* UI State */
const addingUser = ref(false)
const deletingUser = ref(false)
const showSuccessModal = ref(false)
const userToDelete = ref(null)

/* Search + Sort */
const showSearchbar = ref(route().params.query?.length > 0)
const user_query = ref(route().params.query?.length > 0 ? route().params.query : '')
const user_search_results = ref([]) // behalten für Kompatibilität
const sortBy = ref(props.userUserManagementSetting?.sort_by === null ? undefined : props.userUserManagementSetting?.sort_by)

/* Helpers */
const hasAdminRole = () => is('artwork admin')

const checkLink = (user) => {
    if (user.type === 'freelancer') {
        return route('freelancer.show', { freelancer: user.id })
    }
    if (user.type === 'service_provider') {
        return route('service_provider.show', { serviceProvider: user.id })
    }
    return route('user.edit.shiftplan', { user: user.id })
}

/* Searchbar handlers */
const searchBarInput = ref(null)
const closeSearchbar = () => {
    showSearchbar.value = !showSearchbar.value
    user_query.value = ''
}
const openSearchbar = () => {
    showSearchbar.value = !showSearchbar.value
    nextTick(() => {
        if (showSearchbar.value) searchBarInput.value?.focus()
    })
}

/* Success modal */
const openSuccessModal = () => {
    showSuccessModal.value = true
    setTimeout(() => closeSuccessModal(), 2000)
}
const closeSuccessModal = () => (showSuccessModal.value = false)

/* Delete flow */
const openDeleteUserModal = (user) => {
    userToDelete.value = user
    deletingUser.value = true
}
const closeDeleteUserModal = () => {
    userToDelete.value = null
    deletingUser.value = false
}
const deleteUser = () => {
    if (!userToDelete.value) return
    let desiredRoute = null
    const u = userToDelete.value

    switch (u.type) {
        case 'user':
            desiredRoute = route('user.destroy', { user: u.id })
            break
        case 'freelancer':
            desiredRoute = route('freelancer.destroy', { freelancer: u.id })
            break
        case 'service_provider':
            desiredRoute = route('service_provider.destroy', { serviceProvider: u.id })
            break
    }

    if (desiredRoute) {
        router.delete(desiredRoute, {
            onSuccess: () => closeDeleteUserModal(),
        })
    }
}

/* Invite close */
const closeAddUserModal = (bool) => {
    addingUser.value = false
    if (bool) openSuccessModal()
}

/* Sorting & Filtering (unchanged behavior) */
const applyFiltersAndSort = () => {
    router.get(
        route().current(),
        {
            query: user_query.value,
            sort: sortBy.value,
            saveFilterAndSort: 1,
        },
        { preserveState: true }
    )
}

const getUserSortBySetting = () => props.userUserManagementSetting?.sort_by
const resetSort = () => {
    sortBy.value = undefined
    applyFiltersAndSort()
}

const reloadUsersDebounced = debounce(() => applyFiltersAndSort(), 1000)

watch(user_query, () => {
    reloadUsersDebounced()
})

import BaseTable, { type TableColumn } from '@/Artwork/Table/BaseTable.vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const cols = ref<TableColumn[]>([
    { key: 'name',  label: 'Name',  sortable: false },
    { key: 'position', label: 'Position', sortable: false },
    { key: 'teams', label: 'Teams', sortable: false },
])

const page = ref(1)

</script>
