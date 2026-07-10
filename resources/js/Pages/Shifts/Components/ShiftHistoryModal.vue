<template>
    <ArtworkBaseModal
        modal-size="sm:max-w-7xl"
        :title="t('Shift history')"
        :description="t('Select a craft and date range to load shift history. Use filters to narrow down results.')"
        @close="handleClose"
    >
        <!-- Feste Höhe (nur Desktop): alles sichtbar, einzig die Ergebnisliste scrollt -->
        <div class="grid grid-cols-1 gap-4 lg:h-[74vh] lg:min-h-[30rem] lg:grid-cols-[280px_minmax(0,1fr)]">
            <!-- Linke Spalte: Datenauswahl (lädt vom Server) + Filter (nur clientseitig) -->
            <aside class="space-y-3 lg:min-h-0 lg:overflow-y-auto">
                <div class="rounded-xl border border-gray-100 bg-white p-3.5 space-y-3">
                    <ArtworkBaseListbox
                        v-model="selectedCraft"
                        :items="craftsWithAll"
                        :disabled="loading"
                        :useTranslations="false"
                        :placeholder="t('Select craft')"
                        :emptyText="t('No options available')"
                        is-small
                        label="Craft"
                        optionLabel="name"
                        optionKey="id"
                    />

                    <div class="space-y-2">
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium text-gray-700">{{ t('Shift start') }}</span>
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="t('This period refers to the start of the shifts to be displayed in the history, not when the change was made. Only shift entries of shifts starting in the specified period are shown in the history.')"
                                icon="IconInfoCircle"
                                icon-size="h-4 w-4"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <BaseInput
                                v-model="startDate"
                                id="shift-history-start"
                                type="date"
                                label="From"
                                is-small
                                :disabled="loading"
                            />

                            <BaseInput
                                v-model="endDate"
                                id="shift-history-end"
                                type="date"
                                label="To"
                                is-small
                                :disabled="loading"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <BaseUIButton
                            :disabled="loading"
                            is-add-button
                            :icon="loadBtnIcon"
                            @click="fetchHistory(true)"
                            class="w-full justify-center"
                            :class="paramsDirty ? 'ring-2 ring-amber-300' : ''"
                        >
                            {{ loading ? t('Loading...') : t('Load history') }}
                        </BaseUIButton>

                        <p v-if="paramsDirty" class="text-[11px] text-amber-600">
                            {{ t('Selection changed – reload to update the results.') }}
                        </p>
                    </div>

                    <div v-if="error" class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-artwork-messages-error">
                        {{ error }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-3.5 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">{{ t('Filters') }}</span>
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="t('Filters narrow the loaded entries. Press Enter in search to reload all matching history entries.')"
                                icon="IconInfoCircle"
                                icon-size="h-4 w-4"
                            />
                        </div>
                        <button
                            type="button"
                            class="text-[11px] text-gray-500 underline underline-offset-2 transition-colors hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="loading"
                            @click="resetFilters"
                        >
                            {{ t('Reset') }}
                        </button>
                    </div>

                    <BaseInput
                        v-model="search"
                        id="shift-history-search"
                        type="text"
                        label="Search"
                        is-small
                        :placeholder="t('Search in history...')"
                        :disabled="loading"
                        @keyup.enter="fetchHistory(true)"
                    />

                    <ArtworkBaseListbox
                        v-model="selectedAction"
                        :items="actionItems"
                        :disabled="loading"
                        :useTranslations="true"
                        :enable-search="false"
                        :sort-fn="() => 0"
                        is-small
                        label="Kind of change"
                        placeholder="All"
                        emptyText="No options available"
                        optionLabel="name"
                        optionKey="id"
                    >
                        <template #label>
                            <span class="flex items-center gap-1">
                                {{ t('Kind of change') }}
                                <ToolTipComponent
                                    direction="right"
                                    :tooltip-text="actionFilterTooltip"
                                    allow-html
                                    tooltip-css-class="aw-tooltip-wide"
                                    icon="IconInfoCircle"
                                    icon-size="h-3.5 w-3.5"
                                />
                            </span>
                        </template>
                    </ArtworkBaseListbox>

                    <ArtworkBaseListbox
                        v-model="selectedShift"
                        :items="shifts"
                        :disabled="loading"
                        :useTranslations="false"
                        :placeholder="t('All shifts')"
                        :emptyText="t('No shifts in selected range')"
                        is-small
                        label="Shift"
                        :optionLabel="(s) => shiftLabel(s)"
                        optionKey="id"
                    />

                    <!-- Nur Änderungen zeigen, die nach dem Festschreiben der Schicht passiert sind -->
                    <div class="flex items-center justify-between gap-2 pt-0.5">
                        <ArtworkBaseToggle
                            v-model="onlyPostCommit"
                            :label="t('Only subsequent changes')"
                            :disabled="loading"
                            is-small
                        />
                        <ToolTipComponent
                            direction="left"
                            :tooltip-text="t('Shows only changes made after the affected shift was committed.')"
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4"
                        />
                    </div>

                    <!-- Sortier-Umschalter: nach Schichttag (statt nach Änderungsdatum) sortieren -->
                    <div class="flex items-center justify-between gap-2">
                        <ArtworkBaseToggle
                            v-model="groupByShiftDay"
                            :label="t('Sort by shift day')"
                            :disabled="loading"
                            is-small
                        />
                        <ToolTipComponent
                            direction="left"
                            :tooltip-text="t('Sort entries by the day of the shift they belong to instead of by the date of the change.')"
                            icon="IconInfoCircle"
                            icon-size="h-4 w-4"
                        />
                    </div>
                </div>
            </aside>

            <!-- Rechte Spalte: Ergebnisse – füllt die Modalhöhe, nur die Liste scrollt -->
            <section class="min-w-0 flex flex-col gap-4 lg:min-h-0">
                <div class="grid grid-cols-3 gap-3 shrink-0">
                    <div class="rounded-xl border border-gray-100 bg-white p-3">
                        <div class="text-[10px] text-gray-500">{{ t('Shifts') }}</div>
                        <div class="mt-1 text-lg text-gray-900">
                            <CountUp :value="shifts.length" />
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-3">
                        <div class="text-[10px] text-gray-500">{{ t('Entries') }}</div>
                        <div class="mt-1 text-lg text-gray-900">
                            <CountUp :value="meta.total" />
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-white p-3">
                        <div class="text-[10px] text-gray-500">{{ t('Visible') }}</div>
                        <div class="mt-1 text-lg text-gray-900">
                            <CountUp :value="filteredLogs.length" />
                        </div>
                    </div>
                </div>

                <!-- Noch nichts geladen: erst der Klick auf "Verlauf laden" holt Daten -->
                <div v-if="!hasLoaded && !loading" class="flex-1 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50/60 px-6 py-10 text-center">
                    <IconHistory class="h-8 w-8 text-gray-300" stroke-width="1.5" />
                    <p class="mt-3 text-sm font-medium text-gray-900">{{ t('Nothing loaded yet') }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ t('Choose a craft and period on the left, then load the shift history.') }}</p>
                    <div class="mt-4 flex justify-center">
                        <BaseUIButton is-add-button icon="IconRefresh" @click="fetchHistory(true)">
                            {{ t('Load history') }}
                        </BaseUIButton>
                    </div>
                </div>

                <div v-else-if="loading && !rawLogs.length" class="flex-1 flex items-center justify-center rounded-xl border border-gray-100 bg-white px-6 py-10 text-sm text-gray-500">
                    {{ t('Loading...') }}
                </div>

                <div v-else-if="!filteredLogs.length" class="flex-1 rounded-xl border border-gray-100 bg-white p-5 text-sm text-gray-600">
                    <div class="font-medium text-gray-900">{{ t('No history entries available for this selection yet.') }}</div>
                    <div class="mt-1 text-gray-600">{{ t('Try adjusting filters or expanding the date range.') }}</div>
                </div>

                <div v-else class="flex-1 lg:min-h-0 rounded-xl border border-gray-100 bg-white">
                    <div class="h-full px-5 py-5 overflow-y-auto pr-4 space-y-5">
                        <div v-for="group in groupedLogs" :key="group.dayKey" class="space-y-3">
                            <DividerChip :label="group.unknown ? t('Unknown date') : formatDate(group.dayLabel)" variant="brand" />

                            <ol class="space-y-3">
                                <li
                                    v-for="entry in group.items"
                                    :key="entry.id"
                                    class="rounded-xl border-2 border-zinc-900 bg-white overflow-hidden hover:shadow-md transition"
                                >
                                    <!-- Sammel-Eintrag (Festschreibung KW/Zeitraum): eigene Karte statt Schicht-Card -->
                                    <div v-if="entry.commitSummary" class="bg-gray-50/70 px-4 py-2.5 border-b border-gray-100">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                {{ t('Commitment') }}
                                            </p>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100" :class="entry.commitSummary.week ? '' : 'col-span-2'">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Period') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900">{{ entry.shiftDetails.dateLabel }}</p>
                                            </div>
                                            <div v-if="entry.commitSummary.week" class="rounded-lg bg-white px-2 py-1.5 border border-gray-100">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Calendar week') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900">{{ entry.commitSummary.week }}{{ entry.commitSummary.year ? '/' + entry.commitSummary.year : '' }}</p>
                                            </div>
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Craft') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900 truncate">{{ entry.shiftDetails.craft }}</p>
                                            </div>
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Shifts') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900">{{ entry.commitSummary.count ?? '–' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Schicht-Card: die betroffenen Schichtdaten klar dargestellt -->
                                    <div v-else class="bg-gray-50/70 px-4 py-2.5 border-b border-gray-100">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                {{ t('Shift') }}
                                                <span v-if="entry.shiftDetails.id" class="text-gray-400">#{{ entry.shiftDetails.id }}</span>
                                            </p>
                                            <!-- Kategorie-Chips: gleiche Begriffe wie im Aktion-Filter, damit die
                                                 Zuordnung der Einträge zu den Filteroptionen sichtbar ist -->
                                            <div class="flex items-center gap-1.5">
                                                <span
                                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                                    :class="categoryChipClass(entry.category)"
                                                >
                                                    {{ categoryLabel(entry.category) }}
                                                </span>
                                                <span
                                                    v-if="entry.context === 'post_commit'"
                                                    class="inline-flex items-center rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[10px] font-medium text-orange-700"
                                                >
                                                    {{ t('Post-commit') }}
                                                </span>
                                                <span
                                                    v-if="entry.shiftDetails.deleted"
                                                    class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-medium text-rose-600"
                                                >
                                                    <IconTrash class="h-3 w-3" />
                                                    {{ t('Subsequently deleted') }}
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Alle Schicht-Metadaten in EINER Zeile (inkl. Projekt) — spart Höhe -->
                                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Date') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900 truncate">{{ entry.shiftDetails.dateLabel }}</p>
                                            </div>
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Time') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900 truncate">{{ entry.shiftDetails.timeLabel }}</p>
                                            </div>
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Craft') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900 truncate">{{ entry.shiftDetails.craft }}</p>
                                            </div>
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Room') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900 truncate">{{ entry.shiftDetails.room }}</p>
                                            </div>
                                            <div class="rounded-lg bg-white px-2 py-1.5 border border-gray-100" :title="entry.shiftDetails.project">
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ t('Project') }}</p>
                                                <p class="text-[11px] font-medium text-gray-900 truncate">{{ entry.shiftDetails.project }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Vorgang: was passiert ist + wer & wann — in EINER Zeile, spart Höhe -->
                                    <div class="px-4 py-2.5">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <p class="min-w-0 text-sm leading-5 text-gray-900 whitespace-pre-wrap">
                                                {{ entry.message }}
                                            </p>
                                            <span class="shrink-0 self-end sm:self-auto inline-flex items-center gap-2 rounded-full bg-zinc-900 px-3 py-1 text-[11px] font-medium text-white">
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-[9px]">
                                                    {{ entry.causerInitials }}
                                                </span>
                                                {{ t('Changed by {causer} on {datetime}', { causer: entry.causerName, datetime: entry.createdAtFormatted }) }}
                                            </span>
                                        </div>

                                        <!-- Änderung (Vorher/Nachher-Vergleich) -->
                                        <div v-if="entry.changes.length" class="mt-2.5">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 mb-1">
                                                {{ t('Modification') }}
                                            </p>
                                            <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-3">
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
                                    </div>
                                </li>
                            </ol>
                        </div>

                    </div>
                </div>

                <BaseUIButton
                    v-if="canLoadMore"
                    :disabled="loading"
                    :icon="loading ? 'IconLoader2' : 'IconChevronDown'"
                    is-small
                    class="w-full shrink-0 justify-center"
                    @click="fetchHistory(false)"
                >
                    {{ loading ? t('Loading...') : t('Load more') }}
                </BaseUIButton>
            </section>
        </div>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'

import { useShiftPlanRequest } from '../../ShiftPlanRequests/components/useShiftPlanRequest.js'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import CountUp from '@/Artwork/Visual/CountUp.vue'
import DividerChip from '@/Artwork/Divider/DividerChip.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import { IconHistory, IconTrash } from '@tabler/icons-vue'

type ShiftActivityProperties = {
    translation_key?: string | null
    translation_key_placeholder_values?: any[] | null
    context?: 'normal' | 'in_workflow' | 'post_commit' | string | null
    shift_id?: number | null
    shift_ids?: number[]
    [key: string]: any
}

type RawShiftActivity = {
    id: number
    log_name: string
    description: string
    event: string
    subject_id: number | null
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
    craft_id: number
    start_date: string | null
    end_date: string | null
    start: string | null
    end: string | null
    description: string | null
    is_committed: boolean
    in_workflow: boolean
    room?: { id: number; name: string | null } | null
    project?: { id: number; name: string | null } | null
    craft?: CraftLite | null
    deleted_at?: string | null
}

const props = defineProps<{
    crafts: CraftLite[]
    initialCraftId?: number | null
    initialStartDate?: string | null
    initialEndDate?: string | null
    prefillSearch?: string | null
    initialShiftId?: number | null
    autoLoad?: boolean
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

const toYmd = (d: Date) => new Intl.DateTimeFormat('sv-SE').format(d)
const defaultStartOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth(), 1))
}
const defaultEndOfMonth = () => {
    const n = new Date()
    return toYmd(new Date(n.getFullYear(), n.getMonth() + 1, 0))
}

