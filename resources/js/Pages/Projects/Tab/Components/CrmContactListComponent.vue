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
            <p v-if="contactTypes.length === 0 && linkableContactTypes.length === 0 && canWrite" class="mt-2 text-xs text-warning">
                {{ $t('No contact types are allowed for this list yet. Select them in the component settings.') }}
            </p>

            <!-- Ohne Rollen: einfache Liste, Sonderrolle „Künstler*in“ als Badge -->
            <template v-if="!rolesEnabled">
                <p v-if="contacts.length === 0" class="mt-2 text-sm italic text-text-subtle">{{ $t('No contacts added yet.') }}</p>
                <ul v-else class="mt-2 space-y-2">
                    <CrmContactListCard
                        v-for="contact in contacts"
                        :key="contact.id"
                        :contact="contact"
                        :expanded="isExpanded(contact)"
                        :can-write="canWrite"
                        :can-view-crm="canViewCrm"
                        :can-merge="canMerge"
                        :show-role-chips="false"
                        @toggle="toggle(contact)"
                        @edit="openEdit(contact)"
                        @edit-roles="openRoles(contact)"
                        @remove="remove(contact)"
                        @merge="(duplicate) => merge(duplicate, contact)"
                    />
                </ul>
            </template>

            <!-- Mit Rollen: Gruppen „Künstler*in“, Freitext-Rollen, ohne Rolle -->
            <div v-else class="mt-2 space-y-4">
                <section v-for="group in groups" :key="group.key">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="min-w-0">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-text-muted hover:text-text"
                                :aria-expanded="String(!isCollapsed(group))"
                                :title="isCollapsed(group) ? $t('Expand') : $t('Collapse')"
                                @click="toggleGroup(group)"
                            >
                                <component :is="isCollapsed(group) ? IconChevronRight : IconChevronDown" class="size-4 shrink-0 text-text-subtle" stroke-width="1.5" />
                                <IconPalette v-if="group.key === ARTIST_GROUP" class="size-4 shrink-0 text-accent-700" stroke-width="1.5" />
                                <span class="truncate">{{ group.label }}</span>
                                <span class="shrink-0 rounded-full bg-surface-sunken px-1.5 py-0.5 font-medium normal-case tracking-normal text-text-subtle">{{ group.contacts.length }}</span>
                            </button>
                        </h4>
                        <div v-if="canWrite" class="flex items-center gap-1">
                            <!-- zugeklappt steht „Verknüpfen“ im Kopf, aufgeklappt unter der Gruppe -->
                            <button
                                v-if="isCollapsed(group) && canLinkGroup(group)"
                                type="button"
                                class="rounded p-1 text-text-subtle hover:text-text"
                                :title="$t('Link CRM contact as “{role}”', { role: group.label })"
                                :aria-label="$t('Link CRM contact as “{role}”', { role: group.label })"
                                @click="startGroupLink(group)"
                            >
                                <IconLink class="size-3.5" stroke-width="1.5" />
                            </button>
                            <template v-if="group.role">
                                <button type="button" class="rounded p-1 text-text-subtle hover:text-text" :title="$t('Rename role')" :aria-label="$t('Rename role')" @click="renameRole(group.role)">
                                    <IconPencil class="size-3.5" stroke-width="1.5" />
                                </button>
                                <button type="button" class="rounded p-1 text-text-subtle hover:text-danger" :title="$t('Delete role')" :aria-label="$t('Delete role')" @click="deleteRole(group.role)">
                                    <IconTrash class="size-3.5" stroke-width="1.5" />
                                </button>
                            </template>
                        </div>
                    </div>
                    <template v-if="!isCollapsed(group)">
                    <ul v-if="group.contacts.length" class="mt-1.5 space-y-2">
                        <CrmContactListCard
                            v-for="contact in group.contacts"
                            :key="`${group.key}-${contact.id}`"
                            :contact="contact"
                            :expanded="isExpanded(contact)"
                            :can-write="canWrite"
                            :can-view-crm="canViewCrm"
                            :can-merge="canMerge"
                            :show-role-chips="false"
                            :show-artist-badge="group.key !== ARTIST_GROUP"
                            @toggle="toggle(contact)"
                            @edit="openEdit(contact)"
                            @edit-roles="openRoles(contact)"
                            @remove="remove(contact)"
                            @merge="(duplicate) => merge(duplicate, contact)"
                        />
                    </ul>
                    <p v-else class="mt-1 text-xs italic text-text-subtle">{{ $t('Nobody assigned yet.') }}</p>
                    <button
                        v-if="canLinkGroup(group)"
                        type="button"
                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-accent-600 hover:underline"
                        @click="startGroupLink(group)"
                    >
                        <IconLink class="size-3.5" stroke-width="1.5" />
                        {{ $t('Link CRM contact as “{role}”', { role: group.label }) }}
                    </button>
                    </template>
                </section>

                <!-- Neue Rolle -->
                <div v-if="canWrite" class="flex items-end gap-2">
                    <BaseInput
                        :id="`crm-contact-list-new-role-${data.id}`"
                        v-model="newRoleName"
                        :label="$t('Add role')"
                        :list="`crm-contact-list-role-suggestions-${data.id}`"
                        without-translation
                        class="flex-1"
                        @keydown.enter.prevent="addRole"
                    />
                    <BaseUIButton type="button" variant="secondary" size="sm" :icon="IconPlus" :disabled="!newRoleName.trim()" @click="addRole">
                        {{ $t('Add role') }}
                    </BaseUIButton>
                    <datalist :id="`crm-contact-list-role-suggestions-${data.id}`">
                        <option v-for="suggestion in roleSuggestions" :key="suggestion" :value="suggestion" />
                    </datalist>
                </div>
            </div>

            <p v-if="limitReached" class="mt-2 text-xs text-text-subtle">{{ $t('Maximum number of contacts reached ({max}).', { max: maxContacts }) }}</p>
            <p v-if="actionError" class="mt-2 text-xs text-danger">{{ actionError }}</p>

            <!-- Aktionen -->
            <div v-if="canWrite && (linkTarget || (!limitReached && (contactTypes.length || (canLinkExisting && linkableContactTypes.length))))" class="mt-3">
                <div v-if="!linkTarget" class="flex flex-wrap items-center gap-2">
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
                        v-if="canLinkExisting && linkableContactTypes.length"
                        type="button"
                        variant="secondary"
                        size="sm"
                        :icon="IconLink"
                        @click="startLink({})"
                    >
                        {{ $t('Link existing CRM contact') }}
                    </BaseUIButton>
                </div>
                <div v-else class="relative">
                    <p v-if="linkTargetLabel" class="mb-1 text-xs text-text-muted">{{ $t('Link as “{role}”', { role: linkTargetLabel }) }}</p>
                    <div class="flex items-end gap-x-2">
                        <select
                            v-if="linkableContactTypes.length > 1"
                            v-model="searchTypeId"
                            class="h-[42px] shrink-0 rounded-lg border border-border bg-white px-2 text-sm text-text"
                            :aria-label="$t('Contact type')"
                        >
                            <option :value="null">{{ $t('All contact types') }}</option>
                            <option v-for="type in linkableContactTypes" :key="type.id" :value="type.id">{{ $t(type.name) }}</option>
                        </select>
                        <BaseInput :id="`crm-contact-list-search-${data.id}`" v-model="searchQuery" :label="$t('Search CRM contact')" without-translation class="w-full" />
                        <button type="button" class="mb-2.5 shrink-0 text-text-subtle hover:text-text-muted" :title="$t('Close')" :aria-label="$t('Close')" @click="closeSearch">
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
                                <span class="min-w-0">
                                    <span class="block truncate text-text">{{ result.display_name }}</span>
                                    <span v-if="result.linked" class="block truncate text-xs text-text-subtle">{{ linkedHint(result.linked) }}</span>
                                </span>
                                <span
                                    v-if="result.contact_type"
                                    class="ml-auto shrink-0 rounded-full px-2 py-0.5 text-xs"
                                    :style="result.contact_type.color ? { backgroundColor: result.contact_type.color + '22', color: result.contact_type.color } : {}"
                                    :class="result.contact_type.color ? '' : 'bg-surface-sunken text-text-muted'"
                                >
                                    {{ $t(result.contact_type.name) }}
                                </span>
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

        <CrmContactRolesModal
            v-if="rolesModalContact"
            :contact="rolesModalContact"
            :roles="roles"
            :suggestions="roleSuggestions"
            :roles-enabled="rolesEnabled"
            :saving="savingRoles"
            :error="rolesError"
            @close="rolesModalContact = null"
            @save="saveRoles"
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
import CrmContactListCard from '@/Pages/Projects/Tab/Components/CrmContactListCard.vue'
import CrmContactRolesModal from '@/Pages/Projects/Tab/Components/CrmContactRolesModal.vue'
import { usePermission } from '@/Composeables/Permission.js'
import { useTranslation } from '@/Composeables/Translation.js'
import {
    IconChevronDown,
    IconChevronRight,
    IconLink,
    IconPalette,
    IconPencil,
    IconPlus,
    IconTrash,
    IconUserPlus,
    IconX,
} from '@tabler/icons-vue'

