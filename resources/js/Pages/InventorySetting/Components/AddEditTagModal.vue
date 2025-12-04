<template>
    <ArtworkBaseModal
        :title="tag?.id ? $t('Edit tag') : $t('Add tag')"
        :description="tag?.id
            ? $t('Edit the tag and its settings.')
            : $t('Create a new tag and assign it to a group.')"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-8">
            <!-- SECTION: Basics -->
            <div class="rounded-2xl border border-gray-100 bg-gradient-to-br from-white via-gray-50 to-slate-50 p-5 space-y-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <span
                                class="inline-flex h-6 w-6 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 text-xs font-bold"
                            >
                                1
                            </span>
                            {{ $t('Tag basics') }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $t('Define the name, group and color of this tag.') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name + Gruppe -->
                    <div class="space-y-4">
                        <BaseInput
                            id="name"
                            v-model="form.name"
                            :label="$t('Tag name')"
                            required
                            :error="form.errors.name"
                        />

                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-gray-700">
                                {{ $t('Tag group') }}
                            </label>
                            <ArtworkBaseListbox
                                v-model="form.inventory_tag_group_id"
                                :items="tagGroupOptions"
                                :option-label="(o) => o ? o.name : ''"
                                option-key="id"
                                by="id"
                                :placeholder="$t('Select a tag group (optional)')"
                                :enable-search="true"
                                :search-keys="['label']"
                                :clearable="true"
                            />
                            <p v-if="form.errors.inventory_tag_group_id" class="mt-1 text-xs text-red-500">
                                {{ form.errors.inventory_tag_group_id }}
                            </p>
                            <p class="text-[11px] text-gray-400">
                                {{ $t('Groups help to structure tags in the filter sidebar.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Farbe -->
                    <div class="space-y-3">
                        <label class="block text-xs font-medium text-gray-700">
                            {{ $t('Tag color') }}
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <ColorPickerComponent
                                    :color="form.color"
                                    @updateColor="onColorChange"
                                />
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-[11px] text-gray-500">
                                    {{ $t('Preview') }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium border border-gray-100"
                                    :style="{ backgroundColor: softColor, color: textOnSoftColor }"
                                >
                                    <span
                                        class="inline-flex h-1.5 w-1.5 rounded-full"
                                        :style="{ backgroundColor: form.color }"
                                    />
                                    {{ form.name || $t('Example tag') }}
                                </span>
                            </div>
                        </div>

                        <p v-if="form.errors.color" class="mt-1 text-xs text-red-500">{{ form.errors.color }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $t('This color is used to visually highlight the tag in the UI.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- SECTION: Permissions -->
            <div class="rounded-2xl border border-gray-100 bg-gradient-to-br from-white via-slate-50 to-gray-50 p-5 space-y-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <span
                                class="inline-flex h-6 w-6 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 text-xs font-bold"
                            >
                                2
                            </span>
                            {{ $t('Permissions') }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 max-w-md">
                            {{ $t('Control who is allowed to edit or use this tag for inventory maintenance.') }}
                        </p>
                    </div>

                    <label
                        class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-xs cursor-pointer hover:border-emerald-300 hover:bg-emerald-50/70 transition-colors"
                    >
                        <input
                            type="checkbox"
                            v-model="form.has_restricted_permissions"
                            class="input-checklist"
                        />
                        <span>
                            {{ $t('Restrict editing of this tag') }}
                        </span>
                    </label>
                </div>

                <div
                    v-if="form.has_restricted_permissions"
                    class="space-y-6"
                >
                    <!-- Suche -->
                    <div class="relative">
                        <BaseInput
                            :label="$t('Search for teams and/or users')"
                            v-model="userAndTeamsQuery"
                            id="searchUsersAndTeams_1"
                            @input="searchUsersAndTeams"
                            :placeholder="$t('Type to search for users or departments...')"
                        />

                        <!-- Search dropdown -->
                        <div
                            v-if="hasSearchResults"
                            class="absolute z-20 mt-1 w-full max-h-64 overflow-auto rounded-2xl border border-gray-200 bg-white text-sm shadow-xl"
                        >
                            <div class="py-1">
                                <!-- Users -->
                                <template v-if="userAndTeamsSearchResult.users?.length">
                                    <div class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        {{ $t('Users') }}
                                    </div>
                                    <div class="divide-y divide-gray-50">
                                        <div
                                            v-for="(user, idx) in userAndTeamsSearchResult.users"
                                            :key="'u_' + idx"
                                            class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                            @click="addUser(user)"
                                        >
                                            <img
                                                :src="user.profile_photo_url"
                                                :alt="user.first_name + ' ' + user.last_name"
                                                class="h-8 w-8 rounded-full object-cover ring-1 ring-gray-200"
                                            />
                                            <div class="flex flex-col min-w-0">
                                                <span class="truncate text-xs font-medium text-gray-900">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </span>
                                                <span v-if="user.position" class="truncate text-[11px] text-gray-400">
                                                    {{ user.position }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Divider zwischen Usern & Departments -->
                                <div
                                    v-if="userAndTeamsSearchResult.users?.length && userAndTeamsSearchResult.departments?.length"
                                    class="my-1 border-t border-gray-100"
                                />

                                <!-- Departments -->
                                <template v-if="userAndTeamsSearchResult.departments?.length">
                                    <div class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                                        {{ $t('Departments') }}
                                    </div>
                                    <div class="divide-y divide-gray-50">
                                        <div
                                            v-for="(department, idx) in userAndTeamsSearchResult.departments"
                                            :key="'d_' + idx"
                                            class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                            @click="addDepartment(department)"
                                        >
                                            <TeamIconCollection
                                                :iconName="department.svg_name"
                                                :alt="department.name"
                                                class="h-8 w-8"
                                            />
                                            <div class="flex flex-col min-w-0">
                                                <span class="truncate text-xs font-medium text-gray-900">
                                                    {{ department.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Keine Ergebnisse -->
                                <div
                                    v-if="!userAndTeamsSearchResult.users?.length && !userAndTeamsSearchResult.departments?.length"
                                    class="px-4 py-3 text-xs text-gray-400"
                                >
                                    {{ $t('No results found for your search.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auswahl-Übersicht -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Users -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-gray-700 uppercase tracking-wide">
                                    {{ $t('Users') }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-500">
                                    <span class="h-1 w-1 rounded-full bg-gray-400" />
                                    {{ $t('Selected') }}: {{ selectedUsers.length }}
                                </span>
                            </div>

                            <div
                                class="mt-1 flex flex-wrap gap-1.5 min-h-[1.75rem] rounded-xl bg-white/70 px-2 py-1 border border-dashed border-gray-200"
                            >
                                <span
                                    v-for="user in selectedUsers"
                                    :key="user.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-indigo-50 text-indigo-700 px-2 py-0.5 text-xs shadow-xs border border-indigo-100"
                                >
                                    <span class="truncate max-w-[140px]">
                                        {{ user.first_name }} {{ user.last_name }}
                                    </span>
                                    <button
                                        type="button"
                                        class="text-indigo-400 hover:text-indigo-700"
                                        @click="removeUser(user.id)"
                                    >
                                        ×
                                    </button>
                                </span>

                                <span
                                    v-if="selectedUsers.length === 0"
                                    class="text-[11px] text-gray-400"
                                >
                                    {{ $t('No users selected yet.') }}
                                </span>
                            </div>
                        </div>

                        <!-- Departments -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-gray-700 uppercase tracking-wide">
                                    {{ $t('Departments') }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-500">
                                    <span class="h-1 w-1 rounded-full bg-gray-400" />
                                    {{ $t('Selected') }}: {{ selectedDepartments.length }}
                                </span>
                            </div>

                            <div
                                class="mt-1 flex flex-wrap gap-1.5 min-h-[1.75rem] rounded-xl bg-white/70 px-2 py-1 border border-dashed border-gray-200"
                            >
                                <span
                                    v-for="department in selectedDepartments"
                                    :key="department.id"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 px-2 py-0.5 text-xs shadow-xs border border-emerald-100"
                                >
                                    <span class="truncate max-w-[140px]">
                                        {{ department.name }}
                                    </span>
                                    <button
                                        type="button"
                                        class="text-emerald-400 hover:text-emerald-700"
                                        @click="removeDepartment(department.id)"
                                    >
                                        ×
                                    </button>
                                </span>

                                <span
                                    v-if="selectedDepartments.length === 0"
                                    class="text-[11px] text-gray-400"
                                >
                                    {{ $t('No departments selected yet.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-else class="text-xs text-gray-500 flex items-center gap-1.5">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-gray-100 text-[10px] text-gray-500">
                        i
                    </span>
                    {{ $t('If restriction is disabled, every user with access to the inventory can use this tag.') }}
                </p>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <BaseUIButton
                    is-cancel-button
                    :label="$t('Cancel')"
                    type="button"
                    @click="$emit('close')"
                />
                <BaseUIButton
                    type="submit"
                    is-add-button
                    :label="tag?.id ? $t('Save changes') : $t('Create tag')"
                    :disabled="form.processing"
                />
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";

const props = defineProps({
    tag: {
        type: Object,
        required: false,
        default: null
    },
    tagGroups: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['close', 'saved'])

const form = useForm({
    id: props.tag?.id || null,
    name: props.tag?.name || '',
    color: props.tag?.color || '#000000',
    has_restricted_permissions: props.tag?.has_restricted_permissions ?? false,
    permission_mode: props.tag?.permission_mode || 'restricted_edit',
    inventory_tag_group_id: props.tag?.inventory_tag_group_id ? props.tagGroups.find(g => g.id === props.tag.inventory_tag_group_id) || null : null,
    position: props.tag?.position || null,
    user_ids: props.tag?.allowed_users?.map(u => u.id) || [],
    department_ids: props.tag?.allowed_departments?.map(d => d.id) || []
})

/**
 * Taggruppen für ArtworkBaseListbox aufbereiten
 */
const tagGroupOptions = computed(() => {
    return (props.tagGroups || []).map(group => ({
        id: group.id,
        name: group.name
    }))
})

/**
 * Farbe „weicher“ Hintergrund für Preview
 */
const softColor = computed(() => {
    // ganz simpel: ein bisschen Transparenz
    return form.color?.startsWith('#')
        ? form.color + '20'
        : '#EEF2FF'
})

const textOnSoftColor = computed(() => '#111827')

// Ausgewählte User / Departments (Objekte für Anzeige)
const selectedUsers = ref(props.tag?.allowed_users ? [...props.tag.allowed_users] : [])
const selectedDepartments = ref(props.tag?.allowed_departments ? [...props.tag.allowed_departments] : [])

// Suche
const userAndTeamsQuery = ref('')
const userAndTeamsSearchResult = reactive({
    users: [],
    departments: []
})

const hasSearchResults = computed(
    () =>
        (userAndTeamsSearchResult.users?.length > 0 ||
            userAndTeamsSearchResult.departments?.length > 0) &&
        userAndTeamsQuery.value.length > 0
)

function resetSearch() {
    userAndTeamsQuery.value = ''
    userAndTeamsSearchResult.users = []
    userAndTeamsSearchResult.departments = []
}

async function searchUsersAndTeams() {
    const q = userAndTeamsQuery.value?.trim()
    if (!q) {
        resetSearch()
        return
    }

    try {
        const { data } = await axios.get(route('users_departments.search'), {
            params: { query: q }
        })

        userAndTeamsSearchResult.users = data.users ?? []
        userAndTeamsSearchResult.departments = data.departments ?? []
    } catch (e) {
        userAndTeamsSearchResult.users = []
        userAndTeamsSearchResult.departments = []
    }
}

// Sync IDs ins Form
watch(
    selectedUsers,
    val => {
        form.user_ids = val.map(u => u.id)
    },
    { deep: true }
)

watch(
    selectedDepartments,
    val => {
        form.department_ids = val.map(d => d.id)
    },
    { deep: true }
)

function addUser(user) {
    if (!selectedUsers.value.some(u => u.id === user.id)) {
        selectedUsers.value.push(user)
    }
    resetSearch()
}

function addDepartment(department) {
    if (!selectedDepartments.value.some(d => d.id === department.id)) {
        selectedDepartments.value.push(department)
    }
    resetSearch()
}

function removeUser(id) {
    selectedUsers.value = selectedUsers.value.filter(user => user.id !== id)
}

function removeDepartment(id) {
    selectedDepartments.value = selectedDepartments.value.filter(dep => dep.id !== id)
}

function onColorChange(color) {
    if (typeof color === 'string') {
        form.color = color
        return
    }
    const pure = color?.pureColor || color?.hex || color?.rgba || null
    if (pure) {
        form.color = pure
    }
}

function submit() {

    form.inventory_tag_group_id = form.inventory_tag_group_id?.id ?? null;

    if (props.tag?.id) {
        form.patch(route('settings.inventory-tags.update', props.tag.id), {
            preserveScroll: false,
            preserveState: true,
            onSuccess: () => {
                emit('saved')
                emit('close')
            }
        })
    } else {
        form.post(route('settings.inventory-tags.store'), {
            preserveScroll: false,
            preserveState: true,
            onSuccess: () => {
                emit('saved')
                emit('close')
            }
        })
    }
}
</script>

<style scoped>
/* leichte Schattenklasse für Pills / Badges */
.shadow-xs {
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
</style>
