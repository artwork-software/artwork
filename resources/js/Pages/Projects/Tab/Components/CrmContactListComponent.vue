<template>
    <div class="my-2 w-full">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="componentLabel !mb-0" :class="{ '!text-white': inSidebar }">{{ title }}</h3>
                <p v-if="description" class="mt-0.5 whitespace-pre-line text-xs text-text-subtle">{{ description }}</p>
            </div>
            <InfoButtonComponent v-if="component" :component="component" />
        </div>

        <div v-if="loadFailed" class="mt-2 flex items-center gap-x-3 rounded-lg border border-danger-border bg-danger-surface px-3 py-2">
            <span class="text-xs text-danger">{{ $t('Failed to load data') }}</span>
            <button type="button" class="shrink-0 text-xs font-medium text-accent-600 hover:underline" @click="load">{{ $t('Retry') }}</button>
        </div>
        <div v-else-if="loading" class="mt-2 text-xs text-text-subtle">{{ $t('Loading data...') }}</div>
        <template v-else>
            <p v-if="contactTypes.length === 0 && canWrite" class="mt-2 text-xs text-warning">
                {{ $t('No contact types are allowed for this list yet. Select them in the component settings.') }}
            </p>

            <p v-if="contacts.length === 0" class="mt-2 text-sm italic text-text-subtle">{{ $t('No contacts added yet.') }}</p>

            <ul v-else class="mt-2 space-y-2">
                <li v-for="contact in contacts" :key="contact.id" class="overflow-hidden rounded-lg border border-border-subtle bg-white">
                    <div class="flex items-center gap-x-2 px-3 py-2">
                        <img :src="contact.profile_photo_url" :alt="contact.display_name" class="size-7 shrink-0 rounded-full object-cover" />
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 items-center gap-x-2 text-left"
                            :aria-expanded="String(isExpanded(contact))"
                            @click="toggle(contact)"
                        >
                            <span class="truncate text-sm font-medium text-text">{{ contact.display_name }}</span>
                            <span v-if="contact.contact_type" class="inline-flex shrink-0 items-center rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-text-muted">
                                {{ $t(contact.contact_type.name) }}
                            </span>
                            <span
                                v-if="contact.is_external && !contact.reviewed_at"
                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-warning-surface px-2 py-0.5 text-xs text-warning"
                                :title="$t('Added externally by {name} – not yet reviewed', { name: contact.created_by_external?.name ?? '' })"
                            >
                                <IconUserExclamation class="size-3.5" stroke-width="1.5" />
                                {{ $t('External · not reviewed') }}
                            </span>
                            <span
                                v-else-if="contact.is_external"
                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-text-muted"
                                :title="$t('Added externally by {name}', { name: contact.created_by_external?.name ?? '' })"
                            >
                                <IconUserCheck class="size-3.5" stroke-width="1.5" />
                                {{ $t('External') }}
                            </span>
                            <component :is="isExpanded(contact) ? IconChevronUp : IconChevronDown" class="size-4 shrink-0 text-text-subtle" />
                        </button>
                        <button v-if="contact.can_edit" type="button" class="shrink-0 text-text-subtle hover:text-text" :title="$t('Edit')" :aria-label="$t('Edit')" @click="openEdit(contact)">
                            <IconPencil class="size-4" stroke-width="1.5" />
                        </button>
                        <button v-if="contact.can_remove" type="button" class="shrink-0 text-text-subtle hover:text-danger" :title="$t('Remove from list')" :aria-label="$t('Remove from list')" @click="remove(contact)">
                            <IconLinkOff class="size-4" stroke-width="1.5" />
                        </button>
                    </div>

                    <!-- Mögliche Dubletten extern angelegter Kontakte -->
                    <div v-if="contact.possible_duplicates?.length" class="border-t border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
                        <p class="font-medium">{{ $t('Possible duplicate in the CRM') }}</p>
                        <div v-for="duplicate in contact.possible_duplicates" :key="duplicate.id" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <a v-if="canViewCrm" :href="route('crm.contacts.show', duplicate.id)" target="_blank" class="underline">{{ duplicate.display_name }}</a>
                            <span v-else>{{ duplicate.display_name }}</span>
                            <button v-if="canMerge" type="button" class="font-medium underline" @click="merge(duplicate, contact)">
                                {{ $t('Merge into existing contact') }}
                            </button>
                        </div>
                    </div>

                    <div v-if="isExpanded(contact)" class="border-t border-border-subtle px-3 py-2">
                        <dl v-if="contact.fields.length" class="grid grid-cols-1 gap-x-4 gap-y-1.5 sm:grid-cols-2">
                            <div v-for="field in contact.fields" :key="field.property_id">
                                <dt class="text-xs text-text-subtle">{{ $t(field.name) }}</dt>
                                <dd class="whitespace-pre-line break-words text-sm text-text">
                                    <a v-if="field.type === 'link' && isSafeHttpUrl(field.value)" :href="field.value" target="_blank" rel="noopener" class="text-accent-600 hover:underline">{{ field.value }}</a>
                                    <template v-else>{{ formatValue(field) }}</template>
                                </dd>
                            </div>
                        </dl>
                        <p v-else class="text-xs text-text-subtle">{{ $t('No information available') }}</p>
                        <p v-if="contact.is_external" class="mt-2 text-xs text-text-subtle">
                            {{ $t('Added externally by {name}', { name: contact.created_by_external?.name ?? '' }) }}
                            <template v-if="contact.reviewed_at"> · {{ $t('confirmed by {name}', { name: contact.reviewed_by ?? '' }) }}</template>
                        </p>
                        <a v-if="canViewCrm" :href="route('crm.contacts.show', contact.id)" target="_blank" class="mt-1.5 inline-flex items-center gap-x-1 text-xs text-accent-600 hover:underline">
                            <IconExternalLink class="size-3.5" stroke-width="1.5" />
                            {{ $t('Open in CRM') }}
                        </a>
                    </div>
                </li>
            </ul>

            <p v-if="limitReached" class="mt-2 text-xs text-text-subtle">{{ $t('Maximum number of contacts reached ({max}).', { max: maxContacts }) }}</p>
            <p v-if="actionError" class="mt-2 text-xs text-danger">{{ actionError }}</p>

            <!-- Aktionen -->
            <div v-if="canWrite && contactTypes.length && !limitReached" class="mt-3">
                <div v-if="!showSearch" class="flex flex-wrap items-center gap-2">
                    <BaseUIButton
                        v-for="type in contactTypes"
                        :key="type.id"
                        type="button"
                        variant="secondary"
                        size="sm"
                        :icon="IconUserPlus"
                        @click="openCreate(type)"
                    >
                        {{ $t('Add {type}', { type: $t(type.name) }) }}
                    </BaseUIButton>
                    <BaseUIButton
                        v-if="canLinkExisting"
                        type="button"
                        variant="secondary"
                        size="sm"
                        :icon="IconLink"
                        @click="showSearch = true"
                    >
                        {{ $t('Link existing CRM contact') }}
                    </BaseUIButton>
                </div>
                <div v-else class="relative">
                    <div class="flex items-center gap-x-2">
                        <BaseInput :id="`crm-contact-list-search-${data.id}`" v-model="searchQuery" :label="$t('Search CRM contact')" without-translation class="w-full" />
                        <button type="button" class="shrink-0 text-text-subtle hover:text-text-muted" :title="$t('Close')" :aria-label="$t('Close')" @click="closeSearch">
                            <IconX class="size-4" stroke-width="1.5" />
                        </button>
                    </div>
                    <div v-if="searchQuery.length > 0" class="absolute z-30 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white text-sm shadow-lg ring-1 ring-black/5">
                        <div v-if="searching" class="px-4 py-3 text-xs text-text-subtle">{{ $t('Loading data...') }}</div>
                        <template v-else>
                            <button
                                v-for="result in searchResults"
                                :key="result.id"
                                type="button"
                                class="flex w-full items-center gap-x-2 px-4 py-2.5 text-left hover:bg-surface-sunken"
                                @click="link(result)"
                            >
                                <img :src="result.profile_photo_url" :alt="result.display_name" class="size-7 rounded-full object-cover" />
                                <span class="truncate text-text">{{ result.display_name }}</span>
                                <span v-if="result.contact_type" class="ml-auto shrink-0 text-xs text-text-subtle">{{ $t(result.contact_type.name) }}</span>
                            </button>
                            <div v-if="searchResults.length === 0" class="px-4 py-3 text-xs text-text-subtle">{{ $t('No results') }}</div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <CrmContactFormModal
            v-if="modal"
            :project-id="projectId"
            :component-id="data.id"
            :contact-type="modal.contactType"
            :contact="modal.contact"
            @close="modal = null"
            @saved="onSaved"
        />
    </div>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { router, usePage } from '@inertiajs/vue3'
