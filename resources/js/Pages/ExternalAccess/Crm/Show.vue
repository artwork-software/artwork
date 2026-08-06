<template>
    <ExternalAppLayout :title="$t('My data')">
        <div class="px-8 py-10 max-w-4xl">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-text">{{ $t('My data') }}</h1>
                <Link
                    :href="route('external.crm.edit')"
                    class="rounded-lg bg-surface-inverse px-4 py-2 text-sm font-medium text-text-inverse"
                >
                    {{ $t('Edit') }}
                </Link>
            </div>

            <p
                v-if="submissionStatus && submissionStatus.has_pending"
                class="mt-4 rounded-xl border border-warning-border bg-warning-surface px-4 py-3 text-sm text-warning"
            >
                {{ $t('You have an open data update request. It is currently being reviewed.') }}
            </p>
            <p
                v-else-if="submissionStatus && ['rejected','partially_approved'].includes(submissionStatus.latest_status)"
                class="mt-4 rounded-xl border border-danger-border bg-danger-surface px-4 py-3 text-sm text-danger"
            >
                {{ $t('Your last changes were partially or fully declined.') }}
                <span v-if="submissionStatus.rejection_reason">— {{ submissionStatus.rejection_reason }}</span>
            </p>
            <p
                v-else-if="submissionStatus && submissionStatus.latest_status === 'approved'"
                class="mt-4 rounded-xl border border-success-border bg-success-surface px-4 py-3 text-sm text-success"
            >
                {{ $t('Your last changes have been applied.') }}
            </p>

            <section v-for="group in groups" :key="group.id" class="mt-10">
                <h2 class="text-lg font-semibold">{{ group.name }}</h2>
                <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3">
                    <template v-for="property in group.properties" :key="property.id">
                        <dt class="text-sm font-medium text-text-muted">{{ property.name }}</dt>
                        <dd class="text-sm text-text">{{ property.value ?? '—' }}</dd>
                    </template>
                </dl>
            </section>

            <section v-if="!groups.length" class="mt-10 text-sm text-text-subtle">
                {{ $t('You have no shared project tabs at the moment.') }}
            </section>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'

defineProps({
    groups: { type: Array, required: true },
    submissionStatus: { type: Object, default: null },
})
</script>
