<template>
    <AppLayout :title="$t('Review external submission')">
        <div class="mx-auto max-w-5xl mt-6 px-6 pb-20">
            <header>
                <h1 class="text-xl font-semibold">
                    {{ $t('Review submission from {name}', { name: submission.external_access.display_name ?? submission.external_access.email }) }}
                </h1>
                <p class="text-sm text-zinc-500 mt-1">
                    {{ $t('Submitted on {date}', { date: formatDate(submission.submitted_at) }) }}
                    · {{ submission.external_access.email }}
                </p>
            </header>

            <section class="mt-8 rounded-2xl border border-zinc-200 bg-white overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
                            <th class="px-4 py-3">{{ $t('Field') }}</th>
                            <th class="px-4 py-3">{{ $t('Current') }}</th>
                            <th class="px-4 py-3">{{ $t('Proposed') }}</th>
                            <th class="px-4 py-3">{{ $t('Decision') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="change in submission.field_changes" :key="change.id" class="border-b border-zinc-100">
                            <td class="px-4 py-3 text-sm font-medium">{{ change.field_label }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-600">{{ displayValue(change.old_value) }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900">{{ displayValue(change.new_value) }}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-2" v-if="change.approval_status === 'pending' && submission.status === 'pending'">
                                    <button @click="setDecision(change.id, 'approved')" :class="decisionButtonClass(change.id, 'approved')">
                                        {{ $t('Accept') }}
                                    </button>
                                    <button @click="setDecision(change.id, 'rejected')" :class="decisionButtonClass(change.id, 'rejected')">
                                        {{ $t('Reject') }}
                                    </button>
                                </div>
                                <span v-else class="text-xs uppercase text-zinc-500">{{ change.approval_status }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <div class="mt-6 flex flex-wrap gap-3 justify-end" v-if="submission.status === 'pending'">
                <button class="ui-button-cancel" @click="rejectAll">{{ $t('Reject all') }}</button>
                <button class="ui-button" :disabled="!hasAnyDecision" @click="applyPartial">{{ $t('Apply decisions') }}</button>
                <button class="ui-button-add" @click="approveAll">{{ $t('Approve all') }}</button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
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
            ? (type === 'approved' ? 'bg-emerald-50 border-emerald-300 text-emerald-700' : 'bg-red-50 border-red-300 text-red-700')
            : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50',
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

function displayValue(value) {
    if (value === null || value === '' || value === undefined) return '—'
    return value
}

function formatDate(iso) {
    return new Date(iso).toLocaleString()
}
</script>
