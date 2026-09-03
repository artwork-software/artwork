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
                     disabled:bg-border-strong disabled:cursor-not-allowed
                     bg-accent-600 hover:bg-accent-700 focus-visible:outline focus-visible:outline-2
                     focus-visible:outline-offset-2 focus-visible:outline-accent-600"
                            aria-label="Add email"
                        >
                            <IconCheck class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <JetInputError :message="form.error" class="mt-2" />

                <ul v-if="showInvalidEmailErrorText" class="mt-2">
                    <li class="text-danger text-xs">
                        {{ $t('This is not a valid e-mail address.') }}
                    </li>
                </ul>

                <p v-if="helpText" class="text-danger text-xs mt-2">{{ helpText }}</p>

                <!-- Chips -->
                <div class="mt-3 flex flex-wrap gap-2">
          <span
              v-for="(email, i) in form.user_emails"
              :key="email + i"
              class="inline-flex items-center gap-2 rounded-full border border-border-subtle bg-surface-sunken px-3 py-1 text-sm text-text"
          >
            {{ email }}
            <button
                type="button"
                @click="removeEmail(i)"
                class="rounded p-0.5 text-text-subtle hover:text-danger hover:bg-danger-surface transition"
                :aria-label="$t('Remove email from invitation')"
            >
              <IconCircleX class="h-4 w-4" />
            </button>
          </span>
                </div>

                <!-- Server errors -->
                <ul class="mt-4">
                    <li class="text-danger text-xs" v-for="(error, key) in errors" :key="key">{{ error }}</li>
                </ul>
            </div>

            <!-- Teams -->
            <div class="mt-6">
                <Disclosure as="div">
                    <div class="mb-3 flex items-center gap-3">
                        <DisclosureButton>
                            <AddButtonSmall :text="$t('Assign to teams')" />
                        </DisclosureButton>

                        <div v-if="page.props.show_hints && selectedDepartments.length === 0" class="flex items-center gap-1 text-text-subtle">
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
                                class="h-9 w-9 rounded-full ring-2 ring-white bg-surface-inverse text-text-inverse text-xs flex items-center justify-center"
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
                            class="relative z-30 max-h-56 w-80 overflow-y-auto rounded-xl bg-white p-2 ring-1 ring-border-subtle shadow-lg"
                        >
                            <div v-if="deptLocal.length === 0" class="px-3 py-2 text-sm text-text-subtle">
                                {{ $t('No teams available for assignment') }}
                            </div>

                            <label
                                v-for="team in deptLocal"
                                :key="team.id"
                                class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-surface-sunken"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600"
                                    v-model="team.checked"
                                    @change="onTeamToggle(team)"
                                />
                                <TeamIconCollection class="h-7 w-7 rounded-full ring-2 ring-white" :iconName="team.svg_name" />
                                <span :class="team.checked ? 'text-text font-medium' : 'text-text-muted'">{{ team.name }}</span>
                            </label>
                        </DisclosurePanel>
                    </transition>
                </Disclosure>
            </div>

            <!-- Roles -->
            <div class="mt-8">
                <h3 class="mb-4 text-base font-semibold text-text">{{ $t('Define user permissions') }}</h3>

                <div class="space-y-2">
                    <div
                        v-for="role in rolesLocal"
                        :key="role.name"
                        class="flex items-center justify-between rounded-lg border border-border-subtle bg-white px-3 py-2"
                    >
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600"
                                v-model="role.checked"
                                @change="onRoleToggle(role)"
                                :name="role.translation_key"
                                :id="role.translation_key"
                            />
                            <span class="text-text">{{ $t(role.translation_key) }}</span>
                        </label>
                        <ToolTipDefault top :tooltip-text="$t(role.tooltipKey)" />
                    </div>
                </div>
            </div>

            <!-- Rechte (Katalog-Editor, kompakt) -->
            <div v-if="!form.roles.includes('artwork admin')" class="mt-8">
                <h3 class="mb-3 text-base font-semibold text-text">{{ $t('User permissions') }}</h3>
                <PermissionCatalogEditor
                    v-model="form.permissions"
                    :catalog="catalog"
                    :presets="permission_presets"
                    :show-explainer="false"
                    compact
                />
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
import {IconCheck, IconChevronDown, IconChevronUp, IconCircleX, IconSearch, IconX} from "@tabler/icons-vue";
import { ref, computed, reactive, getCurrentInstance } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import PermissionCatalogEditor from '@/Artwork/Permissions/PermissionCatalogEditor.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import AddButtonSmall from '@/Layouts/Components/General/Buttons/AddButtonSmall.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import ToolTipDefault from '@/Components/ToolTips/ToolTipDefault.vue'
import SvgCollection from '@/Layouts/Components/SvgCollection.vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'

/* Props */
const props = defineProps({
    show: Boolean,
    closeModal: Function,
    all_permissions: { type: Object, default: () => ({}) }, // veraltet, Katalog ersetzt die Gruppenliste
    catalog: { type: Object, required: true }, // PermissionCatalogPresenter::present()
    departments: { type: Array, default: () => [] },   // [{id, name, svg_name}]
    roles: { type: Array, default: () => [] },         // [{name, translation_key, tooltipKey}]
    permission_presets: { type: Array, default: () => [] }, // [{id, name, permissions:[names]}]
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

/* Selected departments (derived) */
const selectedDepartments = computed(() => deptLocal.filter(d => d.checked))

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

    form.user_emails = []
    form.permissions = []
    form.departments = []
    form.roles = []

    deptLocal.forEach(d => (d.checked = false))
    rolesLocal.forEach(r => (r.checked = false))
}

function handleClose (bool) {
    resetAll()
    props.closeModal?.(bool)
}
</script>
