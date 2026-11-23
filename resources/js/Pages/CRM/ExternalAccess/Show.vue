<template>
    <AppLayout :title="$t('External access')">
        <div class="mx-auto max-w-5xl mt-6 px-6 pb-20 space-y-8">
            <!-- Header -->
            <header class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ access.display_name ?? access.crm_contact?.display_name ?? access.email }}</h1>
                    <p class="text-sm text-text-subtle">{{ access.email }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <StatusBadge :status="resolveStatus()" />
                        <span class="text-xs text-text-subtle">
                            {{ $t('Invited') }}: {{ formatDate(access.created_at) }}
                            · {{ $t('Last login') }}: {{ formatDate(access.last_login_at) ?? $t('Never') }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2" v-if="canManage">
                    <BaseUIButton v-if="!access.revoked_at" variant="secondary" hide-icon @click="resendInvitation">{{ $t('Resend invitation') }}</BaseUIButton>
                    <BaseUIButton v-if="!access.revoked_at" variant="secondary" hide-icon @click="revokeOpen = true">{{ $t('Revoke access') }}</BaseUIButton>
                    <BaseUIButton v-else hide-icon @click="reactivate">{{ $t('Reactivate') }}</BaseUIButton>
                    <BaseUIButton v-if="canRelink" hide-icon @click="relinkOpen = true">{{ $t('Re-link to another contact') }}</BaseUIButton>
                </div>
            </header>

            <!-- CRM access -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6">
                <header class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ $t('CRM access') }}</h2>
                    <BaseUIButton v-if="canManage && access.crm_contact" hide-icon @click="extendCrmOpen = true">{{ $t('Extend') }}</BaseUIButton>
                </header>
                <p v-if="access.crm_contact" class="mt-4 text-sm">
                    <strong>{{ $t('Valid until') }}:</strong>
                    {{ formatDate(access.crm_access_expires_at) ?? $t('No expiry') }}
                </p>
                <p v-else class="mt-4 text-sm text-text-subtle">
                    {{ $t('Tab access only (no own CRM contact)') }}
                </p>
            </section>

            <!-- Tab scopes -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $t('Tab access') }}</h2>
                <table v-if="scopes.length" class="w-full mt-4">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-text-subtle border-b">
                            <th class="py-2">{{ $t('Project') }}</th>
                            <th class="py-2">{{ $t('Tab') }}</th>
                            <th class="py-2">{{ $t('Access') }}</th>
                            <th class="py-2">{{ $t('Valid') }}</th>
                            <th class="py-2">{{ $t('Last submitted') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="scope in scopes" :key="scope.id" class="border-b border-border-subtle">
                            <td class="py-3 text-sm">{{ scope.project.name }}</td>
                            <td class="py-3 text-sm">{{ scope.tab.name }}</td>
                            <td class="py-3 text-sm">
                                <span :class="scope.access_type === 'write' ? 'text-success' : 'text-text-subtle'">
                                    {{ scope.access_type === 'write' ? $t('Read & write') : $t('Read only') }}
                                </span>
                            </td>
                            <td class="py-3 text-sm">
                                {{ formatDate(scope.valid_from) }} – {{ formatDate(scope.valid_to) }}
                                <span v-if="isExpired(scope)" class="text-xs text-text-subtle ml-1">({{ $t('expired') }})</span>
                            </td>
                            <td class="py-3 text-sm">
                                {{ formatDateTime(scope.last_submitted_at) || $t('Not submitted yet') }}
                            </td>
                            <td class="py-3 text-right" v-if="canManage">
                                <div class="flex justify-end gap-2">
                                    <button class="text-xs text-accent-600 hover:underline" @click="openExtendScope(scope)">{{ $t('Extend') }}</button>
                                    <button class="text-xs text-accent-600 hover:underline" @click="toggleAccessType(scope)">
                                        {{ scope.access_type === 'write' ? $t('Make read-only') : $t('Make writable') }}
                                    </button>
                                    <button class="text-xs text-danger hover:underline" @click="endScope(scope)">{{ $t('End now') }}</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="mt-4 text-sm text-text-subtle">{{ $t('No tab access granted.') }}</p>
            </section>

            <!-- Submissions -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $t('Recent submissions') }}</h2>
                <ul v-if="submissions.length" class="mt-4 space-y-2">
                    <li v-for="s in submissions" :key="s.id" class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span>{{ formatDate(s.submitted_at) }}</span>
                            <span class="text-text-subtle">{{ s.field_count }} {{ $t('changes') }}</span>
                            <StatusBadge :status="s.status" />
                        </div>
                        <Link
                            v-if="s.status === 'pending'"
                            :href="route('crm.contacts.external-submissions.show', [access.crm_contact_id, s.id])"
                            class="text-accent-600 hover:underline text-xs"
                        >{{ $t('Review') }}</Link>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-text-subtle">{{ $t('No submissions yet.') }}</p>
            </section>

            <!-- Audit log -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $t('Audit log') }}</h2>
                <ol v-if="auditLog.length" class="mt-4 space-y-3">
                    <li v-for="(entry, idx) in auditLog" :key="idx" class="flex gap-3 text-sm">
                        <div class="w-40 text-text-subtle shrink-0">{{ formatDateTime(entry.created_at) }}</div>
                        <div class="flex-1">
                            <span class="font-medium">{{ humanize(entry.event) }}</span>
                            <span v-if="entry.causer.is_external" class="ml-1 text-xs bg-warning-surface text-warning rounded px-1.5">{{ $t('External') }}</span>
                            <span class="ml-1 text-text-subtle">— {{ entry.causer.label }}</span>
                        </div>
                    </li>
                </ol>
                <p v-else class="mt-4 text-sm text-text-subtle">{{ $t('No activity yet.') }}</p>
            </section>
        </div>

        <!-- Revoke dialog -->
        <ArtworkBaseModal v-if="revokeOpen" :title="$t('Revoke access')" :description="''" @close="revokeOpen = false">
            <div class="mt-4 space-y-3">
                <label class="componentLabel">{{ $t('Reason for revoke') }}</label>
                <BaseTextarea id="revoke_reason" v-model="revokeReason" :rows="3" label="" />
                <p v-if="dialogError" class="text-sm text-danger">{{ dialogError }}</p>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <BaseUIButton variant="secondary" hide-icon @click="revokeOpen = false">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton variant="primary" hide-icon @click="revoke">{{ $t('Revoke access') }}</BaseUIButton>
            </div>
        </ArtworkBaseModal>

        <!-- Extend CRM access dialog -->
        <ArtworkBaseModal v-if="extendCrmOpen" :title="$t('Extend')" :description="''" @close="extendCrmOpen = false">
            <div class="mt-4 space-y-3">
                <label class="componentLabel">{{ $t('New expiry date') }}</label>
                <BaseInput id="extend_crm_date" type="date" v-model="extendCrmDate" label="" no-margin-top />
                <p v-if="dialogError" class="text-sm text-danger">{{ dialogError }}</p>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <BaseUIButton variant="secondary" hide-icon @click="extendCrmOpen = false">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton variant="primary" hide-icon :disabled="!extendCrmDate" @click="extendCrm">{{ $t('Extend') }}</BaseUIButton>
            </div>
        </ArtworkBaseModal>

        <!-- Extend scope dialog -->
        <ArtworkBaseModal v-if="extendScopeOpen" :title="$t('Extend')" :description="''" @close="extendScopeOpen = false">
            <div class="mt-4 space-y-3">
                <label class="componentLabel">{{ $t('New expiry date') }}</label>
                <BaseInput id="extend_scope_date" type="date" v-model="extendScopeDate" label="" no-margin-top />
                <p v-if="dialogError" class="text-sm text-danger">{{ dialogError }}</p>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <BaseUIButton variant="secondary" hide-icon @click="extendScopeOpen = false">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton variant="primary" hide-icon :disabled="!extendScopeDate" @click="extendScope">{{ $t('Extend') }}</BaseUIButton>
            </div>
        </ArtworkBaseModal>

        <!-- Relink dialog -->
        <ArtworkBaseModal v-if="relinkOpen" :title="$t('Re-link to another contact')" :description="''" @close="relinkOpen = false">
            <div class="mt-4 space-y-3">
                <!-- Gewählter Kontakt -->
                <div v-if="relinkContact" class="flex items-center justify-between gap-3 rounded-lg border border-border-subtle px-3 py-2">
                    <div class="min-w-0">
                        <div class="text-xs text-text-subtle">{{ $t('Selected contact') }}</div>
                        <div class="truncate text-sm font-medium text-text">
                            {{ relinkContact.display_name }}
                            <span v-if="relinkContact.contact_type" class="font-normal text-text-subtle">· {{ $t(relinkContact.contact_type.name) }}</span>
                        </div>
                    </div>
                    <button type="button" class="text-sm text-accent-600 hover:underline shrink-0" @click="relinkContact = null">
                        {{ $t('Change') }}
                    </button>
                </div>

                <!-- Suche nach Name statt Eingabe einer Kontakt-ID -->
                <template v-else>
                    <BaseInput id="relink_search" v-model="relinkSearch" :label="$t('Search contact to link to')" />
                    <div class="max-h-56 overflow-y-auto">
                        <p v-if="relinkSearching" class="py-3 text-center text-sm text-text-subtle">{{ $t('Searching...') }}</p>
                        <p v-else-if="relinkSearched && !relinkResults.length" class="py-3 text-center text-sm text-text-subtle">
                            {{ $t('No contacts found.') }}
                        </p>
                        <div v-else class="divide-y divide-border-subtle">
                            <button
                                v-for="contact in relinkResults"
                                :key="contact.id"
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-surface-sunken"
                                @click="relinkContact = contact"
                            >
                                <span class="truncate text-sm font-medium text-text">{{ contact.display_name }}</span>
                                <span v-if="contact.contact_type" class="shrink-0 text-xs text-text-subtle">{{ $t(contact.contact_type.name) }}</span>
                            </button>
                        </div>
                    </div>
                </template>
                <p v-if="dialogError" class="text-sm text-danger">{{ dialogError }}</p>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <BaseUIButton variant="secondary" hide-icon @click="relinkOpen = false">{{ $t('Cancel') }}</BaseUIButton>
                <BaseUIButton variant="primary" hide-icon :disabled="!relinkContact" @click="relink">{{ $t('Confirm re-linking') }}</BaseUIButton>
            </div>
        </ArtworkBaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import debounce from 'lodash.debounce'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import StatusBadge from './Components/StatusBadge.vue'

