<template>
    <ArtworkBaseModal
        :title="$t('Material issue log')"
        :description="$t('View all changes to material issues. Use filters to narrow down results.')"
        @close="$emit('close')"
    >
        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <div class="text-[10px] text-gray-500">{{ $t('Entries') }}</div>
                    <div class="mt-1 text-lg text-gray-900">{{ meta.total }}</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <div class="text-[10px] text-gray-500">{{ $t('Visible') }}</div>
                    <div class="mt-1 text-lg text-gray-900">{{ filteredLogs.length }}</div>
                </div>
            </div>

            <!-- Controls -->
            <div class="rounded-xl border border-gray-100 bg-white p-5 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <!-- Project filter -->
                    <div class="col-span-full">
                        <ArtworkBaseListbox
                            :model-value="selectedProject"
                            @update:model-value="selectedProject = $event"
                            :items="projectsWithAll"
                            :disabled="loading"
                            :use-translations="false"
                            :placeholder="$t('Select project')"
                            :empty-text="$t('No options available')"
                            :label="$t('Project')"
                            option-label="name"
                            option-key="id"
                        />
                    </div>

                    <!-- Date range heading -->
                    <div class="col-span-full flex items-center gap-1">
                        <span class="text-xs font-medium text-gray-700">{{ $t('Issue period') }}</span>
                        <ToolTipComponent
                            direction="right"
                            :tooltip-text="$t('This period refers to the time range of the material issues. Only entries of material issues whose time range overlaps at least one day with the specified period are shown.')"
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4"
                        />
                    </div>

                    <!-- Date range -->
                    <div class="md:col-span-6">
                        <BaseInput
                            v-model="startDate"
                            id="log-start"
                            type="date"
                            :label="$t('From')"
                            :disabled="loading"
                        />
                    </div>
                    <div class="md:col-span-6">
                        <BaseInput
                            v-model="endDate"
                            id="log-end"
                            type="date"
                            :label="$t('To')"
                            :disabled="loading"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="col-span-full flex gap-4">
                        <BaseUIButton
                            :disabled="loading"
                            :icon="loading ? 'IconLoader2' : 'IconRefresh'"
                            @click="fetchLogs(true)"
                            class="w-full justify-center"
                        >
                            {{ loading ? $t('Loading...') : $t('Load') }}
                        </BaseUIButton>
                        <BaseUIButton
                            :disabled="loading"
                            icon="IconX"
                            @click="resetFilters"
                            class="w-full justify-center"
                        >
                            {{ $t('Reset') }}
                        </BaseUIButton>
                    </div>
                </div>

                <!-- Search + type filter -->
                <DividerChip :label="$t('Filters')" variant="brand" />

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-8">
                        <BaseInput
                            v-model="search"
                            id="log-search"
                            type="text"
                            :label="$t('Search')"
                            :placeholder="$t('Search in log...')"
                            :disabled="loading"
                        />
                    </div>
                    <div class="md:col-span-4">
                        <ArtworkBaseListbox
                            :model-value="selectedType"
                            @update:model-value="selectedType = $event"
                            :items="typeItems"
                            :disabled="loading"
                            :use-translations="true"
                            :label="$t('Issue type')"
                            :placeholder="$t('All')"
                            :empty-text="$t('No options available')"
                            option-label="name"
                            option-key="id"
                        />
                    </div>
                </div>

                <div v-if="error" class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-artwork-messages-error">
                    {{ error }}
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!filteredLogs.length && !loading" class="rounded-xl border border-gray-100 bg-white p-5 text-sm text-gray-600">
                <div class="font-medium text-gray-900">{{ $t('No history entries available for this selection yet.') }}</div>
                <div class="mt-1 text-gray-600">{{ $t('Try adjusting filters or expanding the date range.') }}</div>
            </div>

            <!-- Results -->
            <div v-else class="rounded-xl border border-gray-100 bg-white">
                <div class="px-5 py-5 max-h-[60vh] overflow-y-auto pr-4 space-y-6">
                    <div v-for="group in groupedLogs" :key="group.dayKey" class="space-y-3">
                        <DividerChip :label="formatDisplayDate(group.dayKey)" variant="brand" />

                        <ol class="space-y-3">
                            <li
                                v-for="entry in group.items"
                                :key="entry.id"
                                class="flex gap-4 rounded-xl border border-gray-100 bg-white p-4 hover:bg-gray-50/40 transition"
                            >
                                <div class="flex-1 space-y-2">
                                    <!-- Message -->
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                        <p class="text-sm leading-5 text-gray-900 whitespace-pre-wrap">
                                            {{ entry.message }}
                                        </p>
                                    </div>

                                    <!-- Meta -->
                                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 border border-gray-100">
                                            {{ entry.createdAtFormatted }}
                                        </span>

                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-1 text-[11px] border"
                                            :class="entry.issueType === 'internal'
                                                ? 'border-sky-200 bg-sky-50 text-sky-700'
                                                : 'border-amber-200 bg-amber-50 text-amber-700'"
                                        >
                                            {{ entry.issueType === 'internal' ? $t('Internal') : $t('External') }}
                                        </span>

                                        <span v-if="entry.issueName" class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[11px] text-gray-700">
                                            {{ entry.issueName }}
                                        </span>

                                        <span v-if="entry.causerName" class="inline-flex items-center gap-2">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-[10px] text-gray-700">
                                                {{ entry.causerInitials }}
                                            </span>
                                            <span class="text-gray-700">{{ entry.causerName }}</span>
                                        </span>
                                    </div>

                                    <!-- Field changes -->
                                    <div v-if="entry.changes.length" class="mt-2 rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                                        <table class="w-full border-collapse text-[11px]">
                                            <thead>
                                            <tr class="text-gray-500 text-[10px]">
                                                <th class="text-left font-medium pb-2 pr-3">{{ $t('Field') }}</th>
                                                <th class="text-left font-medium pb-2 pr-3">{{ $t('Before') }}</th>
                                                <th class="text-left font-medium pb-2">{{ $t('After') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(change, idx) in entry.changes" :key="idx" class="align-top">
                                                <td class="py-2 pr-3 text-gray-700">{{ fieldLabel(change.field) }}</td>
                                                <td class="py-2 pr-3 text-gray-500">{{ change.oldValue ?? '-' }}</td>
                                                <td class="py-2 text-gray-900">{{ change.newValue ?? '-' }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Article diff -->
                                    <div v-if="entry.articleDiff.length" class="mt-2 space-y-1">
                                        <div
                                            v-for="(diff, idx) in entry.articleDiff"
                                            :key="idx"
                                            class="flex items-center gap-2 text-[11px] rounded-md px-2 py-1"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700': diff.type === 'added',
                                                'bg-rose-50 text-rose-700': diff.type === 'removed',
                                                'bg-amber-50 text-amber-700': diff.type === 'changed',
                                            }"
                                        >
                                            <span v-if="diff.type === 'added'">+ {{ $t('Article added') }}: {{ diff.name }} ({{ diff.quantity }})</span>
                                            <span v-else-if="diff.type === 'removed'">- {{ $t('Article removed') }}: {{ diff.name }}</span>
                                            <span v-else>~ {{ $t('Quantity changed') }}: {{ diff.name }} ({{ diff.oldQuantity }} &rarr; {{ diff.newQuantity }})</span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- Load more -->
                    <div v-if="meta.current_page < meta.last_page" class="pt-2">
                        <BaseUIButton
                            :disabled="loading"
                            :icon="loading ? 'IconLoader2' : 'IconChevronDown'"
                            :isSmall="true"
                            @click="fetchLogs(false)"
                            class="w-full justify-center"
                        >
                            {{ loading ? $t('Loading...') : $t('Load more') }}
                        </BaseUIButton>
                    </div>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue"
import DividerChip from "@/Artwork/Divider/DividerChip.vue"
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue"
import BaseInput from "@/Artwork/Inputs/BaseInput.vue"
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue"
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue"

const { t } = useI18n()

type ProjectLite = { id: number; name: string }

const props = defineProps<{
    projects: ProjectLite[]
    initialProjectId?: number | null
}>()

const emit = defineEmits<{ (e: 'close'): void }>()

// Defaults
const toYmd = (d: Date) => new Intl.DateTimeFormat('sv-SE').format(d)
const defaultStartOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth(), 1))
}
const defaultEndOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth() + 1, 0))
}