import debounce from 'lodash.debounce'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import InfoButtonComponent from '@/Pages/Projects/Tab/Components/InfoButtonComponent.vue'
import CrmContactFormModal from '@/Pages/Projects/Tab/Components/CrmContactFormModal.vue'
import { usePermission } from '@/Composeables/Permission.js'
import { isSafeHttpUrl } from '@/Helper/SafeUrl.js'
import { useTranslation } from '@/Composeables/Translation.js'
import {
    IconChevronDown,
    IconChevronUp,
    IconExternalLink,
    IconLink,
    IconLinkOff,
    IconPencil,
    IconUserCheck,
    IconUserExclamation,
    IconUserPlus,
    IconX,
} from '@tabler/icons-vue'

defineOptions({ name: 'CrmContactListComponent' })

const props = defineProps({
    data: { type: Object, required: true },
    projectId: { type: [String, Number], required: true },
    inSidebar: { type: Boolean, default: false },
    canEditComponent: { type: Boolean, default: false },
    component: { type: Object, default: null },
})

const $t = useTranslation()
const { can } = usePermission(usePage().props)
const canViewCrm = computed(() => can('can view crm'))

const title = computed(() => props.data.data?.title || props.data.name)
const description = computed(() => props.data.data?.description || '')
const routeParams = computed(() => ({ project: props.projectId, component: props.data.id }))

