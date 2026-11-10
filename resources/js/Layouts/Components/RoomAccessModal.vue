<template>
    <ArtworkBaseModal
        v-if="show"
        modal-size="sm:max-w-3xl"
        title="Access to room"
        description="Define who can edit the room and release bookings (room admin), and who can request the room."
        @close="$emit('close')"
    >
        <div class="space-y-6">
            <!-- Personen und Teams in einer Suche -->
            <div class="relative">
                <BaseInput
                    id="roomAccessSearch"
                    v-model="query"
                    :label="$t('Search for people and teams')"
                    autocomplete="off"
                />
                <div
                    v-if="showResults"
                    class="absolute z-20 mt-1 w-full max-h-72 overflow-auto rounded-xl border border-border-subtle bg-white shadow-xl ring-1 ring-black/5"
                >
                    <div v-if="searchResults.departments.length" class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-text-subtle">
                        {{ $t('Teams') }}
                    </div>
                    <button
                        v-for="department in searchResults.departments"
                        :key="`dep-${department.id}`"
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-surface-sunken"
                        @click="addDepartment(department)"
                    >
                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-8 w-8 rounded-full object-cover" />
                        <span class="text-sm font-medium text-text">{{ department.name }}</span>
                        <span class="ml-auto text-xs text-text-subtle">{{ $t('Members: {0}', [department.users?.length ?? 0]) }}</span>
                    </button>
                    <div v-if="searchResults.users.length" class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-text-subtle">
                        {{ $t('People') }}
                    </div>
                    <button
                        v-for="user in searchResults.users"
                        :key="`user-${user.id}`"
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-surface-sunken"
                        @click="addUser(user)"
                    >
                        <img :src="user.profile_photo_url" alt="" class="h-8 w-8 rounded-full object-cover" />
                        <span class="text-sm font-medium text-text">{{ user.first_name }} {{ user.last_name }}</span>
                    </button>
                </div>
            </div>

            <p v-if="!roomUsers.length && !roomDepartments.length" class="rounded-xl border border-dashed border-border-subtle px-4 py-6 text-center text-sm text-text-subtle">
                {{ $t('Nobody has access to this room yet. Add people or whole teams above.') }}
            </p>

            <!-- Teams: Rechte gelten für alle Mitglieder -->
            <section v-if="roomDepartments.length">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t('Teams') }}</h3>
                <div class="divide-y divide-border-subtle rounded-2xl border border-border-subtle bg-white">
                    <div v-for="department in roomDepartments" :key="`assigned-dep-${department.id}`" class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-9 w-9 shrink-0 rounded-full object-cover" />
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-text">{{ department.name }}</div>
                                <div class="text-xs text-text-subtle">{{ $t('Applies to all members ({0})', [department.users_count ?? department.users?.length ?? 0]) }}</div>
                            </div>
                        </div>
                        <BaseCheckbox :id="`dep-${department.id}-admin`" v-model="department.is_admin" :label="$t('Room admin')" />
                        <BaseCheckbox :id="`dep-${department.id}-request`" v-model="department.can_request" :label="$t('Authorized to request')" />
                        <button type="button" class="rounded-full p-1 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="removeDepartment(department.id)">
                            <span class="sr-only">{{ $t('Remove team from room access') }}</span>
                            <IconCircleX class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </section>

            <section v-if="roomUsers.length">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t('People') }}</h3>
                <div class="divide-y divide-border-subtle rounded-2xl border border-border-subtle bg-white">
                    <div v-for="user in roomUsers" :key="`assigned-user-${user.id}`" class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <img class="h-9 w-9 shrink-0 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                            <span class="truncate font-semibold text-text">{{ user.first_name }} {{ user.last_name }}</span>
                        </div>
                        <BaseCheckbox :id="`user-${user.id}-admin`" v-model="user.is_admin" :label="$t('Room admin')" />
                        <BaseCheckbox :id="`user-${user.id}-request`" v-model="user.can_request" :label="$t('Authorized to request')" />
                        <button type="button" class="rounded-full p-1 text-text-subtle hover:bg-surface-sunken hover:text-danger" @click="removeUser(user.id)">
                            <span class="sr-only">{{ $t('Remove user as room admin') }}</span>
                            <IconCircleX class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <BaseUIButton :label="$t('Save')" is-add-button :processing="saving" @click="save" />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { IconCircleX } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'

const props = defineProps({
    show: Boolean,
    room: Object,
})
const emit = defineEmits(['close'])

const roomUsers = ref((props.room.users ?? []).map((user) => ({
    ...user,
    is_admin: !!user.pivot?.is_admin,
    can_request: !!user.pivot?.can_request,
})))
const roomDepartments = ref((props.room.departments ?? []).map((department) => ({ ...department })))

const query = ref('')
const searchResults = reactive({ users: [], departments: [] })
const showResults = computed(() => query.value.trim().length > 0
    && (searchResults.users.length > 0 || searchResults.departments.length > 0))

let searchTimer = null
let searchToken = 0
watch(query, (value) => {
    clearTimeout(searchTimer)
    const term = value.trim()
    if (!term) {
        searchResults.users = []
        searchResults.departments = []
        return
    }
    searchTimer = setTimeout(async () => {
        const token = ++searchToken
        // Teams über departments.search: users_departments.search liefert Teams nur mit Team-Verwaltungsrecht
        const [usersResponse, departmentsResponse] = await Promise.allSettled([
            axios.get(route('users_departments.search'), { params: { query: term } }),
            axios.get(route('departments.search'), { params: { query: term } }),
        ])
        if (token !== searchToken) return
        const assignedUserIds = new Set(roomUsers.value.map((user) => user.id))
        const assignedDepartmentIds = new Set(roomDepartments.value.map((department) => department.id))
        searchResults.users = usersResponse.status === 'fulfilled'
            ? (usersResponse.value.data?.users ?? []).filter((user) => !assignedUserIds.has(user.id))
            : []
        searchResults.departments = departmentsResponse.status === 'fulfilled'
            ? (departmentsResponse.value.data ?? []).filter((department) => !assignedDepartmentIds.has(department.id))
            : []
    }, 250)
})

function resetSearch() {
    query.value = ''
    searchResults.users = []
    searchResults.departments = []
}

function addUser(user) {
    if (!roomUsers.value.some((roomUser) => roomUser.id === user.id)) {
        roomUsers.value.push({ ...user, is_admin: false, can_request: true })
    }
    resetSearch()
}

function addDepartment(department) {
    if (!roomDepartments.value.some((roomDepartment) => roomDepartment.id === department.id)) {
        roomDepartments.value.push({
            id: department.id,
            name: department.name,
            svg_name: department.svg_name,
            users_count: department.users?.length ?? 0,
            is_admin: false,
            can_request: true,
        })
    }
    resetSearch()
}

function removeUser(userId) {
    roomUsers.value = roomUsers.value.filter((user) => user.id !== userId)
}

function removeDepartment(departmentId) {
    roomDepartments.value = roomDepartments.value.filter((department) => department.id !== departmentId)
}

const saving = ref(false)
function save() {
    if (saving.value) return
    saving.value = true
    router.patch(route('room.users.update', { room: props.room.id }), {
        users: roomUsers.value.map((user) => ({ id: user.id, is_admin: !!user.is_admin, can_request: !!user.can_request })),
        departments: roomDepartments.value.map((department) => ({
            id: department.id,
            is_admin: !!department.is_admin,
            can_request: !!department.can_request,
        })),
    }, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onFinish: () => { saving.value = false },
    })
}
</script>
