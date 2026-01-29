<template>
    <ArtworkBaseModal
        @close="handleClose(false)"
        :title="$t('Invite users')"
        :description="$t('You can invite several users with the same user permissions and team memberships at once.')"
    >
        <div class="mx-4">
            <!-- Emails -->
            <div class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <div class="col-span-4">
                        <BaseInput
                            id="email"
                            v-model="emailInput"
                            :label="$t('E-Mail*')"
                            required
                            @keyup.enter.prevent="addEmailsFromInput"
                            @blur="addEmailsFromInput"
                            autocomplete="off"
                            placeholder="max@example.com, anna@firma.de …"
                        />
                    </div>
                    <div class="col-span-1 flex items-center justify-center">
                        <button
                            :disabled="!emailInput"
                            @click="addEmailsFromInput"
                            class="rounded-full mt-1 inline-flex items-center p-2 text-white transition
                     disabled:opacity-50 disabled:cursor-not-allowed
                     bg-blue-600 hover:bg-blue-700 focus-visible:outline focus-visible:outline-2
                     focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            aria-label="Add email"
                        >
                            <CheckIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <JetInputError :message="form.error" class="mt-2" />

                <ul v-if="showInvalidEmailErrorText" class="mt-2">
                    <li class="text-red-600 text-xs">
                        {{ $t('This is not a valid e-mail address.') }}
                    </li>
                </ul>

                <p v-if="helpText" class="text-red-600 text-xs mt-2">{{ helpText }}</p>

                <!-- Chips -->
                <div class="mt-3 flex flex-wrap gap-2">
          <span
              v-for="(email, i) in form.user_emails"
              :key="email + i"
              class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-sm text-zinc-800"
          >
            {{ email }}
            <button
                type="button"
                @click="removeEmail(i)"
                class="rounded p-0.5 text-zinc-500 hover:text-red-600 hover:bg-red-50 transition"
                :aria-label="$t('Remove email from invitation')"
            >
              <XCircleIcon class="h-4 w-4" />
            </button>
          </span>
                </div>

                <!-- Server errors -->
                <ul class="mt-4">
                    <li class="text-red-600 text-xs" v-for="(error, key) in errors" :key="key">{{ error }}</li>
                </ul>
            </div>

            <!-- Teams -->
            <div class="mt-6">
                <Disclosure as="div">
                    <div class="mb-3 flex items-center gap-3">
                        <DisclosureButton>
                            <AddButtonSmall :text="$t('Assign to teams')" />
                        </DisclosureButton>

                        <div v-if="page.props.show_hints && selectedDepartments.length === 0" class="flex items-center gap-1 text-zinc-500">
                            <SvgCollection svgName="arrowLeft" class="h-4 w-4" />
                            <span class="text-xs">{{ $t('Assign users directly to your teams') }}</span>
                        </div>

                        <div class="ml-auto flex -space-x-3" v-if="selectedDepartments.length">
                            <TeamIconCollection
                                v-for="(t, idx) in selectedDepartments.slice(0, 4)"
                                :key="t.id + '-' + idx"
                                class="h-9 w-9 rounded-full ring-2 ring-white"
                                :iconName="t.svg_name"
                            />
                            <div
                                v-if="selectedDepartments.length > 4"
                                class="h-9 w-9 rounded-full ring-2 ring-white bg-zinc-800 text-white text-xs flex items-center justify-center"
                            >
                                +{{ selectedDepartments.length - 4 }}
                            </div>
                        </div>
                    </div>

                    <transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <DisclosurePanel
                            class="relative z-30 max-h-56 w-80 overflow-y-auto rounded-xl bg-white p-2 ring-1 ring-zinc-200 shadow-lg"
                        >
                            <div v-if="deptLocal.length === 0" class="px-3 py-2 text-sm text-zinc-500">
                                {{ $t('No teams available for assignment') }}
                            </div>

                            <label
                                v-for="team in deptLocal"
                                :key="team.id"
                                class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-zinc-50"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                                    v-model="team.checked"
                                    @change="onTeamToggle(team)"
                                />
                                <TeamIconCollection class="h-7 w-7 rounded-full ring-2 ring-white" :iconName="team.svg_name" />
                                <span :class="team.checked ? 'text-zinc-900 font-medium' : 'text-zinc-600'">{{ team.name }}</span>
                            </label>
                        </DisclosurePanel>
                    </transition>
                </Disclosure>
            </div>

            <!-- Roles -->
            <div class="mt-8">
                <h3 class="mb-4 text-base font-semibold text-zinc-900">{{ $t('Define user permissions') }}</h3>

                <div class="space-y-2">
                    <div
                        v-for="role in rolesLocal"
                        :key="role.name"
                        class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2"
                    >
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                                v-model="role.checked"
                                @change="onRoleToggle(role)"
                                :name="role.translation_key"
                                :id="role.translation_key"
                            />
                            <span class="text-zinc-900">{{ $t(role.translation_key) }}</span>
                        </label>
                        <ToolTipDefault top :tooltip-text="$t(role.tooltipKey)" />
                    </div>
                </div>
            </div>

            <!-- Presets -->
            <div v-if="!form.roles.includes('artwork admin')" class="mt-8">
                <button
                    class="mb-2 flex w-full items-center justify-between rounded-lg bg-white px-3 py-2 text-left text-sm font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50"
                    @click="showPresets = !showPresets"
                    type="button"
                >
                    <span>{{ $t('Permission presets') }}</span>
                    <component :is="showPresets ? ChevronUpIcon : ChevronDownIcon" class="h-4 w-4" />
                </button>

                <div v-if="showPresets" class="space-y-2">
                    <div
                        v-if="presetsLocal.length > 0"
                        v-for="preset in presetsLocal"
                        :key="preset.id"
                        class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2"
                    >
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                                v-model="preset.checked"
                                @change="applyPreset(preset)"
                                :id="`preset-${preset.id}`"
                                :name="preset.name"
                            />
                            <span class="text-zinc-900">{{ preset.name }}</span>
                        </label>
                    </div>
                    <div v-else class="text-sm text-zinc-500">
                        {{ $t('No permission presets have been created yet.') }}
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div v-if="!form.roles.includes('artwork admin')" class="mt-8">
                <button
                    class="mb-2 flex w-full items-center justify-between rounded-lg bg-white px-3 py-2 text-left text-sm font-medium text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50"
                    @click="showUserPermissions = !showUserPermissions"
                    type="button"
                >
                    <span>{{ $t('User permissions') }}</span>
                    <component :is="showUserPermissions ? ChevronUpIcon : ChevronDownIcon" class="h-4 w-4" />
                </button>

                <div v-if="showUserPermissions" class="space-y-6">
                    <!-- Search within permissions -->
                    <div class="relative">
                        <input
                            v-model="permQuery"
                            type="text"
                            :placeholder="$t('Search permissions…')"
                            class="h-9 w-full rounded-lg border border-zinc-300 bg-white px-9 text-sm text-zinc-900 placeholder:text-zinc-400 outline-none ring-0 transition focus:border-zinc-400 focus:bg-zinc-50"
                        />
                        <SearchIcon class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                        <button
                            v-if="permQuery"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-700"
                            @click="permQuery = ''"
                            aria-label="Clear permission search"
                        >
                            <XIcon class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        v-for="(group, groupName) in filteredGroupedPermissions"
                        :key="groupName"
                        v-show="group.shown && group.permissions.length"
                        class="rounded-xl border border-zinc-200 bg-white p-3"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-zinc-600">{{ $t(groupName) }}</h4>
                            <button
                                class="text-xs underline text-blue-600 hover:text-blue-700"
                                @click="toggleWholeGroup(group)"
                            >
                                {{
                                    group.permissions.some(p => p.checked)
                                        ? $t('Deselect all')
                                        : $t('Select all')
                                }}
                            </button>
                        </div>

                        <div class="divide-y divide-dashed divide-zinc-200">
                            <div
                                v-for="perm in group.permissions"
                                :key="perm.name"
                                class="flex items-center justify-between py-2"
                            >
                                <label class="flex items-center gap-3 text-sm">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600"
                                        v-model="perm.checked"
                                        @change="onPermissionToggle(perm)"
                                        :id="perm.translation_key"
                                        :name="perm.translation_key"
                                    />
                                    <span :class="perm.checked ? 'text-zinc-900' : 'text-zinc-600'">
                    {{ $t(perm.translation_key) }}
                  </span>
                                </label>
                                <ToolTipDefault top :tooltip-text="$t(perm.tooltipKey)" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="w-full text-center">
                <FormButton
                    class="mt-6"
                    :text="$t('Invite')"
                    :disabled="form.processing || form.user_emails.length === 0"
                    @click="submit"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, reactive, getCurrentInstance } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import AddButtonSmall from '@/Layouts/Components/General/Buttons/AddButtonSmall.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import ToolTipDefault from '@/Components/ToolTips/ToolTipDefault.vue'