// Project selection
const allProjectsOption: ProjectLite = { id: 0, name: t('All projects') }
const projectsWithAll = computed(() => [allProjectsOption, ...props.projects])

const selectedProject = ref<ProjectLite | null>(
    props.initialProjectId
        ? (props.projects.find(p => p.id === props.initialProjectId) ?? allProjectsOption)
        : allProjectsOption
)
const projectId = computed(() => selectedProject.value?.id ?? 0)

const startDate = ref<string>(defaultStartOfMonth())
const endDate = ref<string>(defaultEndOfMonth())

// Type filter
const typeItems = [
    { id: 'all', name: 'All' },
    { id: 'internal', name: 'Internal' },
    { id: 'external', name: 'External' },
]
const selectedType = ref<{ id: string; name: string } | null>({ id: 'all', name: 'All' })

// Data
const loading = ref(false)
const error = ref<string | null>(null)
const rawLogs = ref<any[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 50, total: 0 })
const search = ref('')

const resetFilters = () => {
    search.value = ''
    selectedType.value = { id: 'all', name: 'All' }
    selectedProject.value = allProjectsOption
    startDate.value = defaultStartOfMonth()
    endDate.value = defaultEndOfMonth()
}

// Fetch
const fetchLogs = async (reset: boolean) => {
    loading.value = true
    error.value = null

    try {
        const nextPage = reset ? 1 : meta.value.current_page + 1

        const res = await axios.get(route('material-issue-log.index'), {
            params: {
                project_id: projectId.value,
                start_date: startDate.value,
                end_date: endDate.value,
                per_page: meta.value.per_page,
                page: nextPage,
            },
        })

        const payload = res.data
        const newLogs: any[] = payload.logs?.data ?? []
        meta.value = payload.logs?.meta ?? { current_page: 1, last_page: 1, per_page: 50, total: 0 }

        rawLogs.value = reset ? newLogs : [...rawLogs.value, ...newLogs]
    } catch (e: any) {
        error.value = e?.response?.data?.message || e?.message || t('Failed to load history.')
    } finally {
        loading.value = false
    }
}

