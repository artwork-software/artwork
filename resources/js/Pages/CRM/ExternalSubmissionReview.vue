<template>
    <AppLayout :title="$t('Review external submission')">
        <div class="mx-auto max-w-5xl mt-6 px-6 pb-20">
            <header>
                <h1 class="text-xl font-semibold">
                    {{ $t('Review submission from {name}', { name: submission.external_access.display_name ?? submission.external_access.email }) }}
                </h1>
                <p class="text-sm text-text-subtle mt-1">
                    {{ $t('Submitted on {date}', { date: formatDate(submission.submitted_at) }) }}
                    · {{ submission.external_access.email }}
                </p>
            </header>

            <section class="mt-8 rounded-2xl border border-border-subtle bg-white overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-text-subtle border-b border-border-subtle">
                            <th class="px-4 py-3">{{ $t('Field') }}</th>
                            <th class="px-4 py-3">{{ $t('Current') }}</th>
                            <th class="px-4 py-3">{{ $t('Proposed') }}</th>
                            <th class="px-4 py-3">{{ $t('Decision') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="change in submission.field_changes" :key="change.id" class="border-b border-border-subtle">
                            <td class="px-4 py-3 text-sm font-medium">{{ change.field_label }}</td>
                            <td class="px-4 py-3 text-sm text-text-muted">{{ displayValue(change.old_value, change.field_type) }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-text">
                                <!-- Upload-Eigenschaft: vorgeschlagene Datei ansehen/herunterladen -->
                                <template v-if="change.file_change?.kind === 'upload'">
                                    <a
                                        v-if="change.file_change.url"
                                        :href="change.file_change.url + '?inline=1'"
                                        target="_blank"
                                        rel="noopener"
                                        class="break-all text-accent-700 hover:underline"
                                    >
                                        {{ change.file_change.name }}
                                    </a>
                                    <span v-else class="break-all">{{ change.file_change.name }}</span>
                                </template>
                                <span v-else-if="change.file_change?.kind === 'remove'" class="text-danger">
                                    {{ $t('Remove file') }}
                                </span>
                                <template v-else>{{ displayValue(change.new_value, change.field_type) }}</template>
                                <p v-if="change.not_applicable_reason" class="mt-1 text-xs font-normal text-warning">
                                    {{ notApplicableLabel(change.not_applicable_reason) }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-2" v-if="change.approval_status === 'pending' && submission.status === 'pending'">
                                    <button
                                        v-if="!change.not_applicable_reason"
                                        @click="setDecision(change.id, 'approved')"
                                        :class="decisionButtonClass(change.id, 'approved')"
                                    >
                                        {{ $t('Accept') }}
                                    </button>
                                    <button @click="setDecision(change.id, 'rejected')" :class="decisionButtonClass(change.id, 'rejected')">
                                        {{ $t('Reject') }}
                                    </button>
                                </div>
                                <span v-else-if="change.not_applicable_reason === 'legacy_upload'" class="text-xs text-text-subtle">
                                    {{ $t('Not applicable (legacy entry)') }}
                                </span>
                                <span v-else class="text-xs uppercase text-text-subtle">{{ $t(change.approval_status) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <div class="mt-6 flex flex-wrap gap-3 justify-end" v-if="submission.status === 'pending'">
                <BaseUIButton variant="secondary" hide-icon @click="rejectAll">{{ $t('Reject all') }}</BaseUIButton>
                <BaseUIButton hide-icon :disabled="!hasAnyDecision" @click="applyPartial">{{ $t('Apply decisions') }}</BaseUIButton>
                <BaseUIButton variant="primary" hide-icon @click="approveAll">{{ $t('Approve all') }}</BaseUIButton>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const props = defineProps({
    contact: { type: Object, required: true },
    submission: { type: Object, required: true },
})

const $t = useTranslation()
const decisions = ref({})

const hasAnyDecision = computed(() => Object.keys(decisions.value).length > 0)

function setDecision(fieldChangeId, decision) {
    if (decisions.value[fieldChangeId] === decision) {
        delete decisions.value[fieldChangeId]
    } else {
        decisions.value[fieldChangeId] = decision
    }
}

function decisionButtonClass(id, type) {
    const isActive = decisions.value[id] === type
    return [
        'px-3 py-1 text-xs rounded-md border',
        isActive
            ? (type === 'approved' ? 'bg-success-surface border-success-border text-success' : 'bg-danger-surface border-danger-border text-danger')
            : 'border-border-subtle text-text-muted hover:bg-surface-sunken',
    ]
}

function routeArgs() {
    return [props.contact.id, props.submission.id]
}

function approveAll() {
    router.post(route('crm.contacts.external-submissions.approve-all', routeArgs()))
}

function rejectAll() {
    const reason = window.prompt($t('Reason (optional):')) ?? null
    router.post(route('crm.contacts.external-submissions.reject-all', routeArgs()), { reason })
}

function applyPartial() {
    router.post(route('crm.contacts.external-submissions.partial-decisions', routeArgs()), {
        decisions: Object.entries(decisions.value).map(([id, decision]) => ({
            field_change_id: parseInt(id),
            decision,
        })),
    })
}

function notApplicableLabel(reason) {
    return reason === 'legacy_upload'
        ? $t('Not applicable (legacy entry): file paths are never taken over. "Approve all" skips this row.')
        : $t('The proposed file is no longer available. "Approve all" skips this row.')
}

// Checkboxen werden wie intern als '1'/'0' gespeichert
function displayValue(value, fieldType) {
    if (fieldType === 'checkbox') return value === '1' ? $t('Yes') : $t('No')
    if (value === null || value === '' || value === undefined) return '—'
    return value
}

function formatDate(iso) {
    return new Date(iso).toLocaleString()
}
</script>
