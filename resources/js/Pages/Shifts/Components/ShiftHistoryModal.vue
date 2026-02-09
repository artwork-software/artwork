<!-- resources/js/Components/Shift/ShiftActivityLogModal.vue -->
<template>
    <ArtworkBaseModal
        :title="t('Shift history')"
        :description="t('Select a craft and date range to load shift history. Use filters to narrow down results.')"
        @close="handleClose"
    >
        <div class="space-y-6">
            <!-- Stats (luftiger, ohne gequetschte Divider) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <div class="text-[10px] text-gray-500">{{ t('Shifts') }}</div>
                    <div class="mt-1 text-lg text-gray-900">
                        <CountUp :value="shifts.length" />
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <div class="text-[10px] text-gray-500">{{ t('Entries') }}</div>
                    <div class="mt-1 text-lg text-gray-900">
                        <CountUp :value="meta.total" />
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-4">
                    <div class="text-[10px] text-gray-500">{{ t('Visible') }}</div>
                    <div class="mt-1 text-lg text-gray-900">
                        <CountUp :value="filteredLogs.length" />
                    </div>
                </div>
            </div>

            <!-- Controls Card -->
            <div class="rounded-xl border border-gray-100 bg-white p-5 space-y-6">
                <!-- Selection row (mehr gap, Buttons stacked) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="col-span-full">
                        <ArtworkBaseListbox
                            v-model="selectedCraft"
                            :items="crafts"
                            :disabled="loading"
                            :useTranslations="false"
                            :placeholder="t('Select craft')"
                            :emptyText="t('No options available')"
                            label="Craft"
                            optionLabel="name"
                            optionKey="id"
                        />
                    </div>

                    <div class="md:col-span-6">
                        <BaseInput
                            v-model="startDate"
                            id="shift-history-start"
                            type="date"
                            label="From"
                            :disabled="loading"
                        />
                    </div>

                    <div class="md:col-span-6">
                        <BaseInput
                            v-model="endDate"
                            id="shift-history-end"
                            type="date"
                            label="To"
                            :disabled="loading"
                        />
                    </div>

                    <!-- Actions: vertikal, dadurch nicht gequetscht -->
                    <div class="col-span-full flex gap-4">
                        <BaseUIButton
                            :disabled="loading || !craftId"
                            :icon="loadBtnIcon"
                            @click="fetchHistory(true)"
                            class="w-full justify-center"
                        >
                            {{ loading ? t('Loading...') : t('Load') }}
                        </BaseUIButton>

                        <BaseUIButton
                            :disabled="loading || !craftId"
                            icon="IconX"
                            @click="resetFilters"
                            class="w-full justify-center"
                            :title="t('Reset filters')"
                        >
                            {{ t('Reset') }}
                        </BaseUIButton>
                    </div>
                </div>

                <DividerChip :label="t('Filters')" variant="brand" />

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <!-- Search: mehr Platz -->
                    <div class="col-span-full">
                        <BaseInput
                            v-model="search"
                            id="shift-history-search"
                            type="text"
                            label="Search"
                            :placeholder="t('Search in history...')"
                            :disabled="loading || !craftId"
                        />
                    </div>

                    <div class="md:col-span-4">
                        <ArtworkBaseListbox
                            v-model="selectedContext"
                            :items="contextItems"
                            :disabled="loading || !craftId"
                            :useTranslations="true"
                            label="Context"
                            placeholder="All contexts"
                            emptyText="No options available"
                            optionLabel="name"
                            optionKey="id"
                        />
                    </div>

                    <div class="md:col-span-4">
                        <ArtworkBaseListbox
                            v-model="selectedLevel"
                            :items="levelItems"
                            :disabled="loading || !craftId"
                            :useTranslations="true"
                            label="Type"
                            placeholder="All types"
                            emptyText="No options available"
                            optionLabel="name"
                            optionKey="id"
                        />
                    </div>

                    <div class="md:col-span-4">
                        <ArtworkBaseListbox
                            v-model="selectedShift"
                            :items="shiftOptions"
                            :disabled="loading || !craftId"
                            :useTranslations="false"
                            :placeholder="t('All shifts')"
                            :emptyText="t('No shifts in selected range')"
                            label="Shift"
                            :optionLabel="(s) => shiftLabel(s)"
                            optionKey="id"
                        />
                    </div>
                </div>

                <div v-if="error" class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-artwork-messages-error">
                    {{ error }}
                </div>
            </div>

            <!-- Empty states -->
            <div v-if="!craftId" class="rounded-xl border border-gray-100 bg-white p-5 text-sm text-gray-600">
                {{ t('Select a craft to load shift history.') }}
            </div>

            <div v-else-if="!filteredLogs.length && !loading" class="rounded-xl border border-gray-100 bg-white p-5 text-sm text-gray-600">
                <div class="font-medium text-gray-900">{{ t('No history entries available for this selection yet.') }}</div>
                <div class="mt-1 text-gray-600">{{ t('Try adjusting filters or expanding the date range.') }}</div>
            </div>

            <!-- Results -->
            <div v-else class="rounded-xl border border-gray-100 bg-white">
                <!-- Scroll area, damit nichts zusammengedrückt wirkt -->
                <div class="px-5 py-5 max-h-[60vh] overflow-y-auto pr-4 space-y-6">
                    <div v-for="group in groupedLogs" :key="group.dayKey" class="space-y-3">
                        <DividerChip :label="formatDate(group.dayLabel)" variant="brand" />

                        <ol class="space-y-3">
                            <li
                                v-for="entry in group.items"
                                :key="entry.id"
                                class="flex gap-4 rounded-xl border border-gray-100 bg-white p-4 hover:bg-gray-50/40 transition"
                            >
                                <!-- Content -->
                                <div class="flex-1 space-y-2">
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

                                        <span v-if="entry.shiftId" class="inline-flex items-center">
                                            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-[11px] text-gray-700">
                                                {{ shiftLabelById(entry.shiftId) }}
                                            </span>
                                        </span>

                                        <span v-if="entry.causerName" class="inline-flex items-center gap-2">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-[10px] text-gray-700">
                                                {{ entry.causerInitials }}
                                            </span>
                                            <span class="text-gray-700">{{ entry.causerName }}</span>
                                        </span>
                                    </div>

                                    <!-- Changes -->
                                    <div v-if="entry.changes.length" class="mt-2 rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                                        <table class="w-full border-collapse text-[11px]">
                                            <thead>
                                            <tr class="text-gray-500 text-[10px]">
                                                <th class="text-left font-medium pb-2 pr-3">{{ t('Field') }}</th>
                                                <th class="text-left font-medium pb-2 pr-3">{{ t('Before') }}</th>
                                                <th class="text-left font-medium pb-2">{{ t('After') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                            <tr
                                                v-for="change in entry.changes"
                                                :key="change.fieldName + '-' + change.index"
                                                class="align-top"
                                            >
                                                <td class="py-2 pr-3 text-gray-700">
                                                    {{ fieldLabel(change.fieldName) }}
                                                </td>
                                                <td class="py-2 pr-3 text-gray-500">
                                                    {{ formatFieldValue(change.fieldName, change.oldValue) }}
                                                </td>
                                                <td class="py-2 text-gray-900">
                                                    {{ formatFieldValue(change.fieldName, change.newValue) }}
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- Load more -->
                    <div v-if="craftId && meta.current_page < meta.last_page" class="pt-2">
                        <BaseUIButton
                            :disabled="loading"
                            :icon="loading ? 'IconLoader2' : 'IconChevronDown'"
                            :isSmall="true"
                            @click="fetchHistory(false)"
                            class="w-full justify-center"
                        >
                            {{ loading ? t('Loading...') : t('Load more') }}
                        </BaseUIButton>
                    </div>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'

import { useShiftPlanRequest } from '../../ShiftPlanRequests/components/useShiftPlanRequest.js'

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import CountUp from "@/Artwork/Visual/CountUp.vue";
import DividerChip from "@/Artwork/Divider/DividerChip.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

type ShiftActivityProperties = {
    translation_key?: string | null
    translation_key_placeholder_values?: any[] | null
    context?: 'normal' | 'in_workflow' | 'post_commit' | string | null
    shift_id?: number | null
    [key: string]: any
}

type RawShiftActivity = {
    id: number
    log_name: string
    description: string
    event: string
    subject_id: number
    created_at: string
    properties: ShiftActivityProperties
    causer: {
        id: number
        first_name: string | null
        last_name: string | null
        full_name: string | null
    } | null
}

type CraftLite = { id: number; name: string; abbreviation?: string | null }

type ShiftLite = {
    id: number
    start_date: string | null
    end_date: string | null
    start: string | null
    end: string | null
    room?: { id: number; name: string | null } | null
    project?: { id: number; name: string | null } | null
}

const props = defineProps<{
    crafts: CraftLite[]
    initialCraftId?: number | null
    initialStartDate?: string | null
    initialEndDate?: string | null
}>()

const emit = defineEmits<{ (e: 'close'): void }>()
const handleClose = () => emit('close')

const {
    t,
    fieldLabel,
    formatFieldValue,
    formatDateTime,
    activityContext,
    extractActivityChanges,
    activityTranslation,
    formatDate
} = useShiftPlanRequest()

// Defaults (lokal, ohne UTC shift)
const toYmd = (d: Date) => new Intl.DateTimeFormat('sv-SE').format(d)
const defaultStartOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth(), 1))
}
const defaultEndOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth() + 1, 0))
}

