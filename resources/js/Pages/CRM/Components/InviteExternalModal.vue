<template>
    <ArtworkBaseModal
        :title="modalTitle"
        :description="modalDescription"
        @close="$emit('close')"
    >
        <div class="space-y-5 mt-4">
            <!-- Ausführliche Hilfe: Ablauf, was die eingeladene Person sieht, CRM-Verknüpfung -->
            <div class="rounded-lg border border-border-subtle bg-surface-sunken">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm font-medium text-text"
                    @click="showHelp = !showHelp"
                >
                    <span class="flex items-center gap-2">
                        <IconInfoCircle stroke-width="1.5" class="size-5 text-accent-600" />
                        {{ $t('How does external access work?') }}
                    </span>
                    <IconChevronDown class="size-4 text-text-subtle transition-transform" :class="{ 'rotate-180': showHelp }" />
                </button>
                <div v-if="showHelp" class="space-y-3 border-t border-border-subtle px-3 py-3 text-xs text-text-muted">
                    <div v-for="section in helpSections" :key="section.title">
                        <p class="font-semibold text-text">{{ section.title }}</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-4">
                            <li v-for="(line, idx) in section.lines" :key="idx">{{ line }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Zweck der Funktion (Selbstpflege) — bei Einladungen aus dem Tab gibt es keine eigene CRM-Maske -->
            <p v-if="!isTabInvite" class="rounded-lg border border-info-border bg-info-surface px-3 py-2 text-xs text-info">
                {{ $t('This invitation is meant for contacts who should maintain their own master data (e.g. address, bank details) in the CRM. To let someone fill in a project tab, use “Invite external to this tab” in the project instead.') }}
            </p>

            <!-- Fixer Kontakt (Einstieg von der Kontaktseite) -->
            <div v-if="fixedContact" class="rounded-md bg-surface-sunken px-3 py-2 text-sm">
                <span class="text-xs text-text-subtle">{{ $t('Contact') }}:</span>
                <span class="ml-1 font-medium">{{ fixedContact.display_name }}</span>
                <span v-if="inviteInfo?.contact?.contact_type" class="ml-2 text-xs text-text-subtle">{{ inviteInfo.contact.contact_type.name }}</span>
            </div>

            <!-- E-Mail -->
            <div v-if="selectedContact && contactEmail">
                <p class="text-sm text-text">
                    {{ $t('The invitation will be sent to') }}
                    <span class="font-medium">{{ contactEmail }}</span>
                </p>
                <div class="mt-2">
                    <BaseCheckbox
                        id="invite-override-email"
                        v-model="overrideEmail"
                        :label="$t('Send to a different email address instead')"
                    />
                </div>
                <div v-if="overrideEmail" class="mt-2">
                    <BaseInput
                        id="invite-email"
                        v-model="form.email"
                        type="email"
                        :label="$t('Email for external access')"
                        required
                    />
                    <p class="text-xs text-text-subtle mt-1">
                        {{ $t('This address is stored as the external access email; the contact\'s own email stays unchanged.') }}
                    </p>
                </div>
            </div>
            <div v-else>
                <BaseInput
                    id="invite-email"
                    v-model="form.email"
                    type="email"
                    :label="$t('Email')"
                    required
                />
                <BaseInput
                    v-if="isTabInvite"
                    id="invite-name"
                    v-model="form.name"
                    class="mt-3"
                    :label="$t('Name (optional)')"
                />
                <p v-if="isTabInvite" class="text-xs text-text-subtle mt-1">
                    {{ $t('Shown in notifications and in the tab status. No CRM contact is created for the invited person.') }}
                </p>
                <p v-if="selectedContact && !contactEmail" class="text-xs text-text-subtle mt-1">
                    {{ $t('This contact has no email address yet; the address you enter will be stored on the contact.') }}
                </p>
            </div>
            <p v-if="form.errors.email" class="text-xs text-danger">{{ form.errors.email }}</p>

            <!-- Bereits vorhandene Zugänge des Kontakts -->
            <div v-if="existingAccesses.length" class="rounded-lg border border-border-subtle bg-surface-sunken p-3 text-xs text-text-muted space-y-1">
                <p class="font-semibold text-text">{{ $t('Existing external access') }}</p>
                <div v-for="access in existingAccesses" :key="access.id">
                    <span class="font-medium">{{ access.email }}</span>
                    <span v-if="access.revoked_at"> · {{ $t('revoked') }}</span>
                    <span v-else-if="access.crm_access_expires_at"> · {{ $t('CRM until') }} {{ formatDate(access.crm_access_expires_at) }}</span>
                    <ul v-if="access.scopes.length" class="ml-3 list-disc">
                        <li v-for="(scope, idx) in access.scopes" :key="idx">
                            {{ scope.project }} – {{ scope.tab }} ({{ $t('until') }} {{ formatDate(scope.valid_to) }})
                        </li>
                    </ul>
                </div>
                <p>{{ $t('A new invitation extends this access and adds the selected tabs.') }}</p>
            </div>

            <!-- Kontaktart + Pflichtfelder (nur für neue Kontakte) -->
            <template v-if="!selectedContact && !isTabInvite">
                <div>
                    <BaseCombobox
                        v-model="form.crm_contact_type_id"
                        :items="contactTypes"
                        option-label="name"
                        option-key="id"
                        :label="$t('Contact type')"
                        :placeholder="$t('Search contact type')"
                        :empty-text="$t('No results')"
                        coerce="number"
                    />
                    <p v-if="requirements && !requirements.invitable" class="text-xs text-danger mt-1">
                        {{ $t('This contact type cannot be invited for external access.') }}
                    </p>
                    <p v-if="form.errors.crm_contact_type_id" class="text-xs text-danger mt-1">{{ form.errors.crm_contact_type_id }}</p>
                </div>

                <div v-for="field in publicRequiredFields" :key="`pub-${field}`">
                    <BaseInput
                        :id="`pub-${field}`"
                        v-model="form.public_field_values[field]"
                        :label="publicFieldLabel(field)"
                        required
                    />
                </div>

                <template v-if="confidentialRequiredProperties.length">
                    <div class="rounded-lg bg-surface-sunken border border-border-subtle p-3 space-y-3">
                        <p class="text-xs text-text-muted">
                            {{ $t('These fields will not be visible to the external person and must be filled in by you in advance.') }}
                        </p>
                        <div v-for="property in confidentialRequiredProperties" :key="`conf-${property.id}`">
                            <BaseInput
                                :id="`conf-${property.id}`"
                                v-model="form.confidential_field_values[property.id]"
                                :label="property.name"
                                required
                            />
                        </div>
                    </div>
                </template>
            </template>

            <!-- CRM-Zugriff (nur Selbstpflege, nicht bei Einladungen aus dem Tab) -->
            <div v-if="!isTabInvite">
                <BaseInput
                    id="invite-crm-until"
                    v-model="form.crm_access_expires_at"
                    type="date"
                    :label="$t('CRM access until')"
                    :min="today"
                />
                <p class="text-xs text-text-subtle mt-1">
                    {{ $t('Until this date the person can maintain their own contact data. Leave empty to use the default duration.') }}
                </p>
                <p v-if="form.errors.crm_access_expires_at" class="text-xs text-danger mt-1">{{ form.errors.crm_access_expires_at }}</p>
            </div>

            <!-- Tab-Freigaben (nur aus dem Projekt-Tab heraus) -->
            <template v-if="source === 'project_tab'">
                <div class="space-y-2">
                    <label class="componentLabel">{{ $t('Shared tabs') }}</label>
                    <div
                        v-for="tab in availableTabs"
                        :key="tab.id"
                        class="rounded-md border border-border-subtle p-3"
                    >
                        <BaseCheckbox
                            :id="`invite-tab-${tab.id}`"
                            :model-value="selectedTabIds.includes(tab.id)"
                            :label="tab.name"
                            @update:model-value="(checked) => toggleTab(tab.id, checked)"
                        />
                        <p v-if="tab.hasExternalComponents === false" class="mt-1 ml-6 text-xs text-text-subtle">
                            {{ $t('No externally visible components') }}
                            {{ '· ' + $t('This tab contains no components that external persons could see. It is not preselected.') }}
                        </p>

                        <div v-if="selectedTabIds.includes(tab.id)" class="mt-3 grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <ArtworkBaseListbox
                                    v-model="tabConfig[tab.id].access"
                                    :items="accessTypeItems"
                                    by="id"
                                    option-label="name"
                                    :label="$t('Access')"
                                />
                            </div>
                            <div>
                                <BaseInput
                                    :id="`invite-tab-${tab.id}-from`"
                                    v-model="tabConfig[tab.id].valid_from"
                                    type="date"
                                    :label="$t('From')"
                                />
                            </div>
                            <div>
                                <BaseInput
                                    :id="`invite-tab-${tab.id}-to`"
                                    v-model="tabConfig[tab.id].valid_to"
                                    type="date"
                                    :label="$t('Until')"
                                    :min="tabConfig[tab.id].valid_from"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="selectedTabIds.length"
                    class="rounded-lg bg-warning-surface border border-warning-border p-4 text-sm text-warning"
                >
                    <p class="font-semibold">{{ $t('Important') }}</p>
                    <p class="mt-1">
                        {{ $t('The external person will see all components of the selected tab(s), regardless of their visibility settings. Consider creating a dedicated tab for external access.') }}
                    </p>
                </div>

                <div
                    v-if="selectedTabsWithoutUpload"
                    class="rounded-lg bg-warning-surface border border-warning-border p-4 text-sm text-warning"
                >
                    <p class="font-semibold">{{ $t('File upload disabled') }}</p>
                    <p class="mt-1">
                        {{ $t('The invited person cannot upload files in this tab because file upload for external accesses is disabled. If needed, enable it under Settings → External access.') }}
                    </p>
                </div>
            </template>

            <p v-if="form.errors.source_reference_project_id" class="text-xs text-danger">
                {{ form.errors.source_reference_project_id }}
            </p>
            <p v-if="serverError" class="text-xs text-danger">{{ serverError }}</p>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <BaseUIButton type="button" variant="secondary" hide-icon @click="$emit('close')">{{ $t('Cancel') }}</BaseUIButton>
            <BaseUIButton
                type="button"
                variant="primary"
                hide-icon
                :disabled="!canSubmit"
                @click="submit"
            >
                {{ $t('Send invitation') }}
            </BaseUIButton>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseCombobox from '@/Artwork/Inputs/BaseCombobox.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { IconChevronDown, IconInfoCircle } from '@tabler/icons-vue'

const props = defineProps({
    /** 'crm_index' | 'project_tab' | 'crm_contact' */
    source: { type: String, default: 'crm_index' },
    contactTypes: { type: Array, default: () => [] },
    project: { type: Object, default: null },
    availableTabs: { type: Array, default: () => [] },
    preselectedTabId: { type: Number, default: null },
    /** Einstellung "Dateiupload für Externe erlauben" (Einstellungen → Externer Zugriff) */
    externalFileUploadEnabled: { type: Boolean, default: false },
    /** Fester Kontakt (Einstieg von der CRM-Kontaktseite): { id, display_name } */
    contact: { type: Object, default: null },
})

const emit = defineEmits(['close', 'success'])
const $t = useTranslation()

const contactTypes = ref([...props.contactTypes])
const fixedContact = computed(() => (props.source === 'crm_contact' ? props.contact : null))
// Einladung aus dem Projekt-Tab: nur E-Mail, optionaler Name und Tabs — kein eigener CRM-Kontakt,
// keine Selbstpflege. Kontakte entstehen im Tab über die Komponente „CRM-Kontaktliste“.
const isTabInvite = computed(() => props.source === 'project_tab')

// Hilfetexte (aufklappbar) — bewusst ausführlich, weil der Ablauf mehrere Beteiligte hat
const showHelp = ref(false)
const helpSections = computed(() => [
    {
        title: $t('What happens after sending'),
        lines: [
            $t('The person receives an email with a personal link. No account and no password are needed; the link signs them in directly.'),
            $t('The link is valid for a short time only. Afterwards the person can request a new link at any time on the external login page with their email address.'),
            $t('Access is always tied to the email address. Inviting the same address again extends the access and adds the selected tabs.'),
        ],
    },
    ...(isTabInvite.value ? tabInviteHelp() : selfEditHelp()),
    {
        title: $t('Managing access'),
        lines: [
            $t('CRM → External access lists every access: resend invitation, extend, switch tabs to read-only, end access, revoke, or re-link to another contact.'),
            $t('On the contact page you see all accesses of a contact with their tabs, periods and the last submission.'),
            $t('A few days before an access expires, the inviting person is notified (configurable under Settings → External access).'),
        ],
    },
])

function tabInviteHelp() {
    return [
        {
            title: $t('What the invited person can see and do'),
            lines: [
                $t('Only the tabs you share here, only for this project. Inside a shared tab the person sees ALL components of that tab, regardless of internal visibility settings – a dedicated tab for external exchange is recommended.'),
                $t('With "Read and write" the person can fill in text fields, dropdowns, checkboxes and links and upload documents; entries are saved immediately and are visible to you right away.'),
                $t('In a "CRM contact list" the person can add contacts of the allowed contact types (e.g. every artist travelling). Confidential fields stay hidden. They can only edit or remove contacts they added themselves.'),
                $t('With "Submit entered data" the person marks their entries as final: you and the project managers are notified and the tab is locked for the person.'),
            ],
        },
        {
            title: $t('Confirming the data'),
            lines: [
                $t('Next to the invite button you see every invited person with their status. Click it to confirm submitted data or to return it for revision (with an optional comment).'),
                $t('Confirming marks the contacts added by the person as reviewed. Returning unlocks the tab for the person again. The person is informed by email in both cases.'),
                $t('No CRM contact is created for the invited person themselves.'),
            ],
        },
    ]
}

function selfEditHelp() {
    return [
        {
            title: $t('What the invited person can see and do'),
            lines: [
                $t('Under "My data" the person sees and edits their own CRM contact. Those changes are NOT applied directly – you review and approve them in the CRM.'),
            ],
        },
        {
            title: $t('How the CRM is linked'),
            lines: [
                $t('New person: a CRM contact of the selected contact type is created with this email address. Existing contact: the access is linked to that contact; if the contact has no email yet, the address is stored on it.'),
                $t('A different address can be used for the access only; the contact\'s own email stays unchanged.'),
                $t('The CRM access period defines how long the person may maintain their own data. When it has expired, the login stops working.'),
                $t('Confidential mandatory properties of the contact type are never shown to the person and must be filled in by you when inviting.'),
            ],
        },
    ]
}

const modalTitle = computed(() => fixedContact.value ? $t('Invite contact for external access') : $t('Invite external'))
const modalDescription = computed(() => {
    if (props.source === 'project_tab') return $t('The person receives a link by email and can fill in the shared tabs without an account.')
    if (fixedContact.value) return $t('The contact receives a link by email and can maintain their own contact data without an account.')
    return $t('Invite an external person to maintain their own data.')
})

const fieldLabels = {
    first_name: 'First name',
    last_name: 'Last name',
    name: 'Name',
    display_name: 'Name',
    provider_name: 'Provider name',
    contact_person: 'Contact person',
}
const publicFieldLabel = (field) => $t(fieldLabels[field] ?? field)

const today = new Date().toISOString().slice(0, 10)
const defaults = ref({ crm_access_expires_at: '', tab_valid_from: today, tab_valid_to: '' })

const requirements = ref(null)
const serverError = ref('')
const submitting = ref(false)

// --- Bestehender Kontakt (fix, Einstieg von der Kontaktseite) ---------------------------
const selectedContact = ref(fixedContact.value ? { ...fixedContact.value } : null)
const inviteInfo = ref(null)
const overrideEmail = ref(false)

const contactEmail = computed(() => inviteInfo.value?.contact?.email ?? null)
const existingAccesses = computed(() => inviteInfo.value?.accesses ?? [])

async function loadInviteInfo(contactId) {
    try {
        const { data } = await axios.get(route('crm.externals.contacts.invite-info', { crmContact: contactId }))
        inviteInfo.value = data
        if (data.defaults) applyDefaults(data.defaults)
    } catch (e) {
        inviteInfo.value = null
    }
}

// --- Tabs ------------------------------------------------------------------------------
const accessTypeItems = computed(() => [
    { id: 'read', name: $t('Read only') },
    { id: 'write', name: $t('Read and write') },
])
// Der aktuelle Tab wird nur vorausgewählt, wenn externe Personen darin überhaupt etwas sehen könnten.
function tabHasExternalContent(tabId) {
    const tab = props.availableTabs.find((entry) => entry.id === tabId)
    return tab ? tab.hasExternalComponents !== false : true
}
const selectedTabIds = ref(
    props.preselectedTabId && tabHasExternalContent(props.preselectedTabId) ? [props.preselectedTabId] : [],
)
const tabConfig = ref({})

function tabDefaults() {
    return {
        access: accessTypeItems.value[1],
        valid_from: defaults.value.tab_valid_from || today,
        valid_to: defaults.value.tab_valid_to || '',
    }
}
props.availableTabs.forEach((tab) => { tabConfig.value[tab.id] = tabDefaults() })

// Ausgewählte Tabs mit Dokument-Komponente können ohne den Upload-Schalter nur lesend genutzt werden.
const selectedTabsWithoutUpload = computed(() =>
    !props.externalFileUploadEnabled
    && props.availableTabs.some((tab) => tab.hasDocumentComponent === true && selectedTabIds.value.includes(tab.id)),
)

function toggleTab(tabId, checked) {
    if (checked) {
        if (!selectedTabIds.value.includes(tabId)) selectedTabIds.value.push(tabId)
        if (!tabConfig.value[tabId]) tabConfig.value[tabId] = tabDefaults()
    } else {
        selectedTabIds.value = selectedTabIds.value.filter((id) => id !== tabId)
    }
}

function applyDefaults(d) {
    defaults.value = { ...defaults.value, ...d }
    if (!form.crm_access_expires_at) form.crm_access_expires_at = d.crm_access_expires_at ?? ''
    Object.keys(tabConfig.value).forEach((tabId) => {
        if (!tabConfig.value[tabId].valid_to) tabConfig.value[tabId].valid_to = d.tab_valid_to ?? ''
        if (!tabConfig.value[tabId].valid_from) tabConfig.value[tabId].valid_from = d.tab_valid_from ?? today
    })
}

// --- Form -------------------------------------------------------------------------------
const form = useForm({
    email: '',
    name: '',
    crm_contact_type_id: contactTypes.value[0]?.id ?? null,
    source: props.source,
    source_reference_project_id: props.project?.id ?? null,
    crm_access_expires_at: '',
    tab_scopes: [],
    public_field_values: {},
    confidential_field_values: {},
    crm_contact_id: null,
})

const publicRequiredFields = computed(() => requirements.value?.public_required_fields ?? [])
const confidentialRequiredProperties = computed(() => requirements.value?.confidential_required_properties ?? [])

const canSubmit = computed(() => {
    if (submitting.value) return false
    if (selectedContact.value) {
        if (overrideEmail.value || !contactEmail.value) return !!form.email
        return true
    }
    if (isTabInvite.value) return !!form.email && selectedTabIds.value.length > 0
    if (!form.email || !form.crm_contact_type_id) return false
    return !(requirements.value && !requirements.value.invitable)
})

const loadRequirements = async () => {
    if (!form.crm_contact_type_id || selectedContact.value || isTabInvite.value) return
    serverError.value = ''
    form.public_field_values = {}
    form.confidential_field_values = {}
    try {
        const { data } = await axios.get(
            route('crm.externals.contact-types.requirements', { crmContactType: form.crm_contact_type_id })
        )
        requirements.value = data
    } catch (e) {
        requirements.value = null
    }
}

const fetchContactTypes = async () => {
    try {
        const { data } = await axios.get(route('crm.externals.contact-types.index'))
        if (!contactTypes.value.length) contactTypes.value = data.contact_types ?? []
        if (data.defaults) applyDefaults(data.defaults)
        if (!form.crm_contact_type_id) {
            form.crm_contact_type_id = contactTypes.value[0]?.id ?? null
        }
    } catch (e) {
        // Kontaktarten wurden ggf. schon per Prop geliefert
    }
}

watch(() => form.crm_contact_type_id, loadRequirements)
onMounted(async () => {
    await fetchContactTypes()
    if (fixedContact.value) {
        await loadInviteInfo(fixedContact.value.id)
    } else {
        await loadRequirements()
    }
})

const submit = () => {
    form.tab_scopes = selectedTabIds.value.map((tabId) => ({
        project_tab_id: tabId,
        access_type: tabConfig.value[tabId].access?.id ?? 'read',
        valid_from: tabConfig.value[tabId].valid_from,
        valid_to: tabConfig.value[tabId].valid_to,
    }))

    const payload = form.data()
    if (selectedContact.value) {
        payload.crm_contact_id = selectedContact.value.id
        payload.crm_contact_type_id = null
        payload.public_field_values = {}
        payload.confidential_field_values = {}
        if (!overrideEmail.value && contactEmail.value) payload.email = ''
    } else {
        payload.crm_contact_id = null
    }
    if (isTabInvite.value) {
        payload.crm_contact_type_id = null
        payload.crm_access_expires_at = null
        payload.public_field_values = {}
        payload.confidential_field_values = {}
    } else {
        payload.name = null
    }
    if (!payload.crm_access_expires_at) payload.crm_access_expires_at = null

    // Der Endpunkt antwortet mit JSON (201/422), nicht mit einer Inertia-Response —
    // deshalb axios statt form.post (Inertia würde die JSON-Antwort als ungültig verwerfen).
    serverError.value = ''
    form.clearErrors()
    submitting.value = true
    axios.post(route('crm.externals.invitations.store'), payload)
        .then(() => {
            emit('success')
            emit('close')
        })
        .catch((error) => {
            const response = error?.response
            if (response?.status === 422 && response.data?.errors) {
                form.setError(Object.fromEntries(
                    Object.entries(response.data.errors).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : messages])
                ))
            }
            serverError.value = response?.data?.message ?? $t('Could not save. Try again.')
        })
        .finally(() => {
            submitting.value = false
        })
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString() : ''
}
</script>
