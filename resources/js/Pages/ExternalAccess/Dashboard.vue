<template>
    <ExternalAppLayout :title="$t('Dashboard')">
        <div class="px-4 py-6 sm:px-8 sm:py-10 max-w-4xl">
            <h1 class="text-xl sm:text-2xl font-bold text-text break-words">
                {{ $t('Welcome back, {name}', { name: page.props.auth.external.display_name }) }}
            </h1>

            <p v-if="page.props.auth.external.crm_access_expires_at" class="mt-2 text-sm text-text-muted">
                {{ $t('Your CRM access is valid until') }}: {{ formatDate(page.props.auth.external.crm_access_expires_at) }}
            </p>

            <section v-if="page.props.accessible_scopes?.length" class="mt-8 sm:mt-10">
                <h2 class="text-lg font-semibold">{{ $t('Shared with you') }}</h2>
                <ul class="mt-4 space-y-2">
                    <li v-for="scope in page.props.accessible_scopes" :key="scope.id" class="text-sm text-text-muted">
                        <Link
                            :href="route('external.project.tab.show', { project: scope.project.id, tab: scope.tab.id })"
                            class="inline-flex flex-wrap items-center gap-x-2 py-1 hover:text-text hover:underline"
                        >
                            <span><strong>{{ scope.project.name }}</strong>{{ ' — ' }}{{ scope.tab.name }}</span>
                            <span class="text-xs text-text-subtle">
                                ({{ scope.access_type === 'write' ? $t('can edit') : $t('read only') }})
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section v-else class="mt-10 text-sm text-text-subtle">
                {{ $t('You have no shared project tabs at the moment.') }}
            </section>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'

const page = usePage()

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}
</script>