// Selection
const selectedCraft = ref<CraftLite | null>(
    props.initialCraftId ? (props.crafts.find(c => c.id === props.initialCraftId) ?? null) : null
)
const craftId = computed(() => selectedCraft.value?.id ?? null)

const startDate = ref<string>(props.initialStartDate ?? defaultStartOfMonth())
const endDate   = ref<string>(props.initialEndDate ?? defaultEndOfMonth())

// Data
const loading = ref(false)
const error = ref<string | null>(null)

const shifts = ref<ShiftLite[]>([])
const rawLogs = ref<RawShiftActivity[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 50, total: 0 })

// Button icons (safe: IconLoader2 & IconRefresh exist bei dir)
const loadBtnIcon = computed(() => (loading.value ? 'IconLoader2' : 'IconRefresh'))

// Filters
const search = ref('')
const selectedContext = ref<{ id: string; name: string } | null>({ id: 'all', name: 'All contexts' })
const selectedLevel   = ref<{ id: string; name: string } | null>({ id: 'all', name: 'All types' })
const selectedShift   = ref<ShiftLite | null>(null)

const contextItems = [
    { id: 'all',         name: 'All contexts' },
    { id: 'normal',      name: 'Normal' },
    { id: 'in_workflow', name: 'Workflow' },
    { id: 'post_commit', name: 'Post-commit' },
]

