<template>
    <UserHeader
        title="Freelancers & Service Providers"
        description="Manage your freelancers and service providers"
    >
        <div class="my-8">
            <!-- Top controls -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                <!-- Left: Filter + Invite -->
                <div class="flex items-center gap-3">
                    <!-- Filter -->
                    <Listbox v-model="selectedFilter" as="div" class="relative">
                        <ListboxButton
                            class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-900 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-blue-600"
                        >
                            <span>{{ $t(selectedFilter.name) }}</span>
                            <ChevronDownIcon class="size-5 text-zinc-500" />
                        </ListboxButton>

                        <transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <ListboxOptions
                                class="absolute z-40 mt-2 w-64 rounded-xl border border-zinc-200 bg-white p-1 shadow-lg focus:outline-none"
                            >
                                <ListboxOption
                                    v-for="filter in displayFilters"
                                    :key="filter.name"
                                    :value="filter"
                                    v-slot="{ active, selected }"
                                >
                                    <li
                                        :class="[
                                          active ? 'bg-zinc-100' : '',
                                          'flex cursor-pointer items-center justify-between rounded-lg px-3 py-2 text-sm text-zinc-800'
                                        ]"
                                                        >
                                        <span :class="[selected ? 'font-semibold' : 'font-normal']">
                                          {{ $t(filter.name) }}
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>


                </div>

                <!-- Right: Sort + Search -->
                <div class="flex items-center gap-3">
                    <!-- Invite button (permission gated) -->
                    <button
                        v-if="can('can manage workers') || is('artwork admin')"
                        @click="openSelectAddUsersModal = true"
                        type="button"
                        class="ui-button"
                    >
                        <PlusIcon class="size-5" />
                    </button>
                    <!-- Sort -->
                    <BaseMenu show-sort-icon dots-size="size-5" menu-width="w-72" classes="ui-button">
                        <div class="flex items-center justify-between px-4 py-2">
                            <button
                                class="text-xs text-zinc-500 hover:text-zinc-900"
                                @click="resetSort()"
                                type="button"
                            >
                                {{ $t('Reset') }}
                            </button>
                        </div>
                        <MenuItem
                            v-for="memberSortEnum in memberSortEnums"
                            :key="memberSortEnum"
                            v-slot="{ active }"
                        >
                            <div
                                @click="sortBy = memberSortEnum; applyFiltersAndSort()"
                                :class="[
                  active ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-600',
                  'cursor-pointer group flex items-center justify-between px-4 py-2 text-sm'
                ]"
                            >
                                {{ getSortEnumTranslation(memberSortEnum) }}
                                <IconCheck
                                    v-if="getUserSortBySetting() === memberSortEnum"
                                    class="size-5 text-blue-600"
                                />
                            </div>
                        </MenuItem>
                    </BaseMenu>

                    <!-- Search -->
                    <button
                        v-if="!showSearchbar"
                        @click="openSearchbar"
                        class="ui-button"
                    >
                        <SearchIcon class="size-5 text-zinc-700" />
                    </button>
                    <div v-else class="flex items-center">
                        <input
                            id="userSearch"
                            ref="searchBarInput"
                            v-model="user_query"
                            type="text"
                            autocomplete="off"
                            :placeholder="$t('Search users')"
                            class="h-10 w-64 rounded-xl border border-zinc-300 bg-white px-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-600"
                        />
                        <XIcon
                            class="ml-2 size-5 cursor-pointer text-zinc-500 hover:text-zinc-800"
                            @click="closeSearchbar()"
                        />
                    </div>
                </div>
            </div>

            <!-- List -->
            <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
                <ul role="list" class="divide-y divide-zinc-100">
                    <!-- No separate search results array used -> keep compatibility -->
                    <li
                        v-if="user_search_results.length < 1"
                        v-for="user in userObjectsToShow"
                        :key="`${user.type}-${user.id}`"
                        class="py-4"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <!-- Left: Avatar + Name -->
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <img
                                    class="size-12 rounded-full object-cover ring-2 ring-zinc-200"
                                    :src="user.profile_photo_url ?? user.profile_image"
                                    :alt="user.display_name ?? user.provider_name"
                                />
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link
                                            :href="checkLink(user)"
                                            class="truncate text-sm font-medium text-zinc-900 hover:text-blue-700"
                                        >
                                            {{ user.display_name ?? user.provider_name }}
                                            <span v-if="user.position || user.business">,</span>
                                        </Link>
                                        <p class="truncate text-sm text-zinc-500">
                                            <span v-if="user.business">{{ user.business }}<span v-if="user.position">,</span> </span>
                                            <span v-if="user.position">{{ user.position }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Middle: Departments (only when relevant; kept for parity) -->
                            <div
                                class="flex items-center gap-3"
                                v-if="selectedFilter.type === 'users'"
                            >
                                <div class="flex -space-x-3">
                                    <div v-for="department in (user.departments?.slice(0,2) || [])" :key="department.id">
                                        <TeamIconCollection
                                            class="size-10 rounded-full ring-2 ring-white"
                                            :iconName="department.svg_name"
                                        />
                                    </div>
                                </div>

                                <div v-if="(user.departments || []).length >= 3" class="relative">
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
                                                    v-for="department in user.departments"
                                                    :key="`${user.id}-${department.id}`"
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

                            <!-- Right: Actions -->
                            <BaseMenu v-if="is('artwork admin')" has-no-offset>
                                <MenuItem v-slot="{ active }">
                                    <a
                                        :href="checkLink(user)"
                                        :class="[
                      active ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-700',
                      'group flex items-center px-4 py-2 text-sm'
                    ]"
                                    >
                                        <PencilAltIcon class="mr-3 size-5 text-zinc-500 group-hover:text-blue-700" />
                                        {{ $t('Edit Profile') }}
                                    </a>
                                </MenuItem>

                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        @click="openDeleteUserModal(user)"
                                        :class="[
                      active ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-700',
                      'group flex w-full items-center px-4 py-2 text-sm'
                    ]"
                                    >
                                        <TrashIcon class="mr-3 size-5 text-zinc-500 group-hover:text-red-600" />
                                        <span v-if="user.type === 'user'">{{ $t('Delete user') }}</span>
                                        <span v-else-if="user.type === 'freelancer'">{{ $t('Delete freelancer') }}</span>
                                        <span v-else-if="user.type === 'service_provider'">{{ $t('Delete service provider') }}</span>
                                    </button>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </li>

                    <!-- (Optional) separate results list kept for compatibility -->
                    <li
                        v-else
                        v-for="user in user_search_results"
                        :key="`search-${user.email}`"
                        class="py-4"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <img
                                    class="size-12 rounded-full object-cover ring-2 ring-zinc-200"
                                    :src="user.profile_photo_url"
                                    :alt="user.name"
                                />
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link
                                            v-if="is('artwork admin')"
                                            :href="getEditHref(user)"
                                            class="truncate text-sm font-medium text-zinc-900 hover:text-blue-700"
                                        >
                                            {{ user.name }}
                                        </Link>
                                        <p class="truncate text-sm text-zinc-500">
                                            {{ user.business }}, {{ user.position }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex -space-x-3">
                                    <div v-for="department in (user.departments || []).slice(0,2)" :key="department.id">
                                        <TeamIconCollection class="size-10 rounded-full ring-2 ring-white" :iconName="department.svg_name" />
                                    </div>
                                </div>

                                <div v-if="(user.departments || []).length >= 3" class="relative">
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
                                                    v-for="department in user.departments"
                                                    :key="`s-${user.id}-${department.id}`"
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

                            <BaseMenu v-if="is('artwork admin')">
                                <MenuItem v-slot="{ active }">
                                    <a
                                        :href="getEditHref(user)"
                                        :class="[
                      active ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-700',
                      'group flex items-center px-4 py-2 text-sm'
                    ]"
                                    >
                                        <PencilAltIcon class="mr-3 size-5 text-zinc-500 group-hover:text-blue-700" />
                                        {{ $t('Edit Profile') }}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        @click="openDeleteUserModal(user)"
                                        :class="[
                      active ? 'bg-zinc-100 text-zinc-900' : 'text-zinc-700',
                      'group flex w-full items-center px-4 py-2 text-sm'
                    ]"
                                    >
                                        <TrashIcon class="mr-3 size-5 text-zinc-500 group-hover:text-red-600" />
                                        {{ $t('Delete user') }}
                                    </button>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Delete Modal -->
        <BaseModal
            v-if="deletingUser"
            @closed="closeDeleteUserModal"
            modal-image="/Svgs/Overlays/illu_warning.svg"
        >
            <div class="mx-4">
                <div class="my-2 text-2xl font-bold text-zinc-900">
                    <span v-if="userToDelete?.type === 'user'">{{ $t('Delete user') }}</span>
                    <span v-else-if="userToDelete?.type === 'freelancer'">{{ $t('Delete freelancer') }}</span>
                    <span v-else-if="userToDelete?.type === 'service_provider'">{{ $t('Delete service provider') }}</span>
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
                    <FormButton :text="$t('Delete')" @click="deleteUser" />
                    <button @click="closeDeleteUserModal" class="text-sm text-zinc-500 hover:text-zinc-800">
                        {{ $t('No, not really') }}
                    </button>
                </div>
            </div>
        </BaseModal>

        <!-- Success Modal -->
        <SuccessModal
            :open="showSuccessModal"
            @closed="closeSuccessModal"
            :title="$t('Users invited')"
            :description="$t('The users have received an invitation email.')"
            button="Okay"
        />
    </UserHeader>

    <!-- Invite users -->
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

    <!-- Helper modal to choose type (unchanged behavior) -->
    <AddUsersModal
        v-if="openSelectAddUsersModal"
        @closeModal="openSelectAddUsersModal = false"
        @openUserModal="addingUser = true"
    />
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    Listbox, ListboxButton, ListboxOption, ListboxOptions,
    Menu, MenuButton, MenuItem, MenuItems,
} from '@headlessui/vue'
import {
    SearchIcon, TrashIcon, XIcon, PencilAltIcon, ChevronDownIcon, PlusIcon,
} from '@heroicons/vue/outline'
import { IconCheck } from '@tabler/icons-vue'
import InviteUsersModal from '@/Layouts/Components/InviteUsersModal.vue'
import SuccessModal from '@/Layouts/Components/General/SuccessModal.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import UserHeader from '@/Pages/Users/UserHeader.vue'
import AddUsersModal from '@/Pages/Users/Components/AddUsersModal.vue'
import { is, can } from 'laravel-permission-to-vuejs'
import debounce from 'lodash.debounce'
import { useSortEnumTranslation } from '@/Composeables/SortEnumTranslation.js'