const $t = useTranslation()

const props = defineProps({
    access: { type: Object, required: true },
    scopes: { type: Array, required: true },
    submissions: { type: Array, required: true },
    auditLog: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    canRelink: { type: Boolean, default: false },
})

const revokeOpen = ref(false)
const revokeReason = ref('')
const extendCrmOpen = ref(false)
const extendCrmDate = ref('')
const extendScopeOpen = ref(false)
const extendScopeDate = ref('')
const scopeBeingEdited = ref(null)
const relinkOpen = ref(false)
const relinkContact = ref(null)
const relinkSearch = ref('')
const relinkResults = ref([])
const relinkSearching = ref(false)
const relinkSearched = ref(false)
let relinkSearchRequest = 0

// Fehler der Dialog-Aktionen (Server-Validierung); vorher blieb der Dialog kommentarlos offen
const dialogError = ref('')
const opts = {
    preserveScroll: true,
    onBefore: () => { dialogError.value = '' },
    onError: (errors) => { dialogError.value = Object.values(errors ?? {})[0] ?? $t('Failed to save') },
}

watch([revokeOpen, extendCrmOpen, extendScopeOpen, relinkOpen], () => { dialogError.value = '' })

watch(relinkOpen, (open) => {
    if (!open) return
    relinkContact.value = null
    relinkSearch.value = ''
    relinkResults.value = []
    relinkSearched.value = false
})