const allCraftsOption: CraftLite = { id: 0, name: t('All crafts') }
const craftsWithAll = computed(() => [allCraftsOption, ...props.crafts])

const selectedCraft = ref<CraftLite | null>(
    props.initialCraftId
        ? (props.crafts.find(c => c.id === props.initialCraftId) ?? allCraftsOption)
        : allCraftsOption
)
const craftId = computed(() => selectedCraft.value?.id ?? 0)

const startDate = ref<string>(props.initialStartDate ?? defaultStartOfMonth())
const endDate = ref<string>(props.initialEndDate ?? defaultEndOfMonth())

const loading = ref(false)
const error = ref<string | null>(null)
const hasLoaded = ref(false)

const shifts = ref<ShiftLite[]>([])
const rawLogs = ref<RawShiftActivity[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 50, total: 0 })

const loadBtnIcon = computed(() => loading.value ? 'IconLoader2' : 'IconRefresh')
const initialSearch = (props.prefillSearch ?? '').trim()

const search = ref(initialSearch)
const selectedShift = ref<ShiftLite | null>(null)

const groupByShiftDay = ref(false)
const onlyPostCommit = ref(false)

type ActionCategory = 'staffing' | 'shift_data' | 'lifecycle' | 'commitment' | 'other'

const ACTION_META: Record<ActionCategory, { label: string; description: string; chip: string }> = {
    staffing: { label: 'Staffing', description: 'Person assigned to or removed from a shift.', chip: 'border-blue-200 bg-blue-50 text-blue-700' },
    shift_data: { label: 'Shift details', description: 'Times, room, qualifications or similar changed.', chip: 'border-amber-200 bg-amber-50 text-amber-700' },
    lifecycle: { label: 'Shift created/deleted', description: 'Shift created, deleted or restored.', chip: 'border-violet-200 bg-violet-50 text-violet-700' },
    commitment: { label: 'Commitment & request', description: 'Shifts committed, commitment revoked or requested for approval.', chip: 'border-emerald-200 bg-emerald-50 text-emerald-700' },
    other: { label: 'Other', description: 'Rare entries that do not fit any other category, e.g. older log entries.', chip: 'border-gray-200 bg-gray-50 text-gray-600' },
}

