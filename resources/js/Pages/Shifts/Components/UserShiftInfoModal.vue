<template>
    <ArtworkBaseModal
        :title="$t('Key figures') + (userName ? ': ' + userName : '')"
        :description="$t('Season-related key figures. Data is loaded per tab on demand.')"
        modal-size="sm:max-w-4xl"
        is-in-shift-plan
        @close="$emit('closed')"
    >
        <!-- Tab-Leiste -->
        <div class="border-b border-border-subtle mb-4">
            <nav class="-mb-px flex gap-4 overflow-x-auto" aria-label="Tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    @click="activate(tab.key)"
                    :class="[
                        activeTab === tab.key
                            ? 'border-accent-600 text-accent-600'
                            : 'border-transparent text-text-subtle hover:text-text-muted hover:border-border',
                        'whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium'
                    ]"
                >
                    {{ $t(tab.label) }}<span v-if="tab.key === 'overtime' && overtimeRuleInactive"> ({{ $t('inactive') }})</span>
                </button>
            </nav>
        </div>

        <div class="min-h-[18rem]">
            <!-- Ladezustand -->
            <div v-if="loading[activeTab]" class="flex items-center justify-center py-16 text-sm text-text-subtle">
                <PropertyIcon name="IconLoader2" class="size-5 mr-2 animate-spin" />
                {{ $t('Loading...') }}
            </div>

            <!-- Fehlerzustand (alle Tabs) -->
            <div v-else-if="hasError(activeTab)" class="rounded-lg border border-danger/30 bg-surface-sunken p-4 space-y-3">
                <div class="flex items-start gap-2 text-sm text-danger">
                    <PropertyIcon name="IconAlertTriangle" class="size-5 shrink-0" />
                    <div>
                        <p class="font-medium">{{ $t('The data could not be loaded.') }}</p>
                        <p v-if="data[activeTab]?.message" class="mt-1 text-text-muted">{{ data[activeTab].message }}</p>
                    </div>
                </div>
                <BaseUIButton :label="$t('Retry')" size="sm" @click="reload(activeTab)" />
            </div>

            <!-- 1) Spielzeitbezogene Daten -->
            <div v-else-if="activeTab === 'season' && data.season?.kpis" class="space-y-4">
                <p class="text-xs text-text-subtle">
                    {{ $t('Season') }}: {{ formatDate(data.season.season?.start) }} – {{ formatDate(data.season.season?.end) }}
                    <span v-if="data.season.counted_until">
                        · {{ $t('Counted until') }} {{ formatDate(data.season.counted_until) }}
                    </span>
                </p>
                <p class="text-xs text-text-subtle">
                    <template v-if="data.season.snapshot_recalculated_at">
                        {{ $t('Last nightly calculation') }}: {{ formatDateTime(data.season.snapshot_recalculated_at) }} ·
                    </template>
                    {{ $t('Display = current status') }}
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-text-subtle">
                                <th class="py-2 pr-4 font-medium">{{ $t('Parameter') }}</th>
                                <th class="py-2 px-2 font-medium text-center">{{ $t('1st half') }}</th>
                                <th class="py-2 px-2 font-medium text-center">{{ $t('2nd half') }}</th>
                                <th class="py-2 pl-2 font-medium text-center">{{ $t('Season / Year') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            <tr v-for="row in visibleSeasonRows" :key="row.label" :class="row.targetActive ? '' : 'text-text-subtle'">
                                <td class="py-2 pr-4 text-text">
                                    <div class="flex items-center gap-1.5">
                                        <span>{{ $t(row.label) }}</span>
                                        <ToolTipComponent
                                            icon="IconInfoCircle"
                                            icon-size="w-3.5 h-3.5"
                                            :tooltip-text="row.ruleParams ? $t(row.rule, row.ruleParams) : $t(row.rule)"
                                            direction="top"
                                            classes="text-text-subtle"
                                        />
                                    </div>
                                </td>
                                <td class="py-2 px-2 text-center font-medium" :class="row.h1 === null ? 'text-text-subtle' : ''">
                                    {{ row.h1 === null ? '–' : row.h1 }}
                                </td>
                                <td class="py-2 px-2 text-center font-medium" :class="row.h2 === null ? 'text-text-subtle' : ''">
                                    {{ row.h2 === null ? '–' : row.h2 }}
                                </td>
                                <td class="py-2 pl-2 text-center font-medium" :class="row.total === null ? 'text-text-subtle' : ''">
                                    {{ row.total === null ? '–' : row.total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Kennzahlen ohne aktiven Zielwert im Vertrag sind standardmäßig ausgeblendet (Zustand pro Browser) -->
                <p v-if="allSeasonTargetsInactive" class="text-xs text-text-subtle">
                    {{ $t('No target values are stored for this contract.') }}
                </p>
                <button
                    v-else-if="hiddenSeasonRowCount > 0 || showInactiveKpis"
                    type="button"
                    class="text-xs text-accent-600 hover:text-accent-700 underline underline-offset-2"
                    @click="toggleInactiveKpis"
                >
                    {{ showInactiveKpis
                        ? $t('Hide key figures without target value')
                        : $t('Show {n} more key figures without target value', { n: hiddenSeasonRowCount }) }}
                </button>
                <p class="text-[11px] text-text-subtle">
                    {{ $t('Format "actual / X" – X is the contract target. "–" means not applicable / not activated. The season is configured in the tool settings under "Communication & Legal".') }}
                </p>
            </div>

            <!-- 2) Ersatzfreie Tage -->
            <div v-else-if="activeTab === 'compensation' && data.compensation" class="space-y-5">
                <div>
                    <h4 class="text-sm font-semibold text-text mb-1">{{ $t('Open substitute days off') }}</h4>
                    <div class="overflow-x-auto">
                        <SimpleDayTable :rows="data.compensation.openCompensations ?? []" empty="No open substitute days off." />
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-text mb-1">{{ $t('Granted substitute days off') }}</h4>
                    <div class="overflow-x-auto">
                        <SimpleDayTable :rows="data.compensation.grantedCompensations ?? []" :granted="true" empty="No granted substitute days off." />
                    </div>
                </div>
            </div>

            <!-- 3) Urlaub -->
            <div v-else-if="activeTab === 'vacation' && data.vacation" class="space-y-4">
                <p class="text-xs text-text-subtle">
                    {{ $t('Calendar year') }} {{ data.vacation.year }} · {{ $t('including planned vacation') }} ·
                    {{ $t('Full day = 1, half day = 0.5') }}
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Entitlement') }} {{ data.vacation.year }}</p>
                        <p class="mt-1 text-2xl font-semibold">{{ data.vacation.entitlement ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Granted') }}</p>
                        <p class="mt-1 text-2xl font-semibold">{{ data.vacation.granted ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Remaining') }}</p>
                        <p class="mt-1 text-2xl font-semibold" :class="(data.vacation.remaining ?? 0) < 0 ? 'text-danger' : ''">
                            {{ data.vacation.remaining ?? 0 }}
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-text-subtle">
                                <th class="py-2 pr-4 font-medium">{{ $t('Date') }}</th>
                                <th class="py-2 px-2 font-medium">{{ $t('Scope') }}</th>
                                <th class="py-2 pl-2 font-medium">{{ $t('Comment') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            <tr v-for="v in (data.vacation.vacations ?? [])" :key="v.id">
                                <td class="py-2 pr-4">{{ formatDate(v.date) }}</td>
                                <td class="py-2 px-2">{{ v.full_day ? $t('Full day') : $t('Half day') }}</td>
                                <td class="py-2 pl-2 text-text-subtle">{{ v.comment }}</td>
                            </tr>
                            <tr v-if="!(data.vacation.vacations ?? []).length">
                                <td colspan="3" class="py-4 text-center text-text-subtle">{{ $t('No vacation granted yet.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4) Ist-Stunden -->
            <div v-else-if="activeTab === 'worktimes' && data.worktimes" class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1">
                        <button type="button" class="rounded p-1 hover:bg-surface-sunken text-text-muted" :title="$t('Previous month')" @click="shiftMonth(-1)">
                            <PropertyIcon name="IconChevronLeft" class="size-4" />
                        </button>
                        <span class="text-sm font-semibold text-text min-w-[9rem] text-center">{{ worktimesMonthLabel }}</span>
                        <button type="button" class="rounded p-1 hover:bg-surface-sunken text-text-muted" :title="$t('Next month')" @click="shiftMonth(1)">
                            <PropertyIcon name="IconChevronRight" class="size-4" />
                        </button>
                    </div>
                    <p class="text-xs text-text-subtle">
                        {{ $t('Period') }}: {{ formatDate(data.worktimes.dateRange?.start) }} – {{ formatDate(data.worktimes.dateRange?.end) }}
                    </p>
                </div>

                <div v-if="data.worktimes.totals" class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Actual') }}</p>
                        <p class="mt-1 text-lg font-semibold">{{ data.worktimes.totals.worked }} h</p>
                    </div>
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Target') }}</p>
                        <p class="mt-1 text-lg font-semibold">{{ data.worktimes.totals.wanted }} h</p>
                    </div>
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Balance') }}</p>
                        <p class="mt-1 text-lg font-semibold" :class="(data.worktimes.totals.difference_minutes ?? 0) >= 0 ? 'text-success' : 'text-danger'">
                            {{ data.worktimes.totals.difference_signed ?? data.worktimes.totals.difference }}
                        </p>
                    </div>
                </div>

                <div v-for="(days, weekKey) in (data.worktimes.workTimes ?? {})" :key="weekKey"
                     class="rounded-lg border border-border-subtle">
                    <div class="flex items-center justify-between px-3 py-2 bg-surface-sunken rounded-t-lg">
                        <span class="text-sm font-semibold">{{ weekKey }}</span>
                        <span class="text-xs" :class="weekDiff(days).minutes >= 0 ? 'text-success' : 'text-danger'">
                            {{ $t('Actual') }} {{ weekDiff(days).worked }} / {{ $t('Target') }} {{ weekDiff(days).wanted }}
                            ({{ weekDiff(days).diff }})
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <tbody class="divide-y divide-border-subtle">
                                <tr v-for="day in Object.values(days)" :key="day.date">
                                    <td class="py-1.5 px-3 text-text-muted w-1/2">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ day.formatted_date }}</span>
                                            <ToolTipComponent
                                                v-if="dayTooltip(day)"
                                                icon="IconInfoCircle"
                                                icon-size="w-3.5 h-3.5"
                                                :tooltip-text="dayTooltip(day)"
                                                direction="top"
                                                :classes="day.reduction_reason === 'special_day' ? 'text-warning' : 'text-text-subtle'"
                                            />
                                            <span v-if="day.is_special_day" class="rounded bg-warning-surface text-warning border border-warning-border px-1 text-[9px] font-semibold uppercase">
                                                {{ $t('Special Day') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-1.5 px-3 text-right">{{ day.worked_hours_formatted }}</td>
                                    <td class="py-1.5 px-3 text-right text-text-subtle">/ {{ day.wantedHoursFormatted }}</td>
                                    <td class="py-1.5 px-3 text-right"
                                        :class="day.work_time_balance_change >= 0 ? 'text-success' : 'text-danger'">
                                        {{ day.work_time_balance_change_formatted }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p v-if="!Object.keys(data.worktimes.workTimes ?? {}).length" class="py-6 text-center text-text-subtle text-sm">
                    {{ $t('No working hours in this period.') }}
                </p>
            </div>

            <!-- 5) Überstunden -->
            <div v-else-if="activeTab === 'overtime' && data.overtime">
                <div class="overflow-x-auto">
                    <UserOvertimePanel :user-id="userId" :data="data.overtime" />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import SimpleDayTable from '@/Pages/Shifts/Components/UserShiftInfoSimpleDayTable.vue'
import UserOvertimePanel from '@/Pages/Shifts/Components/UserOvertimePanel.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    userId: { type: Number, required: true },
    userName: { type: String, default: '' },
})

defineEmits(['closed'])

const tabs = [
    { key: 'season', label: 'Season-related data' },
    { key: 'compensation', label: 'Substitute days off' },
    { key: 'vacation', label: 'Vacation' },
    { key: 'worktimes', label: 'Actual hours' },
    { key: 'overtime', label: 'Overtime' },
]

const activeTab = ref('season')
const loading = ref({ season: false, compensation: false, vacation: false, worktimes: false, overtime: false })
const data = ref({ season: null, compensation: null, vacation: null, worktimes: null, overtime: null })

const routes = {
    season: 'shift.user-info.season',
    compensation: 'shift.user-info.compensation',
    vacation: 'shift.user-info.vacation',
    worktimes: 'shift.user-info.worktimes',
    overtime: 'shift.user-info.overtime',
}

// Ist-Stunden: Monatsnavigation (Default laufender Monat)
const worktimesMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1))
const worktimesMonthLabel = computed(() => worktimesMonth.value.toLocaleDateString('de-DE', { month: 'long', year: 'numeric' }))

const toIso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

const requestParams = (tab) => {
    if (tab !== 'worktimes') return {}
    const start = worktimesMonth.value
    const end = new Date(start.getFullYear(), start.getMonth() + 1, 0)
    return { start: toIso(start), end: toIso(end) }
}

const hasError = (tab) => !!data.value[tab]?.error

const load = async (tab, force = false) => {
    if ((data.value[tab] && !force) || loading.value[tab]) return
    loading.value[tab] = true
    try {
        const res = await axios.get(route(routes[tab], { user: props.userId }), { params: requestParams(tab) })
        data.value[tab] = res.data && typeof res.data === 'object' ? res.data : { error: true }
    } catch (e) {
        const payload = e?.response?.data
        data.value[tab] = {
            error: true,
            message: payload?.message ?? (e?.response?.status === 403 ? $t('You do not have permission to view this data.') : null),
        }
    } finally {
        loading.value[tab] = false
    }
}

const reload = (tab) => load(tab, true)

const activate = (tab) => {
    activeTab.value = tab
    load(tab)
}

const shiftMonth = (delta) => {
    const m = worktimesMonth.value
    worktimesMonth.value = new Date(m.getFullYear(), m.getMonth() + delta, 1)
    load('worktimes', true)
}

// initial laden
load('season')

const fmtIstX = (ist, target) => {
    if (target && target.active) return `${ist} / ${target.value}`
    return `${ist}`
}

// Zielwert im Vertrag aktiv? (fehlender Zielwert-Eintrag = kein Vertrag -> als aktiv behandeln, damit nichts verschwindet)
const targetActive = (target) => !(target && target.active === false)

// Kennzahlen ohne Zielwert ausblenden: Zustand pro Browser (localStorage, try/catch wegen Private Mode/Quota)
const INACTIVE_KPIS_STORAGE_KEY = 'artwork.user-shift-info.show-inactive-kpis'
const readShowInactiveKpis = () => {
    try {
        return window.localStorage?.getItem(INACTIVE_KPIS_STORAGE_KEY) === '1'
    } catch (e) {
        return false
    }
}
const showInactiveKpis = ref(readShowInactiveKpis())
const toggleInactiveKpis = () => {
    showInactiveKpis.value = !showInactiveKpis.value
    try {
        window.localStorage?.setItem(INACTIVE_KPIS_STORAGE_KEY, showInactiveKpis.value ? '1' : '0')
    } catch (e) {
        // Speicherung ist nur Komfort – ohne localStorage gilt der Zustand für dieses Fenster
    }
}

// Überstunden-Tab: Regel inaktiv -> Label "(inaktiv)"; Season-Payload liefert das Flag vorab, der Overtime-Payload bestätigt es
const overtimeRuleInactive = computed(() => {
    if (data.value.overtime && typeof data.value.overtime.rule_active === 'boolean') return !data.value.overtime.rule_active
    if (data.value.season && typeof data.value.season.overtime_rule_active === 'boolean') return !data.value.season.overtime_rule_active
    return false
})

const seasonRows = computed(() => {
    const d = data.value.season
    if (!d || !d.kpis) return []
    const k = d.kpis
    const t = k.targets || {}
    const tSatMon = t.free_sundays_sat_mon_per_half
    const tCombos = t.one_and_half_day_combinations
    return [
        {
            label: 'Free Sundays connected with Saturday/Monday',
            rule: 'Counts a Sunday that is a full free day and whose Saturday or Monday is also a full free day. Full free day = free-day entry, granted substitute day off, both half days free, or a working day (per pattern) without shift or entry.',
            h1: fmtIstX(k.free_sundays_sat_mon_half1, tSatMon),
            h2: fmtIstX(k.free_sundays_sat_mon_half2, tSatMon),
            total: null,
            targetActive: targetActive(tSatMon),
        },
        {
            label: '1.5-day combinations',
            rule: 'A run of n consecutive full free days counts n−1 combinations; a single full free day with a free afternoon before or a free morning after counts 1. Assigned to the half by the first day.',
            h1: fmtIstX(k.one_and_half_combos_half1, tCombos),
            h2: fmtIstX(k.one_and_half_combos_half2, tCombos),
            total: null,
            targetActive: targetActive(tCombos),
        },
        {
            label: 'Granted half free days',
            rule: 'Exactly one free half day (morning or afternoon) or a half substitute day off on a day that is not a full free day.',
            h1: k.granted_half_free_days_half1,
            h2: k.granted_half_free_days_half2,
            total: null,
            // Reine Zählgröße ohne Vertragsziel: immer anzeigen
            targetActive: true,
        },
        {
            label: 'Free Sundays + Saturdays per season',
            rule: 'Sunday and the preceding Saturday are both full free days.',
            h1: null,
            h2: null,
            total: fmtIstX(k.free_sundays_and_saturdays_season, t.free_sundays_and_saturdays_per_season),
            targetActive: targetActive(t.free_sundays_and_saturdays_per_season),
        },
        {
            label: 'Free Sundays per season',
            rule: 'Sundays within the season that are full free days.',
            h1: null,
            h2: null,
            total: fmtIstX(k.free_sundays_per_season, t.free_sundays_per_season),
            targetActive: targetActive(t.free_sundays_per_season),
        },
        {
            label: 'Free Sundays per calendar year',
            rule: 'Sundays within the calendar year that are full free days.',
            h1: null,
            h2: null,
            total: fmtIstX(k.free_sundays_calendar_year, t.free_sundays_per_calendar_year),
            targetActive: targetActive(t.free_sundays_per_calendar_year),
        },
        {
            label: 'Days off in the first 26 weeks',
            rule: 'Within the first 26 weeks of the season ({0}): full free day = 1, half free day = 0.5.',
            ruleParams: [formatDateRange(k.days_off_first_26_weeks_window)],
            h1: null,
            h2: null,
            total: k.days_off_first_26_weeks_window ? fmtIstX(k.days_off_first_26_weeks_count, t.days_off_first_26_weeks) : null,
            targetActive: targetActive(t.days_off_first_26_weeks),
        },
        {
            label: 'Granted vacation days (calendar year)',
            rule: 'Vacation entries in the calendar year up to yesterday: full day = 1, half day = 0.5.',
            h1: null,
            h2: null,
            total: `${k.granted_vacation_days_year}${t.annual_vacation_days ? ' / ' + t.annual_vacation_days.value : ''}`,
            targetActive: targetActive(t.annual_vacation_days),
        },
    ]
})

// Nur abschaltbare Vertragsziele zählen für "alle inaktiv" (Urlaubsanspruch ist immer aktiv, Zählgrößen haben kein Ziel)
const seasonRowsWithTarget = computed(() => {
    const t = data.value.season?.kpis?.targets || {}
    const keys = [
        'free_sundays_sat_mon_per_half', 'one_and_half_day_combinations', 'free_sundays_and_saturdays_per_season',
        'free_sundays_per_season', 'free_sundays_per_calendar_year', 'days_off_first_26_weeks',
    ]
    return keys.filter((key) => t[key] !== undefined)
})
const allSeasonTargetsInactive = computed(() => {
    const t = data.value.season?.kpis?.targets || {}
    const keys = seasonRowsWithTarget.value
    return keys.length > 0 && keys.every((key) => t[key]?.active === false)
})
const hiddenSeasonRowCount = computed(() => seasonRows.value.filter((row) => !row.targetActive).length)
const visibleSeasonRows = computed(() => {
    // Alle inaktiv: alles anzeigen (mit Hinweis) statt leerer Tabelle
    if (allSeasonTargetsInactive.value || showInactiveKpis.value) return seasonRows.value
    return seasonRows.value.filter((row) => row.targetActive)
})

const weekDiff = (days) => {
    let worked = 0
    let wanted = 0
    Object.values(days).forEach((d) => {
        worked += d.worked_hours || 0
        wanted += d.wantedHours || 0
    })
    const diffMin = worked - wanted
    const sign = diffMin >= 0 ? '+' : '−'
    return {
        minutes: diffMin,
        worked: toHM(worked),
        wanted: toHM(wanted),
        diff: sign + toHM(Math.abs(diffMin)),
    }
}

const toHM = (minutes) => {
    const h = Math.floor(Math.abs(minutes) / 60)
    const m = Math.abs(minutes) % 60
    return `${h}:${String(m).padStart(2, '0')} h`
}

const weekdayName = (value) => {
    const date = new Date(value)
    if (isNaN(date.getTime())) return ''
    return date.toLocaleDateString('de-DE', { weekday: 'long' })
}

const formatDayMonth = (value) => {
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    return `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.`
}

/**
 * Tooltip je Tag: Minderungsgrund (Sondertag / Ersatzfreier Tag, ggf. Dreimonatsdurchschnitt)
 * sowie Krank/Urlaub (soll-neutral).
 */
const dayTooltip = (day) => {
    const parts = []
    if (day.is_special_day) {
        parts.push(`${$t('Special Day')}: ${day.special_day_name ?? ''}`.trim())
        if (!day.special_day_counts) {
            parts.push($t('Special day rule inactive for this contract – normal daily target'))
        } else if (!day.reduction_reason) {
            parts.push($t('Work on special day – no target reduction'))
        }
    }
    if (day.reduction_reason === 'compensation_day') {
        parts.push($t('Substitute day off'))
    }
    if (day.reduction_reason && day.target_reduction > 0) {
        let text = `${$t('Target')} −${day.target_reduction_formatted} h`
        if (day.reference_period) {
            text += ` · Ø ${weekdayName(day.date)} ${formatDayMonth(day.reference_period.start)}–${formatDayMonth(day.reference_period.end)}`
        }
        parts.push(text)
    }
    if (day.is_sick) {
        parts.push(`${$t('Sick')}: ${$t('actual = target')}`)
    }
    if (day.is_vacation) {
        parts.push(`${$t('Vacation')}${day.vacation_factor < 1 ? ` (${$t('Half day')})` : ''}: ${$t('actual = target')}`)
    }
    return parts.join(' · ')
}

const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${day}.${month}.${date.getFullYear()}`
}

// Zählfenster "01.09.2026 – 01.03.2027" (Spielzeitbeginn + 26 Wochen); ohne Fenster "–"
const formatDateRange = (window) => {
    if (!window || !window.start || !window.end) return '–'
    return `${formatDate(window.start)} – ${formatDate(window.end)}`
}

const formatDateTime = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    return formatDate(value) + ' ' + String(date.getHours()).padStart(2, '0') + ':' + String(date.getMinutes()).padStart(2, '0')
}
</script>