// Auto-refresh
let timer: number | null = null
watch([projectId, startDate, endDate], () => {
    if (timer) window.clearTimeout(timer)
    timer = window.setTimeout(() => fetchLogs(true), 250)
})

onMounted(() => fetchLogs(true))

// Normalize logs
const getCauserName = (log: any) => {
    const causer = log.causer
    if (!causer) return { name: t('System'), initials: 'S' }

    const name = causer.full_name
        || [causer.first_name, causer.last_name].filter(Boolean).join(' ')
        || null

    if (!name) return { name: t('Unknown user'), initials: '?' }

    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p: string) => p.charAt(0))
        .join('')
        .toUpperCase()

    return { name, initials: initials || name.charAt(0).toUpperCase() }
}

const fieldLabelMap: Record<string, string> = {
    name: 'Name',
    project_id: 'Project',
    start_date: 'Start date',
    start_time: 'Start time',
    end_date: 'End date',
    end_time: 'End time',
    room_id: 'Room',
    notes: 'Notes',
    external_name: 'External name',
    external_address: 'Address',
    external_email: 'Email',
    external_phone: 'Phone',
    issue_date: 'Issue date',
    return_date: 'Return date',
    material_value: 'Material value',
    return_remarks: 'Return remarks',
}

const fieldLabel = (field: string): string => {
    return t(fieldLabelMap[field] ?? field)
}