const categoryLabel = (category: ActionCategory) => t(ACTION_META[category].label)
const categoryChipClass = (category: ActionCategory) => ACTION_META[category].chip

const selectedAction = ref<{ id: string; name: string } | null>({ id: 'all', name: 'All' })

const actionItems = computed(() => {
    const items = [
        { id: 'all', name: 'All' },
        { id: 'staffing', name: ACTION_META.staffing.label },
        { id: 'shift_data', name: ACTION_META.shift_data.label },
        { id: 'lifecycle', name: ACTION_META.lifecycle.label },
        { id: 'commitment', name: ACTION_META.commitment.label },
    ]
    if (normalizedLogs.value.some((e) => e.category === 'other')) {
        items.push({ id: 'other', name: ACTION_META.other.label })
    }
    return items
})

const actionFilterTooltip = computed(() => {
    const items = (Object.keys(ACTION_META) as ActionCategory[])
        .map((c) => `<li><span class="font-semibold">${categoryLabel(c)}:</span> ${t(ACTION_META[c].description)}</li>`)
        .join('')
    return `${t('Which kind of change does an entry show?')}<ul>${items}</ul>`
})

const shiftLabel = (shift: ShiftLite) => {
    const d1 = shift.start_date ?? ''
    const d2 = shift.end_date ?? ''
    const time = [shift.start, shift.end].filter(Boolean).join('–')
    const room = shift.room?.name ? ` · ${shift.room.name}` : ''
    const project = shift.project?.name ? ` · ${shift.project.name}` : ''
    const datePart = d1 === d2 ? d1 : `${d1}–${d2}`
    return `${datePart}${time ? ' · ' + time : ''}${room}${project}`
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

// Monats-Abkürzungen (DE/EN) → für Alt-Einträge, deren properties.old das Datum als
// "21. Sep 2026" / "30. Jun 2026" speichert (Backend ->format('d. M Y')).
const MONTH_ABBR: Record<string, string> = {
    jan: '01', feb: '02', mar: '03', 'mär': '03', maerz: '03', apr: '04',
    may: '05', mai: '05', jun: '06', jul: '07', aug: '08', sep: '09',
    oct: '10', okt: '10', nov: '11', dec: '12', dez: '12',
}

// Wandelt verschiedene Datumsdarstellungen nach DD.MM.YYYY. ISO/DD.MM.YYYY laufen über
// formatDate; das gespeicherte "21. Sep 2026" wird per Monats-Map konvertiert.
const normalizeDate = (value?: string | null): string => {
    if (!value) return ''
    const m = String(value).match(/^(\d{1,2})\.\s*([^\s.]+)\.?\s*(\d{4})$/)
    if (m) {
        const mon = MONTH_ABBR[m[2].toLowerCase()]
        if (mon) return `${m[1].padStart(2, '0')}.${mon}.${m[3]}`
    }
    return formatDate(value)
}

// Liefert einen sortierbaren ISO-Tagesschlüssel (YYYY-MM-DD) aus ISO, DD.MM.YYYY oder
// "21. Sep 2026". Wird für die Gruppierung nach Schichttag benötigt.
const toIsoDay = (value?: string | null): string => {
    if (!value) return ''
    const s = String(value)
    let m = s.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (m) return `${m[1]}-${m[2]}-${m[3]}`
    m = s.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})/)
    if (m) return `${m[3]}-${m[2].padStart(2, '0')}-${m[1].padStart(2, '0')}`
    m = s.match(/^(\d{1,2})\.\s*([^\s.]+)\.?\s*(\d{4})$/)
    if (m) {
        const mon = MONTH_ABBR[m[2].toLowerCase()]
        if (mon) return `${m[3]}-${mon}-${m[1].padStart(2, '0')}`
    }
    return ''
}

