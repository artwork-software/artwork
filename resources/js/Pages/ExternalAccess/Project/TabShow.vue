<template>
    <ExternalAppLayout :title="`${project.name} — ${tab.name}`">
        <div class="px-8 py-10 max-w-5xl mx-auto">
            <header class="mb-8">
                <p class="text-sm text-zinc-500">{{ project.name }}</p>
                <h1 class="text-2xl font-bold text-zinc-900 mt-1">{{ tab.name }}</h1>
                <p class="text-xs text-zinc-500 mt-2">
                    <span v-if="scope.access_type === 'read'">{{ $t('Read only') }}</span>
                    <span v-else>{{ $t('You can edit components in this tab') }}</span>
                    <span> · {{ $t('Access valid until') }}: {{ formatDate(scope.valid_to) }}</span>
                </p>
            </header>

            <div v-if="components.length === 0" class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">
                {{ $t('No externally available content') }}
            </div>

            <div v-else class="space-y-6">
                <ExternalComponentRenderer
                    v-for="comp in components"
                    :key="comp.component_in_tab_id"
                    :component="comp"
                    :project-id="project.id"
                    :tab-id="tab.id"
                    :scope="scope"
                />
            </div>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'
import ExternalComponentRenderer from '@/Pages/ExternalAccess/Project/Components/ExternalComponentRenderer.vue'

defineProps({
    project: { type: Object, required: true },
    tab: { type: Object, required: true },
    scope: { type: Object, required: true },
    components: { type: Array, required: true },
})

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}
</script>