defineOptions({ name: 'CrmContactListComponent' })

const ARTIST_GROUP = '__artist__'
const NO_ROLE_GROUP = '__none__'

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
const linkableContactTypes = ref([])
const maxContacts = ref(null)
const canWriteServer = ref(false)
const canLinkExisting = ref(false)
const canMerge = ref(false)
const rolesEnabled = ref(false)
const roles = ref([])
const roleSuggestions = ref([])
const loading = ref(true)
const loadFailed = ref(false)
const actionError = ref('')
const expandedIds = ref([])
const modal = ref(null)

const canWrite = computed(() => props.canEditComponent && canWriteServer.value)
const limitReached = computed(() => maxContacts.value !== null && contacts.value.length >= maxContacts.value)

// Gruppen: Sonderrolle „Künstler*in“ zuerst, dann die Rollen der Liste, zuletzt Kontakte ohne Rolle.
// Eine Person mit mehreren Rollen steht in jeder ihrer Gruppen.
const groups = computed(() => {
    const result = [{
        key: ARTIST_GROUP,
        label: $t('Artist'),
        role: null,
        contacts: contacts.value.filter((contact) => contact.is_artist),
    }]
    for (const role of roles.value) {
        result.push({
            key: `role:${role}`,
            label: role,
            role,
            contacts: contacts.value.filter((contact) => (contact.roles ?? []).includes(role)),
        })
    }
    const withoutRole = contacts.value.filter((contact) => !contact.is_artist && !(contact.roles ?? []).length)
    if (withoutRole.length) {
        result.push({ key: NO_ROLE_GROUP, label: $t('Without role'), role: null, contacts: withoutRole })
    }
    return result
})

