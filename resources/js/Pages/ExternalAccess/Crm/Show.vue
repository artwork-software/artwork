<template>
    <ExternalAppLayout :title="$t('My data')">
        <div class="px-8 py-10 max-w-4xl">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-zinc-900">{{ $t('My data') }}</h1>
                <Link
                    :href="route('external.crm.edit')"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white"
                >
                    {{ $t('Edit') }}
                </Link>
            </div>

            <p
                v-if="submissionStatus && submissionStatus.has_pending"
                class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                {{ $t('You have an open data update request. It is currently being reviewed.') }}
            </p>
            <p
                v-else-if="submissionStatus && ['rejected','partially_approved'].includes(submissionStatus.latest_status)"
                class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                {{ $t('Your last changes were partially or fully declined.') }}
                <span v-if="submissionStatus.rejection_reason">— {{ submissionStatus.rejection_reason }}</span>
            </p>
            <p
                v-else-if="submissionStatus && submissionStatus.latest_status === 'approved'"
                class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ $t('Your last changes have been applied.') }}
            </p>

            <section v-for="group in groups" :key="group.id" class="mt-10">
                <h2 class="text-lg font-semibold">{{ group.name }}</h2>
                <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3">
                    <template v-for="property in group.properties" :key="property.id">
                        <dt class="text-sm font-medium text-zinc-600">{{ property.name }}</dt>
                        <dd class="text-sm text-zinc-900">{{ property.value ?? '—' }}</dd>
                    </template>
                </dl>
            </section>

            <section v-if="!groups.length" class="mt-10 text-sm text-zinc-500">
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