const props = defineProps({
    users: Array,
    departments: Array,
    all_permissions: Array,
    roles: Array,
    freelancers: Array,
    serviceProviders: Array,
    permission_presets: Array,
    invitedUsers: Array,
    memberSortEnums: Array,
    userUserManagementSetting: Object,
})

/* i18n helper */
const { getSortEnumTranslation } = useSortEnumTranslation()

/* UI state */
const addingUser = ref(false)
const openSelectAddUsersModal = ref(false)
const deletingUser = ref(false)
const userToDelete = ref(null)
const showSuccessModal = ref(false)

/* Search + sort */
const showSearchbar = ref(route().params.query?.length > 0)
const user_query = ref(route().params.query?.length > 0 ? route().params.query : '')
const user_search_results = ref([]) // kept for compatibility
const sortBy = ref(props.userUserManagementSetting?.sort_by === null ? undefined : props.userUserManagementSetting?.sort_by)

/* Filters */
const displayFilters = ref([
    { name: window?.__app__?.i18n?.t?.('All freelancers') ?? 'All freelancers', type: 'freelancer' },
    { name: window?.__app__?.i18n?.t?.('All service providers') ?? 'All service providers', type: 'service_provider' },
    { name: window?.__app__?.i18n?.t?.('All available addresses') ?? 'All available addresses', type: 'all' },
])
const selectedFilter = ref({ name: 'All available addresses', type: 'all' })