// Strukturierte Schichtdaten für die "Schicht-Card" pro Verlaufseintrag.
// Priorität: Snapshot (Stand zum Zeitpunkt des Eintrags) → aktuelle Live-Schicht →
// properties.old des Log-Eintrags (enthält bei Alt-Einträgen die Schichtdaten zum
// Zeitpunkt, z.B. bei Lösch-/Update-Einträgen). So zeigen auch alte Einträge zu
// (force-)gelöschten Schichten echte Daten statt nur "–".
const buildShiftDetails = (log: RawShiftActivity): EntryShiftDetails => {
    // Sammel-Einträge (Festschreibung KW/Zeitraum) haben keine einzelne Schicht —
    // Zeitraum/Gewerke kommen aus properties.commit_summary, die Karte wird im
    // Template separat gerendert.
    const summary = (log.properties as any)?.commit_summary ?? null
    if (summary) {
        const sd = summary.start_date || ''
        const ed = summary.end_date || ''
        return {
            id: null,
            dayKey: toIsoDay(sd),
            dateLabel: sd ? (ed && ed !== sd ? `${normalizeDate(sd)} – ${normalizeDate(ed)}` : normalizeDate(sd)) : '–',
            timeLabel: '–',
            craft: (summary.crafts || []).join(', ') || '–',
            room: '–',
            project: '–',
            deleted: false,
        }
    }

    const snap = (log.properties?.shift_snapshot as ShiftSnapshot | undefined) ?? null
    const id = (
        (log.properties?.shift_id as number | null | undefined) ??
        (log.subject_id as number | null | undefined) ??
        snap?.id ??
        null
    )
    const live = id != null ? shiftsById.value[String(id)] as any : null
    const old = (log.properties as any)?.old ?? null

    const sd = snap?.start_date || live?.start_date || old?.start_date || ''
    const ed = snap?.end_date || live?.end_date || old?.end_date || ''
    const dateLabel = sd
        ? (ed && ed !== sd ? `${normalizeDate(sd)} – ${normalizeDate(ed)}` : normalizeDate(sd))
        : '–'

    const start = snap?.start || live?.start || old?.start || ''
    const end = snap?.end || live?.end || old?.end || ''
    const timeLabel = (start || end) ? [start, end].filter(Boolean).join(' – ') : '–'

    const craft = snap?.craft || live?.craft?.name || live?.craft?.abbreviation || old?.['craft.name'] || '–'
    const room = snap?.room || live?.room?.name || old?.['room.name'] || '–'
    const project = snap?.project || live?.project?.name || old?.['project.name'] || '–'

    // "Nicht mehr existent": Live-Schicht ist (soft-)gelöscht ODER es gibt gar keine
    // Live-Row mehr (force-deleted, nur über Snapshot/old rekonstruiert). Wiederhergestellte
    // Schichten haben eine Live-Row ohne deleted_at → werden NICHT markiert.
    const deleted = id != null && (!live || !!live?.deleted_at)

    const dayKey = toIsoDay(snap?.start_date || live?.start_date || old?.start_date)

    return {
        id: id ?? null,
        dayKey,
        dateLabel,
        timeLabel,
        craft: craft || '–',
        room: room || '–',
        project: project || '–',
        deleted,
    }
}

