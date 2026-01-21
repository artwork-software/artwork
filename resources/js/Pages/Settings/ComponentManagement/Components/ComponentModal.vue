<template>
    <ArtworkBaseModal
        v-if="show"
        @close="closeModal"
        :title="isCreateMode ? $t('Create a new component') : $t('Edit component')"
        :description="isCreateMode ? $t('Here you can create a new component.') : $t('Here you can edit the component {0}.', [componentToEdit?.name])"
    >
        <div class="">
            <div class="grid grid-cols-1 gap-6">
                <!-- Typ-Auswahl (nur im Create) (neu) -->
                <ArtworkBaseListbox
                    v-if="isCreateMode"
                    v-model="selectedType"
                    :items="typesArray"
                    :option-label="(o) => `${$t(o.name)}`"
                    option-key="name"
                    by="name"
                    placeholder="Component Layout"
                    :enable-search="true"
                    :search-keys="['name']"
                />

                <!-- Name -->
                <div>
                    <BaseInput
                        :label="$t('Name of the component')"
                        v-model="componentName"
                        id="componentName"
                    />
                    <span v-if="helpTexts.name" class="mt-1 text-xs text-red-500">
            {{ helpTexts.name }}
          </span>
                </div>

                <!-- Basisdaten (aus availableFields / data) -->
                <div v-if="!componentToEdit?.special" class="grid grid-cols-1 gap-5">
                    <div class="text-sm font-semibold text-gray-900">
                        {{ isCreateMode ? $t('Enter basic data') : $t('Edit basic data') }}
                    </div>

                    <!-- Titel -->
                    <div v-if="'title' in textData">
                        <BaseInput :label="$t('Title')" v-model="textData.title" id="title" />
                    </div>

                    <!-- Label -->
                    <div v-if="'label' in textData">
                        <BaseInput :label="$t('label')" v-model="textData.label" id="label" />
                    </div>

                    <!-- Text -->
                    <div v-if="'text' in textData">
                        <BaseInput :label="$t('Text')" v-model="textData.text" id="text" />
                    </div>

                    <!-- Placeholder -->
                    <div v-if="'placeholder' in textData">
                        <BaseInput :label="$t('Placeholder')" v-model="textData.placeholder" id="placeholder" />
                    </div>

                    <!-- Placeholder Label -->
                    <div v-if="'placeholder_label' in textData">
                        <BaseInput :label="$t('Placeholder label')" v-model="textData.placeholder_label" id="placeholder_label" />
                    </div>

                    <!-- Placeholder URL -->
                    <div v-if="'placeholder_url' in textData">
                        <BaseInput :label="$t('Placeholder URL')" v-model="textData.placeholder_url" id="placeholder_url" />
                    </div>

                    <!-- Max Items -->
                    <div v-if="'max_items' in textData">
                        <label for="max_items" class="text-xs text-gray-500">
                            {{ $t('Max links') }}
                        </label>
                        <input
                            id="max_items"
                            type="number"
                            min="1"
                            max="200"
                            v-model.number="textData.max_items"
                            class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                        />
                    </div>

                    <!-- Höhe (Range) -->
                    <div v-if="'height' in textData">
                        <label for="height" class="text-xs text-gray-500">
                            {{ $t('Height - ({0} pixels)', [textData.height]) }}
                        </label>
                        <input
                            id="height"
                            type="range"
                            v-model.number="textData.height"
                            min="0"
                            max="150"
                            class="mt-2 h-2 w-full rounded-lg accent-indigo-600"
                        />
                    </div>

                    <!-- Titelgröße (Range) -->
                    <div v-if="'title_size' in textData">
                        <label for="title_size" class="text-xs text-gray-500">
                            {{ $t('Font Size - ({0} pixels)', [textData.title_size]) }}
                        </label>
                        <input
                            id="title_size"
                            type="range"
                            v-model.number="textData.title_size"
                            min="10"
                            max="35"
                            class="mt-2 h-2 w-full rounded-lg accent-indigo-600"
                        />
                    </div>

                    <!-- Show line -->
                    <label v-if="'showLine' in textData" class="flex items-center gap-2">
                        <input v-model="textData.showLine" type="checkbox" class="input-checklist" />
                        <span class="text-sm text-gray-700">{{ $t('Show a separator line') }}</span>
                    </label>

                    <!-- Checked (default) -->
                    <label v-if="'checked' in textData" class="flex items-center gap-2">
                        <input v-model="textData.checked" type="checkbox" class="input-checklist" />
                        <span class="text-sm text-gray-700">{{ $t('This checkbox is activated by default') }}</span>
                    </label>

                    <!-- Optionen -->
                    <div v-if="Array.isArray(textData.options)">
                        <div class="grid grid-cols-1 gap-3">
                            <div
                                v-for="(opt, idx) in textData.options"
                                :key="`opt-${idx}`"
                                class="rounded-lg border border-gray-200 p-3 bg-white"
                            >
                                <BaseInput
                                    v-model="textData.options[idx].value"
                                    :label="'Option (' + (idx + 1) + ')'"
                                    :id="`option-${idx}`"
                                />
                                <div class="mt-1 text-right">
                                    <button
                                        v-if="idx !== 0"
                                        type="button"
                                        class="text-xs text-indigo-600 hover:text-indigo-700 underline underline-offset-2"
                                        @click="removeOption(idx)"
                                    >
                                        {{ $t('Remove') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 flex items-center justify-end">
                            <button
                                class="text-xs text-indigo-600 hover:text-indigo-700 underline underline-offset-2"
                                type="button"
                                @click="addMoreOneOption"
                            >
                                {{ $t('Add another option') }}
                            </button>
                        </div>

                        <!-- Default Option Auswahl -->
                        <div v-if="textData.options[0]?.value" class="mt-3">
                            <ArtworkBaseListbox
                                v-model="textData.selected"
                                :items="textData.options"
                                :option-label="(o) => `${o.value}`"
                                option-key="value"
                                by="value"
                                placeholder="Standard Option"
                                :enable-search="true"
                                :search-keys="['name']"
                            />

                            <div class="mt-3 text-right">
                                <button
                                    class="text-xs text-indigo-600 hover:text-indigo-700 underline underline-offset-2"
                                    type="button"
                                    @click="textData.selected = ''"
                                >
                                    {{ $t('Remove default option') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Berechtigungen -->
                <div v-if="isQualifiedForPermissions" class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 text-sm font-bold text-gray-900">
                        {{ $t('Configure component permissions') }}
                    </div>

                    <!-- Everyone can see & edit -->
                    <label class="flex items-center gap-2">
                        <input
                            type="radio"
                            value="allSeeAndEdit"
                            v-model="modulePermissions.permission_type"
                        />
                        <span class="text-sm text-gray-700">
              {{ $t('Everyone can see and edit') }}
            </span>
                    </label>

                    <!-- Everyone can see, some can edit -->
                    <div class="mt-3">
                        <label class="flex items-center gap-2">
                            <input
                                type="radio"
                                value="allSeeSomeEdit"
                                v-model="modulePermissions.permission_type"
                            />
                            <span class="text-sm text-gray-700">
                {{ $t('Everyone can see, but editing is just allowed for:') }}
              </span>
                        </label>

                        <div v-if="modulePermissions.permission_type === 'allSeeSomeEdit'" class="mt-3">
                            <BaseInput
                                :label="$t('Search for teams and/or users')"
                                v-model="userAndTeamsQuery"
                                id="searchUsersAndTeams_1"
                                @input="searchUsersAndTeams"
                            />

                            <!-- Search dropdown -->
                            <div
                                v-if="hasSearchResults"
                                class="absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded-lg border border-gray-200 bg-white text-sm shadow-lg"
                            >
                                <div class="divide-y divide-gray-100">
                                    <div
                                        v-for="(user, idx) in userAndTeamsSearchResult.users"
                                        :key="'u_' + idx"
                                        class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                        @click="addUser(user)"
                                    >
                                        <img :src="user.profile_photo_url" :alt="user.name" class="h-8 w-8 rounded-full object-cover" />
                                        <span class="truncate">{{ user.first_name }} {{ user.last_name }}</span>
                                    </div>
                                    <div
                                        v-for="(department, idx) in userAndTeamsSearchResult.departments"
                                        :key="'d_' + idx"
                                        class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                        @click="addDepartment(department)"
                                    >
                                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-8 w-8" />
                                        <span class="truncate">{{ department.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Auswahl-Liste -->
                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="user in modulePermissions.users"
                                    :key="'sel_u_' + user.id"
                                    class="flex items-center justify-between rounded-lg border-b border-gray-100 pb-2"
                                >
                                    <div class="flex items-center gap-3">
                                        <img class="h-10 w-10 rounded-full" :src="user.profile_photo_url" alt="" />
                                        <span class="text-sm font-medium text-gray-800">
                      {{ user.first_name }} {{ user.last_name }}
                    </span>
                                    </div>
                                    <button type="button" @click="removeUser(user)" class="text-gray-400 hover:text-red-500">
                                        <IconX class="h-5 w-5" />
                                    </button>
                                </div>

                                <div
                                    v-for="department in modulePermissions.departments"
                                    :key="'sel_d_' + department.id"
                                    class="flex items-center justify-between rounded-lg border-b border-gray-100 pb-2"
                                >
                                    <div class="flex items-center gap-3">
                                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-10 w-10" />
                                        <span class="text-sm font-medium text-gray-800">{{ department.name }}</span>
                                    </div>
                                    <button type="button" @click="removeDepartment(department)" class="text-gray-400 hover:text-red-500">
                                        <IconX class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Some see & some edit -->
                    <div class="mt-4">
                        <label class="flex items-center gap-2">
                            <input
                                type="radio"
                                value="someSeeSomeEdit"
                                v-model="modulePermissions.permission_type"
                            />
                            <span class="text-sm text-gray-700">
                {{ $t('Sehen darf nur:') }}
              </span>
                        </label>

                        <div v-if="modulePermissions.permission_type === 'someSeeSomeEdit'" class="mt-3 relative">
                            <BaseInput
                                id="searchUsersAndTeams_2"
                                :label="$t('Search for teams and/or users')"
                                v-model="userAndTeamsQuery"
                                @input="searchUsersAndTeams"
                            />

                            <!-- Search dropdown -->
                            <div
                                v-if="hasSearchResults"
                                class="absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded-lg border border-gray-200 bg-white text-sm shadow-lg"
                            >
                                <div class="divide-y divide-gray-100">
                                    <div
                                        v-for="(user, idx) in userAndTeamsSearchResult.users"
                                        :key="'su_' + idx"
                                        class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                        @click="addUser(user)"
                                    >
                                        <img :src="user.profile_photo_url" :alt="user.name" class="h-8 w-8 rounded-full object-cover" />
                                        <span class="truncate">{{ user.first_name }} {{ user.last_name }}</span>
                                    </div>
                                    <div
                                        v-for="(department, idx) in userAndTeamsSearchResult.departments"
                                        :key="'sd_' + idx"
                                        class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center gap-2"
                                        @click="addDepartment(department)"
                                    >
                                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-8 w-8" />
                                        <span class="truncate">{{ department.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Auswahl-Liste mit Write-Checkbox -->
                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="user in modulePermissions.users"
                                    :key="'ss_u_' + user.id"
                                    class="flex items-center justify-between border-b border-gray-100 pb-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <img class="h-10 w-10 rounded-full" :src="user.profile_photo_url" alt="" />
                                        <span class="text-sm font-medium text-gray-800">
                      {{ user.first_name }} {{ user.last_name }}
                    </span>
                                        <label class="ml-2 flex items-center gap-2">
                                            <input v-model="(user.pivot || (user.pivot = { can_write: false })).can_write" type="checkbox" class="input-checklist" />
                                            <span :class="[user.pivot?.can_write ? 'text-indigo-600 font-semibold' : 'text-gray-500']" class="text-sm">
                        {{ $t('Write permission') }}
                      </span>
                                        </label>
                                    </div>
                                    <button type="button" @click="removeUser(user)" class="text-gray-400 hover:text-red-500">
                                        <IconX class="h-5 w-5" />
                                    </button>
                                </div>

                                <div
                                    v-for="department in modulePermissions.departments"
                                    :key="'ss_d_' + department.id"
                                    class="flex items-center justify-between border-b border-gray-100 pb-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <TeamIconCollection :iconName="department.svg_name" :alt="department.name" class="h-10 w-10" />
                                        <span class="text-sm font-medium text-gray-800">{{ department.name }}</span>
                                        <label class="ml-2 flex items-center gap-2">
                                            <input v-model="(department.pivot || (department.pivot = { can_write: false })).can_write" type="checkbox" class="input-checklist" />
                                            <span :class="[department.pivot?.can_write ? 'text-indigo-600 font-semibold' : 'text-gray-500']" class="text-sm">
                        {{ $t('Write permission') }}
                      </span>
                                        </label>
                                    </div>
                                    <button type="button" @click="removeDepartment(department)" class="text-gray-400 hover:text-red-500">
                                        <IconX class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hinweis, wenn keine Permissions konfigurierbar -->
                <div
                    v-else-if="!isQualifiedForPermissions && componentToEdit?.type !== 'Title'"
                    class="text-xs text-gray-500"
                >
                    {{ $t('The permissions for this component are administered via the user settings and the project.') }}
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex items-center justify-between">
                <BaseUIButton
                    @click="updateOrSaveComponent"
                    :label="isCreateMode ? $t('Create') : $t('Save')" is-add-button
                />
                <button class="text-sm text-gray-500 hover:text-gray-700" @click="closeModal">
                    {{ $t('No, not really') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import {
    Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
} from '@headlessui/vue'
import { IconChevronDown, IconCircleCheck, IconX } from '@tabler/icons-vue'

import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";

defineOptions({ name: 'ComponentModal' })

/* Props & Emits */
const props = defineProps({
    show: { type: Boolean, required: true },
    mode: { type: String, default: 'create' }, // 'create' | 'edit'
    tabComponentTypes: { type: Object, default: () => ({}) },
    componentToEdit: { type: Object, default: null }, // Legacy: for backward compatibility
    componentId: { type: [Number, String], default: null } // New: for lazy loading
})
const emit = defineEmits(['close'])

/* Helpers */
const isCreateMode = computed(() => props.mode === 'create')
const loadedComponent = ref(null) // Stores the lazy-loaded component data
const componentToEdit = computed(() => loadedComponent.value || props.componentToEdit)
const typesArray = computed(() => {
    // tabComponentTypes kann Map oder Objekt sein; wir nehmen Objekt -> Array
    if (!props.tabComponentTypes) return []
    return Object.values(props.tabComponentTypes)
})

/* Auswahl Typ (bei Create) */
const selectedType = ref(
    isCreateMode.value ? props.tabComponentTypes?.['TextField'] ?? typesArray.value[0] ?? null : null
)

/* Name */
const componentName = ref('')
const helpTexts = reactive({ name: null })

/* Text-/Konfig-Daten (Fields) */
const textData = reactive({})

/* Permissions */
const modulePermissions = reactive({
    permission_type: 'allSeeAndEdit',
    users: [],
    departments: []
})

/* Lazy Loading Logic */
onMounted(async () => {
    if (isCreateMode.value) {
        // Initialize for create mode
        componentName.value = ''
        textDataResetTo(selectedType.value?.availableFields ?? {})
        modulePermissions.permission_type = 'allSeeAndEdit'
        modulePermissions.users = []
        modulePermissions.departments = []
    } else if (props.componentId) {
        // Lazy load component data via API
        try {
            const { data } = await axios.get(route('component.show', { component: props.componentId }))
            loadedComponent.value = data

            // Initialize form with loaded data
            componentName.value = data.name ?? ''
            textDataResetTo(data.data ?? {})
            modulePermissions.permission_type = data.permission_type ?? 'allSeeAndEdit'
            modulePermissions.users = deepClone(data.users ?? [])
            modulePermissions.departments = deepClone(data.departments ?? [])
        } catch (error) {
            console.error('Failed to load component:', error)
        }
    } else if (props.componentToEdit) {
        // Legacy: use passed component object
        componentName.value = props.componentToEdit.name ?? ''
        textDataResetTo(props.componentToEdit.data ?? {})
        modulePermissions.permission_type = props.componentToEdit.permission_type ?? 'allSeeAndEdit'
        modulePermissions.users = deepClone(props.componentToEdit.users ?? [])
        modulePermissions.departments = deepClone(props.componentToEdit.departments ?? [])
    }
})

/* Permission-Eignung */
const isQualifiedForPermissions = computed(() => {
    // Logik wie vorher, aber defensiv
    const excluded = ['Title', 'SeparatorComponent', 'ShiftTab', 'BudgetTab', 'CalendarTab']
    if (isCreateMode.value) {
        const name = selectedType.value?.name
        return name ? !excluded.includes(name) : false
    }
    const t = componentToEdit.value?.type
    return t ? !excluded.includes(t) : false
})

/* Suche User/Teams */
const userAndTeamsQuery = ref('')
const userAndTeamsSearchResult = reactive({ users: [], departments: [] })
const hasSearchResults = computed(() =>
    (userAndTeamsSearchResult.users?.length > 0 || userAndTeamsSearchResult.departments?.length > 0) &&
    userAndTeamsQuery.value.length > 0
)

function resetSearch() {
    userAndTeamsQuery.value = ''
    userAndTeamsSearchResult.users = []
    userAndTeamsSearchResult.departments = []
}

function findModulePermissionsUserIndex(id) {
    return modulePermissions.users.findIndex(u => u.id === id)
}
function findModulePermissionsDepartmentIndex(id) {
    return modulePermissions.departments.findIndex(d => d.id === id)
}

function addUser(user) {
    if (findModulePermissionsUserIndex(user.id) < 0) {
        modulePermissions.users.push(user)
    }
    resetSearch()
}
function removeUser(user) {
    const idx = findModulePermissionsUserIndex(user.id)
    if (idx >= 0) modulePermissions.users.splice(idx, 1)
}
function addDepartment(dep) {
    if (findModulePermissionsDepartmentIndex(dep.id) < 0) {
        modulePermissions.departments.push(dep)
    }
    resetSearch()
}
function removeDepartment(dep) {
    const idx = findModulePermissionsDepartmentIndex(dep.id)
    if (idx >= 0) modulePermissions.departments.splice(idx, 1)
}

async function searchUsersAndTeams() {
    const q = userAndTeamsQuery.value?.trim()
    if (!q) {
        resetSearch()
        return
    }
    try {
        const { data } = await axios.get(route('users_departments.search'), { params: { query: q } })
        // Pivot für someSeeSomeEdit hinzufügen (booleans für can_write)
        if (modulePermissions.permission_type === 'someSeeSomeEdit') {
            for (const u of data.users || []) u.pivot = u.pivot ?? { can_write: false }
            for (const d of data.departments || []) d.pivot = d.pivot ?? { can_write: false }
        }
        userAndTeamsSearchResult.users = data.users ?? []
        userAndTeamsSearchResult.departments = data.departments ?? []
    } catch (e) {
        // optional: Fehlermeldung loggen/anzeigen
        userAndTeamsSearchResult.users = []
        userAndTeamsSearchResult.departments = []
    }
}

/* Optionen */
function addMoreOneOption() {
    if (!Array.isArray(textData.options)) textData.options = []
    textData.options.push({ value: '' })
}
function removeOption(index) {
    if (Array.isArray(textData.options)) textData.options.splice(index, 1)
}

/* Watch: Typwechsel -> Fields neu setzen */
watch(selectedType, (val) => {
    if (!isCreateMode.value || !val) return
    textDataResetTo(val?.availableFields ?? {})
})

/* Actions */
function closeModal() {
    emit('close')
}

function updateOrSaveComponent() {
    helpTexts.name = null
    if (!componentName.value?.trim()) {
        helpTexts.name = (typeof $t === 'function' ? $t('Please enter a name.') : 'Please enter a name.')
        return
    }

    const qualified = isQualifiedForPermissions.value
    const payload = {
        name: componentName.value,
        data: deepClone(textData),
        permission_type: qualified ? modulePermissions.permission_type : null,
        users: [],
        departments: []
    }

    if (isCreateMode.value) {
        payload.type = selectedType.value?.name
    }

    if (qualified) {
        for (const u of modulePermissions.users) {
            payload.users.push({ user_id: u.id, can_write: u.pivot ? !!u.pivot.can_write : null })
        }
        for (const d of modulePermissions.departments) {
            payload.departments.push({ department_id: d.id, can_write: d.pivot ? !!d.pivot.can_write : null })
        }
    }

    const options = {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => closeModal()
    }

    if (isCreateMode.value) {
        router.post(route('component.store'), payload, options)
    } else {
        const componentId = props.componentId || componentToEdit.value?.id
        router.patch(route('component.update', { component: componentId }), payload, options)
    }
}

/* Utils */
function deepClone(obj) {
    try {
        return JSON.parse(JSON.stringify(obj ?? {}))
    } catch {
        return {}
    }
}
function textDataResetTo(fields) {
    // hard reset von textData reactive → setze bekannte Properties
    const next = deepClone(fields)
    for (const key of Object.keys(textData)) delete textData[key]
    for (const [k, v] of Object.entries(next)) textData[k] = v
}
</script>