// --- Akkordion je Rolle: zugeklappte Gruppen je Komponente im Browser merken ---
// „Künstler*in“ ist standardmäßig zu, die Personen stehen zusätzlich in ihren Rollengruppen.
const collapsedStorageKey = `crm-contact-list-collapsed:${props.data.id}`
const collapsedGroups = ref(readCollapsedGroups())

function readCollapsedGroups() {
    try {
        const stored = JSON.parse(window.localStorage.getItem(collapsedStorageKey) ?? 'null')
        if (Array.isArray(stored)) return stored
    } catch (e) {
        // ohne Speicher gilt die Vorgabe
    }
    return [ARTIST_GROUP]
}

function isCollapsed(group) {
    return collapsedGroups.value.includes(group.key)
}

function setCollapsed(group, collapsed) {
    collapsedGroups.value = collapsed
        ? [...new Set([...collapsedGroups.value, group.key])]
        : collapsedGroups.value.filter((key) => key !== group.key)
    try {
        window.localStorage.setItem(collapsedStorageKey, JSON.stringify(collapsedGroups.value))
    } catch (e) {
        // nur Komfort: ohne Speicher gilt der Zustand bis zum Neuladen
    }
}

function toggleGroup(group) {
    setCollapsed(group, !isCollapsed(group))
}

// Auch bei erreichter Höchstzahl: Personen, die schon in der Liste stehen, bekommen weitere Rollen
function canLinkGroup(group) {
    return canWrite.value && canLinkExisting.value && linkableContactTypes.value.length > 0 && group.key !== NO_ROLE_GROUP
}

function startGroupLink(group) {
    setCollapsed(group, false)
    startLink(group.key === ARTIST_GROUP ? { artist: true } : { role: group.role })
}