import SvgCollection from '@/Layouts/Components/SvgCollection.vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { CheckIcon, ChevronDownIcon, ChevronUpIcon, XCircleIcon } from '@heroicons/vue/solid'
import { XIcon, SearchIcon } from '@heroicons/vue/outline'

/* Props */
const props = defineProps({
    show: Boolean,
    closeModal: Function,
    all_permissions: { type: Object, required: true }, // { groupName: [ {id, name, translation_key, tooltipKey}, ... ] }
    departments: { type: Array, default: () => [] },   // [{id, name, svg_name}]
    roles: { type: Array, default: () => [] },         // [{name, translation_key, tooltipKey}]
    permission_presets: { type: Array, default: () => [] }, // [{id, name, permissions:[ids]}]
    users: { type: Array, default: () => [] },         // for dedupe email check
    invitedUsers: { type: Array, default: () => [] }
})

/* i18n helper */
const { proxy } = getCurrentInstance()
const $t = (k, v) => proxy.$t(k, v)

/* Page */
const page = usePage()

/* UI state */
const emailInput = ref('')
const helpText = ref('')
const showInvalidEmailErrorText = ref(false)

const showPresets = ref(true)
const showUserPermissions = ref(true)
const permQuery = ref('')

/* Form */
const form = useForm({
    user_emails: [],
    permissions: [],
    departments: [],
    roles: []
})