const contacts = ref([])
const contactTypes = ref([])
const maxContacts = ref(null)
const canWriteServer = ref(false)
const canLinkExisting = ref(false)
const canMerge = ref(false)
const loading = ref(true)
const loadFailed = ref(false)
const actionError = ref('')
const expandedIds = ref([])
const modal = ref(null)

const canWrite = computed(() => props.canEditComponent && canWriteServer.value)
const limitReached = computed(() => maxContacts.value !== null && contacts.value.length >= maxContacts.value)

async function load() {
    loading.value = true
    loadFailed.value = false
    try {
        const { data } = await axios.get(route('projects.components.crm-contacts.index', routeParams.value))
        contacts.value = data.contacts ?? []
        contactTypes.value = data.contact_types ?? []
        maxContacts.value = data.max_contacts ?? null
        canWriteServer.value = data.can_write === true
        canLinkExisting.value = data.can_link_existing === true
        canMerge.value = data.can_merge === true
    } catch (e) {
        loadFailed.value = true
    } finally {
        loading.value = false
    }
}

function isExpanded(contact) {
    return expandedIds.value.includes(contact.id)
}

function toggle(contact) {
    expandedIds.value = isExpanded(contact)
        ? expandedIds.value.filter((id) => id !== contact.id)
        : [...expandedIds.value, contact.id]
}

function openCreate(type) {
    modal.value = { contactType: type, contact: null }
}

function openEdit(contact) {
    modal.value = { contactType: contact.contact_type, contact }
}

function onSaved(saved) {
    const index = contacts.value.findIndex((c) => c.id === saved.id)
    if (index >= 0) {
        contacts.value.splice(index, 1, saved)
    } else {
        contacts.value.push(saved)
    }
}

async function remove(contact) {
    if (!window.confirm($t('Remove {name} from this list? The contact remains in the CRM.', { name: contact.display_name }))) return
    actionError.value = ''
    try {
        await axios.delete(route('projects.components.crm-contacts.destroy', { ...routeParams.value, crmContact: contact.id }))
        contacts.value = contacts.value.filter((c) => c.id !== contact.id)
    } catch (e) {
        actionError.value = e?.response?.data?.message ?? $t('Failed to save')
    }
}

// Zusammenführen über die bestehende CRM-Dublettenfunktion: der extern angelegte Kontakt geht im
// bestehenden auf (Werte werden ergänzt, Projektverknüpfungen umgehängt).
function merge(existing, contact) {
    if (!window.confirm($t('Merge {new} into {existing}? Missing values are taken over, the new contact is removed.', { new: contact.display_name, existing: existing.display_name }))) return
    router.post(route('crm.duplicates.merge'), { primary_id: existing.id, duplicate_ids: [contact.id] }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => load(),
        onError: () => { actionError.value = $t('Failed to save') },
    })
}

// Suche bestehender Kontakte (nur erlaubte Typen, ohne bereits verknüpfte)
const showSearch = ref(false)
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)

const runSearch = debounce(async (query) => {
    try {
        const { data } = await axios.get(route('projects.components.crm-contacts.search', routeParams.value), { params: { search: query } })
        if (query === searchQuery.value) searchResults.value = data ?? []
    } catch (e) {
        searchResults.value = []
    } finally {
        searching.value = false
    }
}, 300)

watch(searchQuery, (query) => {
    if (!query) {
        searchResults.value = []
        return
    }
    searching.value = true
    runSearch(query)
})

function closeSearch() {
    showSearch.value = false
    searchQuery.value = ''
    searchResults.value = []
}

async function link(result) {
    actionError.value = ''
    try {
        const { data } = await axios.post(route('projects.components.crm-contacts.link', routeParams.value), { crm_contact_id: result.id })
        onSaved(data.contact)
        closeSearch()
    } catch (e) {
        actionError.value = e?.response?.data?.message ?? $t('Failed to save')
    }
}

function formatValue(field) {
    if (field.type === 'checkbox') return field.value === '1' ? $t('Yes') : $t('No')
    if (field.type === 'date' && field.value) return new Date(field.value).toLocaleDateString()
    return field.value
}

// Tab wurde intern bestätigt/zurückgegeben → Prüfstatus der Kontakte neu laden
const externalReviewVersion = inject('externalReviewVersion', ref(0))
watch(externalReviewVersion, () => load())

onMounted(load)
</script>
