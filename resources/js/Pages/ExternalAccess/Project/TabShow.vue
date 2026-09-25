<template>
    <ExternalAppLayout :title="`${project.name} — ${tab.name}`">
        <div class="px-8 py-10 max-w-5xl mx-auto">
            <header class="mb-8">
                <p class="text-sm text-text-subtle">{{ project.name }}</p>
                <h1 class="text-2xl font-bold text-text mt-1">{{ tab.name }}</h1>
                <p class="text-xs text-text-subtle mt-2">
                    <span v-if="scope.access_type === 'read'">{{ $t('Read only') }}</span>
                    <span v-else-if="scope.locked">{{ $t('Submitted – read only') }}</span>
                    <span v-else>{{ $t('You can edit components in this tab') }}</span>
                    <span> · {{ $t('Access valid until') }}: {{ formatDate(scope.valid_to) }}</span>
                </p>
            </header>

            <!-- Stand der Abgabe -->
            <div v-if="scope.submission_status === 'submitted'" class="mb-6 rounded-2xl border border-info-border bg-info-surface px-5 py-4 text-sm text-info">
                <p class="font-semibold">{{ $t('Your data has been submitted') }}</p>
                <p class="mt-1">{{ $t('The inviting person is reviewing your entries. You will receive an email as soon as they have been confirmed or returned to you for revision.') }}</p>
            </div>
            <div v-else-if="scope.submission_status === 'confirmed'" class="mb-6 rounded-2xl border border-success-border bg-success-surface px-5 py-4 text-sm text-success">
                <p class="font-semibold">{{ $t('Your data has been confirmed') }}</p>
                <p class="mt-1">{{ $t('Thank you! Your entries have been confirmed by the inviting institution.') }}</p>
            </div>
            <div v-else-if="scope.submission_status === 'returned'" class="mb-6 rounded-2xl border border-warning-border bg-warning-surface px-5 py-4 text-sm text-warning">
                <p class="font-semibold">{{ $t('Your data has been returned for revision') }}</p>
                <p v-if="scope.review_comment" class="mt-1 whitespace-pre-line">{{ scope.review_comment }}</p>
                <p class="mt-1">{{ $t('Please complete your entries and submit them again.') }}</p>
            </div>

            <div v-if="components.length === 0" class="rounded-2xl border border-dashed border-border bg-white p-8 text-center text-sm text-text-subtle">
                {{ $t('No externally available content') }}
            </div>

            <!-- Jede Überschrift beginnt einen Abschnitt (eigene Karte) — gliedert lange Formulare -->
            <div v-else class="space-y-6">
                <section
                    v-for="section in sections"
                    :key="section.key"
                    class="rounded-2xl border border-border-subtle bg-white p-5 sm:p-6"
                >
                    <ExternalComponentRenderer
                        v-if="section.title"
                        :component="section.title"
                        :project-id="project.id"
                        :tab-id="tab.id"
                        :scope="effectiveScope"
                        :class="section.items.length ? 'mb-6' : ''"
                    />
                    <div class="space-y-6">
                        <ExternalComponentRenderer
                            v-for="comp in section.items"
                            :key="comp.component_in_tab_id"
                            :component="comp"
                            :project-id="project.id"
                            :tab-id="tab.id"
                            :scope="effectiveScope"
                        />
                    </div>
                </section>
            </div>

            <!-- Absenden: Eingaben sind bereits gespeichert, erst hier wird das Haus benachrichtigt -->
            <div v-if="scope.access_type === 'write' && !scope.locked && components.length" class="mt-10 rounded-2xl border border-border-subtle bg-white p-5">
                <p v-if="flashStatus" class="mb-4 rounded-xl border border-success-border bg-success-surface px-4 py-3 text-sm text-success">
                    {{ flashStatus }}
                </p>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-text-muted">
                        <p>{{ $t('Your entries are saved automatically. Once everything is complete, submit your data so the inviting person is notified. After submitting, the tab is locked until it is confirmed or returned to you.') }}</p>
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
                        {{ scope.last_submitted_at ? $t('Submit entered data again') : $t('Submit entered data') }}
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
// Abschnitte: jede Überschrift (Title) beginnt einen neuen Abschnitt, Komponenten davor bilden
// einen Abschnitt ohne Überschrift.
const sections = computed(() => {
    const result = []
    let current = { key: 'start', title: null, items: [] }
    for (const comp of props.components) {
        if (comp.type === 'Title') {
            if (current.title || current.items.length) result.push(current)
            current = { key: `section-${comp.component_in_tab_id}`, title: comp, items: [] }
        } else {
            current.items.push(comp)
        }
    }
    if (current.title || current.items.length) result.push(current)
    return result
})

// Nach dem Absenden (bis zur Rückgabe) sind alle Komponenten nur lesbar — Backend sperrt mit 423.
const effectiveScope = computed(() => (props.scope.locked ? { ...props.scope, access_type: 'read' } : props.scope))
const submitting = ref(false)

function submitData() {
    if (!window.confirm($t('Submit your data now? The inviting person will be notified and the tab will be locked until it has been reviewed.'))) return
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