/* Errors */
const errors = computed(() => page.props?.errors || {})

/* Local copies to avoid mutating props */
const deptLocal = reactive((props.departments || []).map(d => ({ ...d, checked: false })))
const rolesLocal = reactive((props.roles || []).map(r => ({ ...r, checked: false })))
const presetsLocal = reactive((props.permission_presets || []).map(p => ({ ...p, checked: false })))

/* Selected departments (derived) */
const selectedDepartments = computed(() => deptLocal.filter(d => d.checked))

/* Build permission map and grouped list with checked flags */
const sageEnabled = computed(() => !!page.props?.sageApiEnabled)

/* Flatten permissions into a map for lookups (id -> name) */
const permissionIdToName = computed(() => {
    const map = new Map()
    Object.values(props.all_permissions || {}).forEach(list => {
        list.forEach(p => map.set(p.id, p.name))
    })
    return map
})

/* Core grouped permissions with 'checked' bound to form.permissions */
const groupedPermissions = computed(() => {
    const groups = {}
    for (const [groupName, list] of Object.entries(props.all_permissions || {})) {
        const perms = []
        list.forEach(p => {
            if (
                (p.name === 'can view and delete sage100-api-data' ||
                 p.name === 'can view project sage data' ||
                 p.name === 'can view global sage data') &&
                !sageEnabled.value
            ) {
                return
            }
            perms.push({
                ...p,
                checked: form.permissions.includes(p.name)
            })
        })
        groups[groupName] = {
            shown: perms.length > 0,
            permissions: perms
        }
    }
    return groups
})

/* Filter by query inside permissions */
const filteredGroupedPermissions = computed(() => {
    const q = permQuery.value.trim().toLowerCase()
    if (!q) return groupedPermissions.value
    const out = {}
    for (const [g, obj] of Object.entries(groupedPermissions.value)) {
        const filtered = obj.permissions.filter(p =>
            (p.translation_key || p.name || '').toLowerCase().includes(q)
        )
        out[g] = { shown: filtered.length > 0, permissions: filtered }
    }
    return out
})