const searchRelinkContacts = debounce(async (term) => {
    const requestId = ++relinkSearchRequest
    if (term.trim().length < 2) {
        relinkResults.value = []
        relinkSearched.value = false
        relinkSearching.value = false
        return
    }
    relinkSearching.value = true
    try {
        const { data } = await axios.get(route('crm.contacts.search'), { params: { search: term.trim() } })
        if (requestId !== relinkSearchRequest) return
        // Der aktuell verknüpfte Kontakt ist kein sinnvolles Ziel
        relinkResults.value = (data ?? []).filter((contact) => contact.id !== (props.access.crm_contact?.id ?? props.access.crm_contact_id))
        relinkSearched.value = true
    } catch {
        if (requestId !== relinkSearchRequest) return
        relinkResults.value = []
        relinkSearched.value = true
    } finally {
        if (requestId === relinkSearchRequest) relinkSearching.value = false
    }
}, 300)

watch(relinkSearch, (term) => searchRelinkContacts(term ?? ''))

function resolveStatus() {
    if (props.access.revoked_at) return 'revoked'
    if (props.access.crm_access_expires_at && new Date(props.access.crm_access_expires_at) < new Date()) return 'expired'
    return 'active'
}

function isExpired(scope) {
    return new Date(scope.valid_to) < new Date()
}

function revoke() {
    router.post(route('crm.external-access.revoke', props.access.id), { reason: revokeReason.value }, {
        ...opts, onSuccess: () => { revokeOpen.value = false },
    })
}

function reactivate() {
    router.post(route('crm.external-access.reactivate', props.access.id), {}, opts)
}

function resendInvitation() {
    if (!window.confirm($t('Resend invitation') + '?')) return
    router.post(route('crm.external-access.resend-invitation', props.access.id), {}, opts)
}

function extendCrm() {
    router.patch(route('crm.external-access.extend-crm-access', props.access.id), { expires_at: extendCrmDate.value }, {
        ...opts, onSuccess: () => { extendCrmOpen.value = false },
    })
}

function openExtendScope(scope) {
    scopeBeingEdited.value = scope
    extendScopeDate.value = ''
    extendScopeOpen.value = true
}

function extendScope() {
    router.patch(route('crm.external-access.scope.update', [props.access.id, scopeBeingEdited.value.id]), {
        valid_to: extendScopeDate.value,
    }, { ...opts, onSuccess: () => { extendScopeOpen.value = false } })
}

function toggleAccessType(scope) {
    router.patch(route('crm.external-access.scope.update', [props.access.id, scope.id]), {
        access_type: scope.access_type === 'write' ? 'read' : 'write',
    }, opts)
}

function endScope(scope) {
    if (!window.confirm($t('End now') + '?')) return
    router.post(route('crm.external-access.scope.end', [props.access.id, scope.id]), {}, opts)
}

function relink() {
    router.post(route('crm.external-access.relink', props.access.id), { new_crm_contact_id: relinkContact.value.id }, {
        ...opts, onSuccess: () => { relinkOpen.value = false },
    })
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString() : null
}

function formatDateTime(iso) {
    return iso ? new Date(iso).toLocaleString() : ''
}

function humanize(event) {
    return $t(String(event ?? '').replaceAll('_', ' '))
}
</script>