const resetFilters = () => {
    if (loading.value) return

    const searchChanged = search.value !== initialSearch
    const shiftChanged = selectedShift.value !== null

    search.value = initialSearch
    selectedAction.value = { id: 'all', name: 'All' }
    selectedShift.value = null
    onlyPostCommit.value = false

    if (hasLoaded.value && searchChanged && !shiftChanged) fetchHistory(true)
}

let pendingShiftId: number | null = props.initialShiftId ?? null

type HistoryQuery = {
    craftId: number
    shiftId?: number
    start_date: string
    end_date: string
    per_page: number
    search?: string
    sort?: 'shift_day'
}

const loadedQuery = ref<HistoryQuery | null>(null)
const currentQuery = computed<HistoryQuery>(() => {
    const loaded = loadedQuery.value
    const selectionScopeChanged = loaded && (
        loaded.craftId !== craftId.value
        || loaded.start_date !== startDate.value
        || loaded.end_date !== endDate.value
    )

    return {
        craftId: craftId.value,
        shiftId: selectionScopeChanged ? undefined : (selectedShift.value?.id ?? pendingShiftId ?? undefined),
        start_date: startDate.value,
        end_date: endDate.value,
        per_page: meta.value.per_page,
        search: search.value.trim() || undefined,
        sort: groupByShiftDay.value ? 'shift_day' : undefined,
    }
})
const querySignature = (query: HistoryQuery | null) => JSON.stringify(query)
const paramsDirty = computed(
    () => hasLoaded.value && querySignature(currentQuery.value) !== querySignature(loadedQuery.value)
)
const canLoadMore = computed(
    () => hasLoaded.value && !paramsDirty.value && meta.value.current_page < meta.value.last_page
)

