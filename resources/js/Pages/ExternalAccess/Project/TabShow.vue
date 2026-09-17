<template>
    <ExternalAppLayout :title="`${project.name} — ${tab.name}`">
        <div class="px-8 py-10 max-w-5xl mx-auto">
            <header class="mb-8">
                <p class="text-sm text-text-subtle">{{ project.name }}</p>
                <h1 class="text-2xl font-bold text-text mt-1">{{ tab.name }}</h1>
                <p class="text-xs text-text-subtle mt-2">
                    <span v-if="scope.access_type === 'read'">{{ $t('Read only') }}</span>
                    <span v-else>{{ $t('You can edit components in this tab') }}</span>
                    <span> · {{ $t('Access valid until') }}: {{ formatDate(scope.valid_to) }}</span>
                </p>
            </header>

            <div v-if="components.length === 0" class="rounded-2xl border border-dashed border-border bg-white p-8 text-center text-sm text-text-subtle">
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

            <!-- Absenden: Eingaben sind bereits gespeichert, erst hier wird das Haus benachrichtigt -->
            <div v-if="scope.access_type === 'write' && components.length" class="mt-10 rounded-2xl border border-border-subtle bg-white p-5">
                <p v-if="flashStatus" class="mb-4 rounded-xl border border-success-border bg-success-surface px-4 py-3 text-sm text-success">
                    {{ flashStatus }}
                </p>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-text-muted">
                        <p>{{ $t('Your entries are saved automatically. Once everything is complete, submit your data so the inviting person is notified.') }}</p>
                        <p v-if="scope.last_submitted_at" class="mt-1 text-xs text-text-subtle">
                            {{ $t('Last submitted') }}: {{ formatDateTime(scope.last_submitted_at) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="submitting"
                        class="shrink-0 rounded-lg bg-surface-inverse px-4 py-2 text-sm font-medium text-white disabled:bg-border-strong disabled:cursor-not-allowed"
                        @click="submitData"
                    >
                        {{ scope.last_submitted_at ? $t('Submit data again') : $t('Submit data') }}
                    </button>
                </div>
            </div>
        </div>
    </ExternalAppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import ExternalAppLayout from '@/Pages/ExternalAccess/Layouts/ExternalAppLayout.vue'
import ExternalComponentRenderer from '@/Pages/ExternalAccess/Project/Components/ExternalComponentRenderer.vue'

const props = defineProps({
    project: { type: Object, required: true },
    tab: { type: Object, required: true },
    scope: { type: Object, required: true },
    components: { type: Array, required: true },
})

const $t = useTranslation()
const page = usePage()
const flashStatus = computed(() => page.props.flash?.status ?? null)
const submitting = ref(false)

function submitData() {
    if (!window.confirm($t('Submit your data now? The inviting person will be notified.'))) return
    submitting.value = true
    router.post(
        route('external.project.tab.submit', { project: props.project.id, tab: props.tab.id }),
        {},
        { preserveScroll: true, onFinish: () => { submitting.value = false } },
    )
}

function formatDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}

function formatDateTime(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleString()
}
</script>
