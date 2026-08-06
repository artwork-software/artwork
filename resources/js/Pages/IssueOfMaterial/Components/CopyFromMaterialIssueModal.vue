<template>
    <ArtworkBaseModal
        title="Copy material from another material issue"
        :description="$t('Select a material issue to copy its articles and quantities into this issue.')"
        @close="$emit('close')"
        modal-size="max-w-3xl"
    >
        <!-- Suche -->
        <div class="relative">
            <input
                v-model="query"
                :placeholder="$t('Search material issue by name')"
                class="w-full rounded-md border border-border bg-white px-3 py-2 text-sm outline-none ring-0 focus:border-accent-600"
                type="text"
                ref="searchInput"
            />
            <button
                v-if="query"
                class="absolute inset-y-0 right-0 mr-2 rounded p-1 text-text-subtle hover:text-text-muted"
                @click="query = ''"
                :aria-label="$t('Clear search')"
            >
                ×
            </button>
        </div>

        <!-- Ergebnisliste -->
        <div class="mt-3 rounded-md border border-border-subtle">
            <div class="sticky top-0 z-[1] flex items-center justify-between border-b bg-white px-3 py-2 text-xs text-text-subtle">
                <span>{{ $t('Results') }}: {{ issues.length }}</span>
                <span v-if="query" class="truncate">{{ $t('Search') }}: “{{ query }}”</span>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <div v-if="loading" class="flex justify-center py-6">
                    <span class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-accent-600 border-t-transparent"></span>
                </div>

                <ul v-else class="divide-y divide-border-subtle">
                    <li
                        v-for="issue in issues"
                        :key="issue.id"
                        class="group flex cursor-pointer items-start justify-between gap-x-4 px-3 py-2 hover:bg-surface-sunken"
                        @click="select(issue)"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-text">
                                    {{ issue.name }}
                                </p>
                                <span class="shrink-0 rounded-full border border-border-subtle px-2 py-0.5 text-[10px] text-text-muted">
                                    {{ issue.articles?.length ?? 0 }} {{ $t('articles') }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-text-subtle">
                                {{ formatDate(issue.start_date ?? issue.issue_date) }} – {{ formatDate(issue.end_date ?? issue.return_date) }}
                                <span v-if="issue.project?.name"> • {{ issue.project.name }}</span>
                                <span v-if="issue.room?.name"> • {{ issue.room.name }}</span>
                                <span v-if="issue.external_name"> • {{ issue.external_name }}</span>
                            </p>

                            <!-- Mini-Preview der Artikel -->
                            <div v-if="(issue.articles?.length ?? 0) > 0" class="mt-1 flex flex-wrap gap-1">
                                <span
                                    v-for="(article, i) in issue.articles.slice(0, 4)"
                                    :key="`${issue.id}-${i}`"
                                    class="rounded bg-surface-sunken px-1.5 py-0.5 text-[10px] text-text-muted"
                                >
                                    {{ article.pivot?.quantity ?? 1 }}× {{ article.name }}
                                </span>
                                <span
                                    v-if="(issue.articles?.length ?? 0) > 4"
                                    class="rounded bg-surface-sunken px-1.5 py-0.5 text-[10px] text-text-muted"
                                >
                                    +{{ issue.articles.length - 4 }}
                                </span>
                            </div>
                        </div>

                        <button
                            class="mt-1 shrink-0 rounded-md border border-border px-2 py-1 text-xs text-text-muted hover:bg-surface-sunken"
                            @click.stop="select(issue)"
                        >
                            {{ $t('Copy') }}
                        </button>
                    </li>

                    <li v-if="issues.length === 0" class="px-3 py-6 text-center text-sm text-text-subtle">
                        {{ $t('No material issues found') }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4 flex items-center justify-end">
            <BaseUIButton is-cancel-button :label="$t('Close')" @click="$emit('close')"/>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue'
import axios from 'axios'
import debounce from 'lodash.debounce'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

const props = defineProps({
    // Beim Bearbeiten: eigene Ausgabe aus den Ergebnissen ausblenden
    excludeIssueId: {
        type: Number,
        required: false,
        default: null,
    },
    // 'intern' durchsucht interne, 'extern' externe Materialausgaben
    issueType: {
        type: String,
        required: false,
        default: 'intern',
        validator: (value) => ['intern', 'extern'].includes(value),
    },
})

const emit = defineEmits(['close', 'copy-issue'])

const query = ref('')
const issues = ref([])
const loading = ref(false)
const searchInput = ref(null)

const fetchIssues = async () => {
    loading.value = true
    try {
        const searchRoute = props.issueType === 'extern'
            ? 'extern-issue-of-material.search-for-copy'
            : 'issue-of-material.search-for-copy';
        const { data } = await axios.get(route(searchRoute), {
            params: {
                q: query.value.trim() || undefined,
                exclude_id: props.excludeIssueId || undefined,
            },
        })
        issues.value = Array.isArray(data) ? data : []
    } catch (e) {
        console.error('Fehler bei der Suche nach Materialausgaben:', e)
        issues.value = []
    } finally {
        loading.value = false
    }
}

const debouncedFetch = debounce(fetchIssues, 300)

watch(query, () => debouncedFetch())

const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const year = date.getFullYear()
    return `${day}.${month}.${year}`
}

function select(issue) {
    emit('copy-issue', issue)
    emit('close')
}

onMounted(() => {
    fetchIssues()
    nextTick(() => searchInput.value?.focus())
})
</script>
