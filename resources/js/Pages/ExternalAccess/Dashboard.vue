<template>
    <ExternalAppLayout :title="$t('Dashboard')">
        <div class="px-8 py-10 max-w-4xl">
            <h1 class="text-2xl font-bold text-zinc-900">
                {{ $t('Welcome back, {name}', { name: page.props.auth.external.display_name }) }}
            </h1>

            <p v-if="page.props.auth.external.crm_access_expires_at" class="mt-2 text-sm text-zinc-600">
                {{ $t('Your CRM access is valid until') }}: {{ formatDate(page.props.auth.external.crm_access_expires_at) }}
            </p>

            <section v-if="page.props.accessible_scopes?.length" class="mt-10">
                <h2 class="text-lg font-semibold">{{ $t('Shared with you') }}</h2>
                <ul class="mt-4 space-y-2">
                    <li v-for="scope in page.props.accessible_scopes" :key="scope.id" class="text-sm text-zinc-700">
                        <strong>{{ scope.project.name }}</strong>{{ ' — ' }}{{ scope.tab.name }}
                        <span class="text-xs text-zinc-500 ml-2">
                            ({{ scope.access_type === 'write' ? $t('can edit') : $t('read only') }})
                        </span>
                    </li>
                </ul>
            </section>

            <section v-else class="mt-10 text-sm text-zinc-500">
                {{ $t('You have no shared project tabs at the moment.') }}
            </section>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'

const page = usePage()

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}
</script>
