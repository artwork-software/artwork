<template>
    <AppLayout :title="$t('External access')">
        <div class="mx-auto max-w-7xl mt-6 px-6 pb-20">
            <header class="flex items-center justify-between mb-8">
                <h1 class="text-xl font-semibold">{{ $t('External access overview') }}</h1>
                <div class="flex gap-1">
                    <button
                        v-for="opt in statusOptions"
                        :key="opt.value"
                        @click="setStatus(opt.value)"
                        :class="[
                            'px-3 py-1.5 text-sm rounded-md border',
                            statusFilter === opt.value ? 'bg-zinc-900 text-white border-zinc-900' : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50',
                        ]"
                    >
                        {{ $t(opt.label) }}
                    </button>
                </div>
            </header>

            <section class="rounded-2xl border border-zinc-200 bg-white overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-zinc-500 border-b border-zinc-200">
                            <th class="px-4 py-3">{{ $t('External') }}</th>
                            <th class="px-4 py-3">{{ $t('Linked to') }}</th>
                            <th class="px-4 py-3">{{ $t('Status') }}</th>
                            <th class="px-4 py-3">{{ $t('CRM access until') }}</th>
                            <th class="px-4 py-3">{{ $t('Scopes') }}</th>
                            <th class="px-4 py-3">{{ $t('Invited by') }}</th>
                            <th class="px-4 py-3">{{ $t('Last login') }}</th>
                            <th class="px-4 py-3 w-px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="access in accesses.data" :key="access.id" class="border-b border-zinc-100">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ access.crm_contact?.display_name }}</div>
                                <div class="text-xs text-zinc-500">{{ access.email }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div>{{ access.crm_contact?.display_name }}</div>
                                <div class="text-xs text-zinc-500">{{ entityTypeLabel(access.crm_contact) }}</div>
                            </td>
                            <td class="px-4 py-3"><StatusBadge :status="resolveStatus(access)" /></td>
                            <td class="px-4 py-3 text-sm">{{ formatDate(access.crm_access_expires_at) ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ access.scopes_count }}</td>
                            <td class="px-4 py-3 text-sm">{{ inviterLabel(access.invited_by) }}</td>
                            <td class="px-4 py-3 text-sm">{{ formatDate(access.last_login_at) ?? $t('Never') }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('crm.external-access.show', access.id)" class="text-blue-600 hover:underline text-sm">
                                    {{ $t('Details') }}
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!accesses.data.length">
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-zinc-500">{{ $t('No activity yet.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <div v-if="accesses.last_page > 1" class="mt-6 flex justify-center gap-1">
                <Link
                    v-for="link in accesses.links"
                    :key="link.label"
                    :href="link.url ?? ''"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 text-sm rounded-md border',
                        link.active ? 'bg-zinc-900 text-white border-zinc-900' : 'border-zinc-200 text-zinc-600',
                        !link.url ? 'pointer-events-none opacity-40' : 'hover:bg-zinc-50',
                    ]"
                    preserve-scroll
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import StatusBadge from './Components/StatusBadge.vue'

const $t = useTranslation()

const props = defineProps({
    accesses: { type: Object, required: true },
    filters: { type: Object, required: true },
})

const statusFilter = ref(props.filters.status ?? 'all')

const statusOptions = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'expired', label: 'Expired' },
    { value: 'revoked', label: 'Revoked' },
]

function setStatus(value) {
    statusFilter.value = value
    router.get(route('crm.external-access.index'), {
        status: value === 'all' ? undefined : value,
    }, { preserveState: true, preserveScroll: true })
}

function resolveStatus(access) {
    if (access.revoked_at) return 'revoked'
    if (access.crm_access_expires_at && new Date(access.crm_access_expires_at) < new Date()) return 'expired'
    return 'active'
}

function formatDate(iso) {
    return iso ? new Date(iso).toLocaleDateString() : null
}

function inviterLabel(inviter) {
    return inviter ? `${inviter.first_name} ${inviter.last_name}` : '—'
}

function entityTypeLabel(contact) {
    if (!contact?.entity_type) return null
    const segments = contact.entity_type.split('\\')
    return segments[segments.length - 1]
}
</script>