/* Derived list */
const userObjectsToShow = computed(() => {
    if (selectedFilter.value.type === 'freelancer') return props.freelancers || []
    if (selectedFilter.value.type === 'service_provider') return props.serviceProviders || []
    return (props.freelancers || []).concat(props.serviceProviders || [])
})

/* Links */
const checkLink = (user) => {
    if (user.type === 'freelancer') return route('freelancer.show', { freelancer: user.id })
    if (user.type === 'service_provider') return route('service_provider.show', { serviceProvider: user.id })
    if (user.user === 'user') return route('user.edit.shiftplan', { user: user.id })
    return route('user.edit.shiftplan', { user: user.id })
}
const getEditHref = (user) => route('user.edit.shiftplan', { user: user.id })

/* Search handlers */
const searchBarInput = ref(null)
const closeSearchbar = () => {
    showSearchbar.value = !showSearchbar.value
    user_query.value = ''
}
const openSearchbar = () => {
    showSearchbar.value = !showSearchbar.value
    nextTick(() => showSearchbar.value && searchBarInput.value?.focus())
}

/* Success */
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

/* Invite modal close */
const closeAddUserModal = (ok) => {
    addingUser.value = false
    if (ok) openSuccessModal()
}

/* Sorting / Filtering */
const applyFiltersAndSort = () => {
    router.get(
        route('users.addresses'),
        { query: user_query.value, sort: sortBy.value, saveFilterAndSort: 1 },
        { preserveState: true }
    )
}
const getUserSortBySetting = () => props.userUserManagementSetting?.sort_by
const resetSort = () => {
    sortBy.value = undefined
    applyFiltersAndSort()
}
const reloadUsersDebounced = debounce(() => applyFiltersAndSort(), 1000)

watch(user_query, () => reloadUsersDebounced())
</script>
