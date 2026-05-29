<template>
    <AppLayout :title="$t('External access')">
        <div class="mx-auto max-w-5xl mt-6 px-6 pb-20 space-y-8">
            <!-- Header -->
            <header class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ access.crm_contact.display_name }}</h1>
                    <p class="text-sm text-zinc-500">{{ access.email }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <StatusBadge :status="resolveStatus()" />
                        <span class="text-xs text-zinc-500">
                            {{ $t('Invited') }}: {{ formatDate(access.created_at) }}
                            · {{ $t('Last login') }}: {{ formatDate(access.last_login_at) ?? $t('Never') }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2" v-if="canManage">
                    <button v-if="!access.revoked_at" class="ui-button-cancel" @click="revokeOpen = true">{{ $t('Revoke access') }}</button>
                    <button v-else class="ui-button" @click="reactivate">{{ $t('Reactivate') }}</button>
                    <button v-if="canRelink" class="ui-button" @click="relinkOpen = true">{{ $t('Re-link to another contact') }}</button>
                </div>
            </header>

            <!-- CRM access -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                <header class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ $t('CRM access') }}</h2>
                    <button v-if="canManage" class="ui-button" @click="extendCrmOpen = true">{{ $t('Extend') }}</button>
                </header>
                <p class="mt-4 text-sm">
                    <strong>{{ $t('Valid until') }}:</strong>
                    {{ formatDate(access.crm_access_expires_at) ?? $t('No expiry') }}
                </p>
            </section>

            <!-- Tab scopes -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $t('Tab access') }}</h2>
                <table v-if="scopes.length" class="w-full mt-4">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-zinc-500 border-b">
                            <th class="py-2">{{ $t('Project') }}</th>
                            <th class="py-2">{{ $t('Tab') }}</th>
                            <th class="py-2">{{ $t('Access') }}</th>
                            <th class="py-2">{{ $t('Valid') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="scope in scopes" :key="scope.id" class="border-b border-zinc-100">
                            <td class="py-3 text-sm">{{ scope.project.name }}</td>
                            <td class="py-3 text-sm">{{ scope.tab.name }}</td>
                            <td class="py-3 text-sm">
                                <span :class="scope.access_type === 'write' ? 'text-emerald-700' : 'text-zinc-500'">
                                    {{ scope.access_type === 'write' ? $t('Read & write') : $t('Read only') }}
                                </span>
                            </td>
                            <td class="py-3 text-sm">
                                {{ formatDate(scope.valid_from) }} – {{ formatDate(scope.valid_to) }}
                                <span v-if="isExpired(scope)" class="text-xs text-zinc-400 ml-1">({{ $t('expired') }})</span>
                            </td>
                            <td class="py-3 text-right" v-if="canManage">
                                <div class="flex justify-end gap-2">
                                    <button class="text-xs text-blue-600 hover:underline" @click="openExtendScope(scope)">{{ $t('Extend') }}</button>
                                    <button class="text-xs text-blue-600 hover:underline" @click="toggleAccessType(scope)">
                                        {{ scope.access_type === 'write' ? $t('Make read-only') : $t('Make writable') }}
                                    </button>
                                    <button class="text-xs text-red-600 hover:underline" @click="endScope(scope)">{{ $t('End now') }}</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="mt-4 text-sm text-zinc-500">{{ $t('No tab access granted.') }}</p>
            </section>

            <!-- Submissions -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $t('Recent submissions') }}</h2>
                <ul v-if="submissions.length" class="mt-4 space-y-2">
                    <li v-for="s in submissions" :key="s.id" class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <span>{{ formatDate(s.submitted_at) }}</span>
                            <span class="text-zinc-500">{{ s.field_count }} {{ $t('changes') }}</span>
                            <StatusBadge :status="s.status" />
                        </div>
                        <Link
                            v-if="s.status === 'pending'"
                            :href="route('crm.contacts.external-submissions.show', [access.crm_contact_id, s.id])"
                            class="text-blue-600 hover:underline text-xs"
                        >{{ $t('Review') }}</Link>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-zinc-500">{{ $t('No submissions yet.') }}</p>
            </section>

            <!-- Audit log -->
            <section class="rounded-2xl border border-zinc-200 bg-white p-6">
                <h2 class="text-lg font-semibold">{{ $t('Audit log') }}</h2>
                <ol v-if="auditLog.length" class="mt-4 space-y-3">
                    <li v-for="(entry, idx) in auditLog" :key="idx" class="flex gap-3 text-sm">
                        <div class="w-40 text-zinc-500 shrink-0">{{ formatDateTime(entry.created_at) }}</div>
                        <div class="flex-1">
                            <span class="font-medium">{{ humanize(entry.event) }}</span>
                            <span v-if="entry.causer.is_external" class="ml-1 text-xs bg-amber-50 text-amber-700 rounded px-1.5">{{ $t('External') }}</span>
                            <span class="ml-1 text-zinc-500">— {{ entry.causer.label }}</span>
                        </div>
                    </li>
                </ol>
                <p v-else class="mt-4 text-sm text-zinc-500">{{ $t('No activity yet.') }}</p>
            </section>
        </div>

        <!-- Revoke dialog -->
        <ArtworkBaseModal v-if="revokeOpen" :title="$t('Revoke access')" :description="''" @close="revokeOpen = false">
            <div class="mt-4 space-y-3">
                <label class="componentLabel">{{ $t('Reason for revoke') }}</label>
                <textarea v-model="revokeReason" rows="3" class="block w-full rounded-lg border-zinc-300 text-sm" />
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button class="ui-button-cancel" @click="revokeOpen = false">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" @click="revoke">{{ $t('Revoke access') }}</button>
            </div>
        </ArtworkBaseModal>

        <!-- Extend CRM access dialog -->
        <ArtworkBaseModal v-if="extendCrmOpen" :title="$t('Extend')" :description="''" @close="extendCrmOpen = false">
            <div class="mt-4 space-y-3">
                <label class="componentLabel">{{ $t('New expiry date') }}</label>
                <input type="date" v-model="extendCrmDate" class="block w-full rounded-lg border-zinc-300 text-sm" />
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button class="ui-button-cancel" @click="extendCrmOpen = false">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" :disabled="!extendCrmDate" @click="extendCrm">{{ $t('Extend') }}</button>
            </div>
        </ArtworkBaseModal>

        <!-- Extend scope dialog -->
        <ArtworkBaseModal v-if="extendScopeOpen" :title="$t('Extend')" :description="''" @close="extendScopeOpen = false">
            <div class="mt-4 space-y-3">
                <label class="componentLabel">{{ $t('New expiry date') }}</label>
                <input type="date" v-model="extendScopeDate" class="block w-full rounded-lg border-zinc-300 text-sm" />
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button class="ui-button-cancel" @click="extendScopeOpen = false">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" :disabled="!extendScopeDate" @click="extendScope">{{ $t('Extend') }}</button>
            </div>
        </ArtworkBaseModal>

        <!-- Relink dialog -->
        <ArtworkBaseModal v-if="relinkOpen" :title="$t('Re-link to another contact')" :description="''" @close="relinkOpen = false">
            <div class="mt-4 space-y-3">
                <label class="componentLabel">{{ $t('Search contact to link to') }}</label>
                <input type="number" v-model="relinkContactId" :placeholder="$t('CRM contact ID')" class="block w-full rounded-lg border-zinc-300 text-sm" />
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button class="ui-button-cancel" @click="relinkOpen = false">{{ $t('Cancel') }}</button>
                <button class="ui-button-add" :disabled="!relinkContactId" @click="relink">{{ $t('Confirm re-linking') }}</button>
            </div>
        </ArtworkBaseModal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
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
const relinkContactId = ref('')

const opts = { preserveScroll: true }

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
    router.post(route('crm.external-access.relink', props.access.id), { new_crm_contact_id: relinkContactId.value }, {
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