const levelItems = [
    { id: 'all',     name: 'All types' },
    { id: 'success', name: 'Added/Assigned' },
    { id: 'warning', name: 'Updated/Reverted' },
    { id: 'danger',  name: 'Removed' },
    { id: 'default', name: 'Other' },
]

const shiftOptions = computed(() => shifts.value ?? [])

const shiftLabel = (s: ShiftLite) => {
    const d1 = s.start_date ?? ''
    const d2 = s.end_date ?? ''
    const time = [s.start, s.end].filter(Boolean).join('–')
    const room = s.room?.name ? ` · ${s.room.name}` : ''
    const proj = s.project?.name ? ` · ${s.project.name}` : ''
    const datePart = d1 === d2 ? d1 : `${d1}–${d2}`
    return `${datePart}${time ? ' · ' + time : ''}${room}${proj}`
}

const shiftsById = computed<Record<string, ShiftLite>>(() => {
    const map: Record<string, ShiftLite> = {}
    for (const s of shifts.value) map[String(s.id)] = s
    return map
})

const shiftLabelById = (id: number) => {
    const s = shiftsById.value[String(id)]
    return s ? `#${id} · ${shiftLabel(s)}` : `#${id}`
}

const resetFilters = () => {
    search.value = ''
    selectedContext.value = { id: 'all', name: 'All contexts' }
    selectedLevel.value   = { id: 'all', name: 'All types' }
    selectedShift.value   = null
}

// Fetch
const fetchHistory = async (reset: boolean) => {
    if (!craftId.value) return

    loading.value = true
    error.value = null

    try {
        const nextPage = reset ? 1 : meta.value.current_page + 1

        const res = await axios.get(route('shift.history.index'), {
            params: {
                craftId: craftId.value,
                start_date: startDate.value,
                end_date: endDate.value,
                per_page: meta.value.per_page,
                page: nextPage,
            },
        })

        const payload = res.data
        shifts.value = payload.shifts ?? []

        const newLogs: RawShiftActivity[] = payload.logs?.data ?? []
        meta.value = payload.logs?.meta ?? { current_page: 1, last_page: 1, per_page: 50, total: 0 }

        rawLogs.value = reset ? newLogs : [...rawLogs.value, ...newLogs]
    } catch (e: any) {
        error.value = e?.response?.data?.message || e?.message || t('Failed to load history.')
    } finally {
        loading.value = false
    }
}

// Auto-refresh (debounced)
let timer: number | null = null
watch([craftId, startDate, endDate], () => {
    if (!craftId.value) return
    if (timer) window.clearTimeout(timer)
    timer = window.setTimeout(() => fetchHistory(true), 250)
})

onMounted(() => {
    if (craftId.value) fetchHistory(true)
})

// Normalized logs
type NormalizedChange = { index: number; fieldName: string; oldValue: any; newValue: any }
type NormalizedLogEntry = {
    id: number
    message: string
    createdAt: string
    createdAtFormatted: string
    context: string | null
    contextLabel: string | null
    causerName: string | null
    causerInitials: string | null
    iconLetter: string
    level: 'default' | 'success' | 'warning' | 'danger'
    changes: NormalizedChange[]
    shiftId: number | null
}