const computeFieldChanges = (log: any) => {
    const props = log.properties || {}
    const oldAttrs = props.old || {}
    const newAttrs = props.attributes || {}
    const changes: { field: string; oldValue: any; newValue: any }[] = []

    const allKeys = new Set([...Object.keys(oldAttrs), ...Object.keys(newAttrs)])
    for (const key of allKeys) {
        const oldVal = oldAttrs[key]
        const newVal = newAttrs[key]
        if (String(oldVal ?? '') !== String(newVal ?? '')) {
            changes.push({ field: key, oldValue: oldVal, newValue: newVal })
        }
    }
    return changes
}

const computeArticleDiff = (log: any) => {
    const props = log.properties || {}
    const oldArticles: any[] = props.old_articles || []
    const newArticles: any[] = props.new_articles || []
    const diffs: any[] = []

    const oldMap = new Map(oldArticles.map(a => [a.id, a]))
    const newMap = new Map(newArticles.map(a => [a.id, a]))

    for (const [id, article] of newMap) {
        if (!oldMap.has(id)) {
            diffs.push({ type: 'added', name: article.name, quantity: article.quantity })
        } else {
            const old = oldMap.get(id)
            if (old.quantity !== article.quantity) {
                diffs.push({ type: 'changed', name: article.name, oldQuantity: old.quantity, newQuantity: article.quantity })
            }
        }
    }

    for (const [id, article] of oldMap) {
        if (!newMap.has(id)) {
            diffs.push({ type: 'removed', name: article.name })
        }
    }

    return diffs
}

const formatDateTime = (dateStr: string) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    const hours = String(d.getHours()).padStart(2, '0')
    const minutes = String(d.getMinutes()).padStart(2, '0')
    return `${day}.${month}.${year} ${hours}:${minutes}`
}

const formatDisplayDate = (dayKey: string) => {
    if (!dayKey || dayKey === 'Unknown') return dayKey
    const parts = dayKey.split('-')
    if (parts.length !== 3) return dayKey
    return `${parts[2]}.${parts[1]}.${parts[0]}`
}

type NormalizedLogEntry = {
    id: number
    message: string
    issueName: string
    createdAt: string
    createdAtFormatted: string
    causerName: string | null
    causerInitials: string | null
    issueType: string
    changes: { field: string; oldValue: any; newValue: any }[]
    articleDiff: any[]
}

const normalizedLogs = computed<NormalizedLogEntry[]>(() => {
    return [...(rawLogs.value || [])]
        .sort((a, b) => (a.created_at > b.created_at ? -1 : 1))
        .map((log) => {
            const { name: causerName, initials: causerInitials } = getCauserName(log)
            const props = log.properties || {}
            const issueType = props.issue_type || 'internal'
            const issueName = props.issue_name || ''
            const translationKey = props.translation_key || log.description || ''

            let message = t(translationKey)
            if (issueName) {
                message = message.replace('{0}', issueName)
            }

            return {
                id: log.id,
                message,
                issueName,
                createdAt: log.created_at,
                createdAtFormatted: formatDateTime(log.created_at),
                causerName,
                causerInitials,
                issueType,
                changes: computeFieldChanges(log),
                articleDiff: computeArticleDiff(log),
            }
        })
})

const filteredLogs = computed(() => {
    const q = search.value.trim().toLowerCase()
    const typeFilter = selectedType.value?.id ?? 'all'

    return normalizedLogs.value.filter((e) => {
        if (typeFilter !== 'all' && e.issueType !== typeFilter) return false

        if (q) {
            const hay = [e.message, e.issueName, e.causerName ?? ''].join(' ').toLowerCase()
            if (!hay.includes(q)) return false
        }
        return true
    })
})

const groupedLogs = computed(() => {
    const groups: Record<string, NormalizedLogEntry[]> = {}

    for (const item of filteredLogs.value) {
        const key = String(item.createdAt ?? '').slice(0, 10)
        const dayKey = key && key.length === 10 ? key : 'Unknown'
        if (!groups[dayKey]) groups[dayKey] = []
        groups[dayKey].push(item)
    }

    const orderedKeys = Object.keys(groups).sort((a, b) => (a > b ? -1 : 1))
    return orderedKeys.map((k) => ({
        dayKey: k,
        items: groups[k],
    }))
})
</script>