async function load() {
    loading.value = true
    loadFailed.value = false
    try {
        const { data } = await axios.get(route('projects.components.crm-contacts.index', routeParams.value))
        contacts.value = data.contacts ?? []
        contactTypes.value = data.contact_types ?? []
        linkableContactTypes.value = data.linkable_contact_types ?? []
        maxContacts.value = data.max_contacts ?? null
        canWriteServer.value = data.can_write === true
        canLinkExisting.value = data.can_link_existing === true
        canMerge.value = data.can_merge === true
        rolesEnabled.value = data.roles_enabled === true
        roles.value = data.roles ?? []
        roleSuggestions.value = data.role_suggestions ?? []
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

function upsertContact(saved) {
    const index = contacts.value.findIndex((c) => c.id === saved.id)
    if (index >= 0) {
        contacts.value.splice(index, 1, saved)
    } else {
        contacts.value.push(saved)
    }
}

function onSaved(saved) {
    upsertContact(saved)
}

async function remove(contact) {
    if (!window.confirm($t('Remove {name} from this list? The contact remains in the CRM.', { name: contact.display_name }))) return
    actionError.value = ''
    try {
        await axios.delete(route('projects.components.crm-contacts.destroy', { ...routeParams.value, crmContact: contact.id }), { skipErrorToast: true }) // Fehler steht unter der Liste
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

// --- Rollen ---
const newRoleName = ref('')
const rolesModalContact = ref(null)
const savingRoles = ref(false)
const rolesError = ref('')

function openRoles(contact) {
    rolesError.value = ''
    rolesModalContact.value = contact
}

async function saveRoles(payload) {
    savingRoles.value = true
    rolesError.value = ''
    try {
        const { data } = await axios.patch(
            route('projects.components.crm-contacts.entry-roles.update', { ...routeParams.value, crmContact: rolesModalContact.value.id }),
            payload,
            { skipErrorToast: true },
        )
        upsertContact(data.contact)
        roles.value = data.roles ?? roles.value
        rolesModalContact.value = null
    } catch (e) {
        rolesError.value = e?.response?.data?.message ?? $t('Failed to save')
    } finally {
        savingRoles.value = false
    }
}

async function addRole() {
    const name = newRoleName.value.trim()
    if (!name) return
    actionError.value = ''
    try {
        const { data } = await axios.post(route('projects.components.crm-contacts.roles.store', routeParams.value), { name }, { skipErrorToast: true })
        roles.value = data.roles ?? roles.value
        newRoleName.value = ''
    } catch (e) {
        actionError.value = e?.response?.data?.message ?? $t('Failed to save')
    }
}

async function renameRole(role) {
    const newName = window.prompt($t('New name for the role “{role}”', { role }), role)
    if (!newName || newName.trim() === role) return
    actionError.value = ''
    try {
        const { data } = await axios.patch(route('projects.components.crm-contacts.roles.rename', routeParams.value), { name: role, new_name: newName }, { skipErrorToast: true })
        roles.value = data.roles ?? roles.value
        await load()
    } catch (e) {
        actionError.value = e?.response?.data?.message ?? $t('Failed to save')
    }
}

async function deleteRole(role) {
    if (!window.confirm($t('Delete the role “{role}”? The contacts stay in the list.', { role }))) return
    actionError.value = ''
    try {
        const { data } = await axios.delete(route('projects.components.crm-contacts.roles.destroy', routeParams.value), { data: { name: role }, skipErrorToast: true })
        roles.value = data.roles ?? roles.value
        contacts.value = contacts.value.map((contact) => ({ ...contact, roles: (contact.roles ?? []).filter((r) => r !== role) }))
    } catch (e) {
        actionError.value = e?.response?.data?.message ?? $t('Failed to save')
    }
}

// --- Suche bestehender Kontakte (als Rolle auch bereits verknüpfte, die die Rolle noch nicht haben) ---
const linkTarget = ref(null) // { role?: string, artist?: boolean }
const linkTargetLabel = computed(() => {
    if (!linkTarget.value) return ''
    if (linkTarget.value.artist) return $t('Artist')
    return linkTarget.value.role ?? ''
})
const searchQuery = ref('')
const searchTypeId = ref(null)
const searchResults = ref([])
const searching = ref(false)

function startLink(target) {
    linkTarget.value = target
    searchQuery.value = ''
    searchResults.value = []
}

const runSearch = debounce(async (query, typeId) => {
    try {
        const { data } = await axios.get(route('projects.components.crm-contacts.search', routeParams.value), {
            params: {
                search: query,
                contact_type_id: typeId ?? undefined,
                role: linkTarget.value?.role ?? undefined,
                artist: linkTarget.value?.artist ? 1 : undefined,
            },
        })
        if (query === searchQuery.value) searchResults.value = data ?? []
    } catch (e) {
        searchResults.value = []
    } finally {
        searching.value = false
    }
}, 300)

watch([searchQuery, searchTypeId], ([query, typeId]) => {
    if (!query) {
        searchResults.value = []
        return
    }
    searching.value = true
    runSearch(query, typeId)
})

// Treffer, die schon in der Liste stehen, bekommen beim Verknüpfen nur die Rolle dazu
function linkedHint(linked) {
    const roles = [...(linked.is_artist ? [$t('Artist')] : []), ...(linked.roles ?? [])]
    return roles.length
        ? $t('Already in the list as {roles}', { roles: roles.join(', ') })
        : $t('Already in the list')
}

function closeSearch() {
    linkTarget.value = null
    searchQuery.value = ''
    searchResults.value = []
}

async function link(result) {
    actionError.value = ''
    const payload = { crm_contact_id: result.id }
    if (linkTarget.value?.artist) payload.is_artist = true
    if (linkTarget.value?.role) payload.roles = [linkTarget.value.role]
    try {
        const { data } = await axios.post(route('projects.components.crm-contacts.link', routeParams.value), payload, { skipErrorToast: true }) // Fehler steht unter der Liste
        upsertContact(data.contact)
        closeSearch()
    } catch (e) {
        actionError.value = e?.response?.data?.message ?? $t('Failed to save')
    }
}

// Tab wurde intern bestätigt/zurückgegeben → Prüfstatus der Kontakte neu laden
const externalReviewVersion = inject('externalReviewVersion', ref(0))
watch(externalReviewVersion, () => load())

onMounted(load)
</script>