const fetchHistory = async (reset: boolean) => {
    if (loading.value) return

    const query = reset ? currentQuery.value : loadedQuery.value
    if (!query) return

    loading.value = true
    error.value = null

    try {
        const nextPage = reset ? 1 : meta.value.current_page + 1

        const res = await axios.get(route('shift.history.index'), {
            params: {
                ...query,
                page: nextPage,
            },
        })

        const payload = res.data
        if (reset) {
            loadedQuery.value = { ...query }
            shifts.value = payload.shifts ?? []
            const selectedShiftId = query.shiftId
            selectedShift.value = shifts.value.find((shift) => shift.id === selectedShiftId) ?? null
            pendingShiftId = null
        }

        const newLogs: RawShiftActivity[] = payload.logs?.data ?? []
        meta.value = payload.logs?.meta ?? { current_page: 1, last_page: 1, per_page: 50, total: 0 }

        rawLogs.value = reset ? newLogs : [...rawLogs.value, ...newLogs]
        hasLoaded.value = true
    } catch (e: any) {
        error.value = e?.response?.data?.message || e?.message || t('Failed to load history.')
    } finally {
        loading.value = false
    }
}

watch(selectedShift, (shift) => {
    if (hasLoaded.value && shift?.id !== loadedQuery.value?.shiftId) fetchHistory(true)
})