/* Methods */
// Add emails (supports comma/space separation)
function addEmailsFromInput () {
    if (!emailInput.value) return
    const raw = emailInput.value
    const parsed = splitEmails(raw)
    const { valid, invalid, duplicates, existing } = validateEmails(parsed)

    // merge valid
    valid.forEach(e => {
        if (!form.user_emails.includes(e)) form.user_emails.push(e)
    })

    // build help messages
    if (invalid.length) {
        showInvalidEmailErrorText.value = true
    } else {
        showInvalidEmailErrorText.value = false
    }

    const hints = []
    if (invalid.length) hints.push($t('This is not a valid e-mail address.'))
    if (duplicates.length) hints.push($t('Duplicate address skipped: {0}', [duplicates.join(', ')]))
    if (existing.length) hints.push($t('This e-mail address already exists in the system. {0}', [existing.join(', ')]))
    helpText.value = hints.join(' ')

    emailInput.value = ''
}

function removeEmail (i) {
    form.user_emails.splice(i, 1)
}

function splitEmails (str) {
    return str
        .split(/[\s,;]+/)
        .map(s => s.trim())
        .filter(Boolean)
}

function validateEmails (list) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    const valid = []
    const invalid = []
    const duplicates = []
    const existing = []

    const known = new Set(form.user_emails)
    const systemEmails = new Set((props.users || []).map(u => u.email))

    list.forEach(e => {
        if (!re.test(e)) {
            invalid.push(e)
            return
        }
        if (known.has(e) || valid.includes(e)) {
            duplicates.push(e)
            return
        }
        if (systemEmails.has(e)) {
            existing.push(e)
            return
        }
        valid.push(e)
    })
    return { valid, invalid, duplicates, existing }
}

/* Teams */
function onTeamToggle (team) {
    if (team.checked) {
        if (!form.departments.find(t => t.id === team.id)) {
            form.departments.push({ id: team.id, name: team.name, svg_name: team.svg_name })
        }
    } else {
        form.departments = form.departments.filter(t => t.id !== team.id)
    }
}

/* Roles */
function onRoleToggle (role) {
    if (role.checked) {
        if (!form.roles.includes(role.name)) form.roles.push(role.name)
    } else {
        form.roles = form.roles.filter(r => r !== role.name)
    }
}

/* Presets */
function applyPreset (preset) {
    const namesFromIds = preset.permissions
        .map(id => permissionIdToName.value.get(id))
        .filter(Boolean)

    if (preset.checked) {
        // add all preset permissions
        form.permissions = Array.from(new Set([...form.permissions, ...namesFromIds]))
    } else {
        // remove all preset permissions
        form.permissions = form.permissions.filter(n => !namesFromIds.includes(n))
    }
}

/* Permissions */
function onPermissionToggle (perm) {
    if (perm.checked) {
        if (!form.permissions.includes(perm.name)) form.permissions.push(perm.name)
    } else {
        form.permissions = form.permissions.filter(n => n !== perm.name)
    }
}

function toggleWholeGroup (group) {
    const someChecked = group.permissions.some(p => p.checked)
    if (someChecked) {
        // uncheck all
        const names = group.permissions.map(p => p.name)
        form.permissions = form.permissions.filter(n => !names.includes(n))
    } else {
        // check all
        const toAdd = group.permissions.map(p => p.name)
        form.permissions = Array.from(new Set([...form.permissions, ...toAdd]))
    }
}

/* Submit */
function submit () {
    form.post(route('invitations.store'), {
        onSuccess: () => {
            resetAll()
            handleClose(true)
        }
    })
}

/* Reset & close */
function resetAll () {
    emailInput.value = ''
    helpText.value = ''
    showInvalidEmailErrorText.value = false
    permQuery.value = ''

    form.user_emails = []
    form.permissions = []
    form.departments = []
    form.roles = []

    deptLocal.forEach(d => (d.checked = false))
    rolesLocal.forEach(r => (r.checked = false))
    presetsLocal.forEach(p => (p.checked = false))
}

function handleClose (bool) {
    resetAll()
    props.closeModal?.(bool)
}
</script>