const getCauserName = (log: RawShiftActivity) => {
    const causer = log.causer
    if (!causer) return { name: t('System'), initials: 'S' }

    const name =
        causer.full_name ||
        [causer.first_name, causer.last_name].filter(Boolean).join(' ') ||
        null

    if (!name) return { name: t('Unknown user'), initials: '?' }

    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(p => p.charAt(0))
        .join('')
        .toUpperCase()

    return { name, initials: initials || name.charAt(0).toUpperCase() }
}

const detectLevel = (log: RawShiftActivity): NormalizedLogEntry['level'] => {
    const desc = log.description || ''
    const ev = log.event || ''
    const key = log.properties?.translation_key || ''

    if (desc.includes('assigned') || ev === 'assigned' || key.includes('assigned_to_shift')) return 'success'
    if (desc.includes('removed')  || ev === 'removed'  || key.includes('removed_from_shift')) return 'danger'
    if (desc.includes('updated')  || ev.includes('updated') || key.includes('updated') || key === 'shift_updated') return 'warning'
    if (key === 'committed_shift_change_reverted' || desc.includes('reverted')) return 'warning'
    return 'default'
}

const iconForLevel = (level: NormalizedLogEntry['level']) => {
    switch (level) {
        case 'success': return 'A'
        case 'danger':  return 'R'
        case 'warning': return 'U'
        default:        return 'H'
    }
}

const messageForLog = (log: RawShiftActivity) => {
    const msgFromKey = activityTranslation(log)
    if (msgFromKey) return msgFromKey
    if (log.description) return t(log.description)
    if (log.event) return t(log.event)
    return t('Change in shift')
}

const normalizeChanges = (log: RawShiftActivity): NormalizedChange[] => {
    const changes = extractActivityChanges(log) || []
    return changes.map((fc: any, index: number) => ({
        index,
        fieldName: fc.fieldName,
        oldValue: fc.old_label ?? fc.old ?? null,
        newValue: fc.new_label ?? fc.new ?? null,
    }))
}

const normalizedLogs = computed<NormalizedLogEntry[]>(() => {
    return [...(rawLogs.value || [])]
        .sort((a, b) => (a.created_at > b.created_at ? -1 : 1))
        .map((log) => {
            const level = detectLevel(log)
            const { name: causerName, initials: causerInitials } = getCauserName(log)

            const context = log.properties?.context || null
            const contextLabel = activityContext(log) || null

            const createdAt = log.created_at
            const createdAtFormatted = formatDateTime(createdAt)

            const shiftId =
                (log.properties?.shift_id as number | null | undefined) ??
                (log.subject_id as number | null | undefined) ??
                null

            return {
                id: log.id,
                message: messageForLog(log),
                createdAt,
                createdAtFormatted,
                context,
                contextLabel,
                causerName,
                causerInitials,
                iconLetter: iconForLevel(level),
                level,
                changes: normalizeChanges(log),
                shiftId,
            }
        })
})

const filteredLogs = computed(() => {
    const q = search.value.trim().toLowerCase()
    const ctx = selectedContext.value?.id ?? 'all'
    const lvl = selectedLevel.value?.id ?? 'all'
    const shiftId = selectedShift.value?.id ?? null

    return normalizedLogs.value.filter((e) => {
        if (ctx !== 'all' && e.context !== ctx) return false
        if (lvl !== 'all' && e.level !== lvl) return false
        if (shiftId && e.shiftId !== shiftId) return false

        if (q) {
            const hay = [
                e.message,
                e.causerName ?? '',
                e.contextLabel ?? '',
                e.shiftId ? shiftLabelById(e.shiftId) : '',
            ].join(' ').toLowerCase()

            if (!hay.includes(q)) return false
        }
        return true
    })
})

const dayLabel = (dayKey: string) => dayKey
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
        dayLabel: dayLabel(k),
        items: groups[k],
    }))
})

const bubbleClass = (entry: NormalizedLogEntry) => {
    switch (entry.level) {
        case 'success': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
        case 'warning': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-100'
        case 'danger':  return 'bg-rose-50 text-rose-700 ring-1 ring-rose-100'
        default:        return 'bg-gray-50 text-gray-700 ring-1 ring-gray-100'
    }
}

const contextBadgeClass = (entry: NormalizedLogEntry) => {
    switch (entry.context) {
        case 'in_workflow':
        case 'workflow':
            return 'border-amber-200 bg-amber-50 text-amber-700'
        case 'post_commit':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700'
        case 'normal':
            return 'border-gray-200 bg-gray-50 text-gray-700'
        default:
            return 'border-gray-200 bg-gray-50 text-gray-700'
    }
}
</script>