watch(groupByShiftDay, () => {
    if (hasLoaded.value) fetchHistory(true)
})

onMounted(() => {
    if (props.autoLoad) fetchHistory(true)
})

type NormalizedChange = { index: number; fieldName: string; oldValue: any; newValue: any }
type EntryShiftDetails = {
    id: number | null
    dayKey: string
    dateLabel: string
    timeLabel: string
    craft: string
    room: string
    project: string
    deleted: boolean
}
type NormalizedLogEntry = {
    id: number
    message: string
    createdAt: string
    createdAtFormatted: string
    context: string | null
    contextLabel: string | null
    causerName: string | null
    causerInitials: string | null
    category: ActionCategory
    changes: NormalizedChange[]
    shiftId: number | null
    shiftIds: number[]
    snapshot: ShiftSnapshot | null
    shiftDetails: EntryShiftDetails
    commitSummary: CommitSummary | null
    haystack: string
}

// Sammel-Eintrag einer Festschreibungs-Aktion (aus properties.commit_summary).
type CommitSummary = {
    committed?: boolean
    start_date?: string | null
    end_date?: string | null
    week?: number | null
    year?: number | null
    crafts?: string[]
    count?: number | null
}

// Zustand der Schicht zum Zeitpunkt des Log-Eintrags (aus properties.shift_snapshot).
type ShiftSnapshot = {
    id?: number | null
    start_date?: string | null
    end_date?: string | null
    start?: string | null
    end?: string | null
    craft_id?: number | null
    craft?: string | null
    room?: string | null
    project?: string | null
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

// Ordnet einen Log-Eintrag einer Aktions-Kategorie zu. Signale in absteigender
// Spezifität: Festschreibungs-/Anfrage-Aktionen → Besetzung → Schicht angelegt/
// gelöscht → Schichtdaten. Alles Unerkannte landet in "Sonstiges".
const detectCategory = (log: RawShiftActivity): ActionCategory => {
    const desc = (log.description || '').toLowerCase()
    const ev = log.event || ''
    const key = String(log.properties?.translation_key || '').toLowerCase()
    const ctx = log.properties?.context || ''

    if ((log.properties as any)?.commit_summary) return 'commitment'
    if (ctx === 'commit') return 'commitment'
    if (['committed', 'uncommitted', 'committed_bulk', 'uncommitted_bulk', 'shift_committed'].includes(ev)) return 'commitment'
    if (ev === 'shift_added_to_request' || ev === 'workflow_withdrawn' || key.includes('request')) return 'commitment'

    if (ev === 'assigned' || ev === 'removed' || desc.includes('assigned') || desc.includes('removed')
        || key.includes('assigned to shift') || key.includes('removed from shift')) return 'staffing'

    if (['created', 'deleted', 'deleted_with_reason', 'restored'].includes(ev)
        || desc.includes('deleted') || desc.includes('restored') || key.includes('shift was deleted')) return 'lifecycle'

    if (ev.includes('updated') || desc.includes('updated') || desc.includes('reverted')
        || key.includes('updated') || key.includes('reverted') || key.includes('changed')) return 'shift_data'

    return 'other'
}

const messageForLog = (log: RawShiftActivity) => {
    const msgFromKey = activityTranslation(log)
    if (msgFromKey) return msgFromKey
    // Lösch-Einträge klar benennen ("Schicht gelöscht") statt nur "gelöscht" – gilt auch
    // für Alt-Einträge ohne translation_key. Die betroffene Schicht steht im Kontext-Chip.
    if (log.event === 'deleted') return t('Shift was deleted')
    // "restored" allein ist unklar – klar benennen ("Schicht wiederhergestellt").
    if (log.event === 'restored' || log.description === 'restored') return t('Shift was restored')
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
    return [...rawLogs.value]
        .sort((a, b) => b.created_at.localeCompare(a.created_at) || b.id - a.id)
        .map((log) => {
            const category = detectCategory(log)
            const { name: causerName, initials: causerInitials } = getCauserName(log)

            const context = log.properties?.context || null
            const contextLabel = activityContext(log) || null

            const createdAt = log.created_at
            const createdAtFormatted = formatDateTime(createdAt)

            const shiftId =
                (log.properties?.shift_id as number | null | undefined) ??
                (log.subject_id as number | null | undefined) ??
                null
            const shiftIds = (log.properties?.shift_ids ?? [])
                .map(Number)
                .filter(Number.isFinite)

            const message = messageForLog(log)

            // Such-Haystack enthält neben der gerenderten Nachricht auch die rohen
            // Platzhalterwerte (z.B. zugewiesene Mitarbeiternamen) und die Beschreibung,
            // damit die clientseitige Suche keinen serverseitigen Treffer ausblendet.
            const placeholderValues = Array.isArray(log.properties?.translation_key_placeholder_values)
                ? log.properties!.translation_key_placeholder_values!.map((v) => String(v ?? '')).join(' ')
                : ''
            const commitSummary = ((log.properties as any)?.commit_summary as CommitSummary | undefined) ?? null

            const haystack = [
                message,
                log.description ?? '',
                placeholderValues,
                causerName ?? '',
                contextLabel ?? '',
                shiftId ? shiftLabelById(shiftId) : '',
                shiftIds.map(shiftLabelById).join(' '),
                commitSummary?.crafts?.join(' ') ?? '',
            ].join(' ').toLowerCase()

            return {
                id: log.id,
                message,
                createdAt,
                createdAtFormatted,
                context,
                contextLabel,
                causerName,
                causerInitials,
                category,
                changes: normalizeChanges(log),
                shiftId,
                shiftIds,
                snapshot: (log.properties?.shift_snapshot as ShiftSnapshot | undefined) ?? null,
                shiftDetails: buildShiftDetails(log),
                commitSummary,
                haystack,
            }
        })
})

const filteredLogs = computed(() => {
    const q = search.value.trim().toLowerCase()
    const action = selectedAction.value?.id ?? 'all'
    const shiftId = selectedShift.value?.id ?? null

    return normalizedLogs.value.filter((e) => {
        if (action !== 'all' && e.category !== action) return false
        if (onlyPostCommit.value && e.context !== 'post_commit') return false
        if (shiftId && e.shiftId !== shiftId && !e.shiftIds.includes(shiftId)) return false

        if (q && !e.haystack.includes(q)) return false
        return true
    })
})

// Gruppierung: nach Änderungsdatum (created_at, Default) ODER nach Schichttag (Toggle).
// Innerhalb einer Gruppe bleibt die Reihenfolge "neueste Änderung zuerst" (filteredLogs
// erbt die created_at-DESC-Sortierung aus normalizedLogs). Gruppen absteigend nach Tag,
// "ohne Datum" ganz unten.
const groupedLogs = computed(() => {
    const byShiftDay = groupByShiftDay.value
    const groups: Record<string, NormalizedLogEntry[]> = {}

    for (const item of filteredLogs.value) {
        let key: string
        if (byShiftDay) {
            key = item.shiftDetails.dayKey || 'unknown'
        } else {
            const date = item.createdAt.slice(0, 10)
            key = date.length === 10 ? date : 'unknown'
        }
        groups[key] ??= []
        groups[key].push(item)
    }

    const orderedKeys = Object.keys(groups).sort((a, b) => {
        if (a === 'unknown') return 1
        if (b === 'unknown') return -1
        return a > b ? -1 : 1
    })
    return orderedKeys.map((k) => ({
        dayKey: k,
        dayLabel: k === 'unknown' ? '' : k,
        unknown: k === 'unknown',
        items: groups[k],
    }))
})
</script>
