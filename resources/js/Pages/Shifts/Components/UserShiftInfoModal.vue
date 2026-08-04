<template>
    <ArtworkBaseModal
        :title="$t('Staff info data') + (userName ? ' – ' + userName : '')"
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
                    {{ $t(tab.label) }}
                </button>
            </nav>
        </div>

        <div class="min-h-[18rem]">
            <!-- Ladezustand -->
            <div v-if="loading[activeTab]" class="flex items-center justify-center py-16 text-sm text-text-subtle">
                <PropertyIcon name="IconLoader2" class="size-5 mr-2 animate-spin" />
                {{ $t('Loading...') }}
            </div>

            <!-- 1) Spielzeitbezogene Daten -->
            <div v-else-if="activeTab === 'season' && data.season" class="space-y-4">
                <p class="text-xs text-text-subtle">
                    {{ $t('Season') }}: {{ formatDate(data.season.season.start) }} – {{ formatDate(data.season.season.end) }}
                    <span v-if="data.season.snapshot_recalculated_at">
                        · {{ $t('Documented status') }}: {{ formatDateTime(data.season.snapshot_recalculated_at) }}
                    </span>
                </p>

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
                        <tr v-for="row in seasonRows" :key="row.label">
                            <td class="py-2 pr-4 text-text">{{ $t(row.label) }}</td>
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
                <p class="text-[11px] text-text-subtle">
                    {{ $t('Format "actual / X" – X is the contract target. "–" means not applicable / not activated. The season is configured in the tool settings under "Communication & Legal".') }}
                </p>
            </div>

            <!-- 2) Ersatzfreie Tage -->
            <div v-else-if="activeTab === 'compensation' && data.compensation" class="space-y-5">
                <div>
                    <h4 class="text-sm font-semibold text-text mb-1">{{ $t('Open substitute days off') }}</h4>
                    <SimpleDayTable :rows="data.compensation.openCompensations" empty="No open substitute days off." />
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-text mb-1">{{ $t('Granted substitute days off') }}</h4>
                    <SimpleDayTable :rows="data.compensation.grantedCompensations" :granted="true" empty="No granted substitute days off." />
                </div>
            </div>

            <!-- 3) Urlaub -->
            <div v-else-if="activeTab === 'vacation' && data.vacation" class="space-y-4">
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Entitlement') }} {{ data.vacation.year }}</p>
                        <p class="mt-1 text-2xl font-semibold">{{ data.vacation.entitlement }}</p>
                    </div>
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Granted') }}</p>
                        <p class="mt-1 text-2xl font-semibold">{{ data.vacation.granted }}</p>
                    </div>
                    <div class="rounded-lg border border-border-subtle p-3">
                        <p class="text-xs uppercase tracking-wide text-text-subtle">{{ $t('Remaining') }}</p>
                        <p class="mt-1 text-2xl font-semibold" :class="data.vacation.remaining < 0 ? 'text-danger' : ''">
                            {{ data.vacation.remaining }}
                        </p>
                    </div>
                </div>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-text-subtle">
                            <th class="py-2 pr-4 font-medium">{{ $t('Date') }}</th>
                            <th class="py-2 px-2 font-medium">{{ $t('Scope') }}</th>
                            <th class="py-2 pl-2 font-medium">{{ $t('Comment') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        <tr v-for="v in data.vacation.vacations" :key="v.id">
                            <td class="py-2 pr-4">{{ formatDate(v.date) }}</td>
                            <td class="py-2 px-2">{{ v.full_day ? $t('Full day') : $t('Half day') }}</td>
                            <td class="py-2 pl-2 text-text-subtle">{{ v.comment }}</td>
                        </tr>
                        <tr v-if="!data.vacation.vacations.length">
                            <td colspan="3" class="py-4 text-center text-text-subtle">{{ $t('No vacation granted yet.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4) Ist-Stunden -->
            <div v-else-if="activeTab === 'worktimes' && data.worktimes" class="space-y-5">
                <p class="text-xs text-text-subtle">
                    {{ $t('Period') }}: {{ formatDate(data.worktimes.dateRange.start) }} – {{ formatDate(data.worktimes.dateRange.end) }}
                </p>
                <div v-for="(days, weekKey) in data.worktimes.workTimes" :key="weekKey"
                     class="rounded-lg border border-border-subtle">
                    <div class="flex items-center justify-between px-3 py-2 bg-surface-sunken rounded-t-lg">
                        <span class="text-sm font-semibold">{{ weekKey }}</span>
                        <span class="text-xs" :class="weekDiff(days).minutes >= 0 ? 'text-success' : 'text-danger'">
                            {{ $t('Actual') }} {{ weekDiff(days).worked }} / {{ $t('Target') }} {{ weekDiff(days).wanted }}
                            ({{ weekDiff(days).diff }})
                        </span>
                    </div>
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-border-subtle">
                            <tr v-for="day in Object.values(days)" :key="day.date">
                                <td class="py-1.5 px-3 text-text-muted w-1/2">{{ day.formatted_date }}</td>
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
                <p v-if="!Object.keys(data.worktimes.workTimes).length" class="py-6 text-center text-text-subtle text-sm">
                    {{ $t('No working hours in this period.') }}
                </p>
            </div>

            <!-- 5) Überstunden -->
            <div v-else-if="activeTab === 'overtime' && data.overtime">
                <UserOvertimePanel :user-id="userId" :data="data.overtime" />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import SimpleDayTable from '@/Pages/Shifts/Components/UserShiftInfoSimpleDayTable.vue'
import UserOvertimePanel from '@/Pages/Shifts/Components/UserOvertimePanel.vue'

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

const load = async (tab) => {
    if (data.value[tab] || loading.value[tab]) return
    loading.value[tab] = true
    try {
        const res = await axios.get(route(routes[tab], { user: props.userId }))
        data.value[tab] = res.data
    } catch (e) {
        data.value[tab] = { error: true }
    } finally {
        loading.value[tab] = false
    }
}

const activate = (tab) => {
    activeTab.value = tab
    load(tab)
}

// initial laden
load('season')

const fmtIstX = (ist, target) => {
    if (target && target.active) return `${ist} / ${target.value}`
    return `${ist}`
}

const seasonRows = computed(() => {
    const d = data.value.season
    if (!d) return []
    const k = d.kpis
    const t = k.targets || {}
    const tSatMon = t.free_sundays_sat_mon_per_half
    const tCombos = t.one_and_half_day_combinations
    return [
        {
            label: 'Free Sundays connected with Saturday/Monday',
            h1: fmtIstX(k.free_sundays_sat_mon_half1, tSatMon),
            h2: fmtIstX(k.free_sundays_sat_mon_half2, tSatMon),
            total: null,
        },
        {
            label: '1.5-day combinations',
            h1: fmtIstX(k.one_and_half_combos_half1, tCombos),
            h2: fmtIstX(k.one_and_half_combos_half2, tCombos),
            total: null,
        },
        {
            label: 'Granted half free days',
            h1: k.granted_half_free_days_half1,
            h2: k.granted_half_free_days_half2,
            total: null,
        },
        {
            label: 'Free Sundays + Saturdays per season',
            h1: null,
            h2: null,
            total: fmtIstX(k.free_sundays_and_saturdays_season, t.free_sundays_and_saturdays_per_season),
        },
        {
            label: 'Free Sundays per season',
            h1: null,
            h2: null,
            total: fmtIstX(k.free_sundays_per_season, t.free_sundays_per_season),
        },
        {
            label: 'Free Sundays per calendar year',
            h1: null,
            h2: null,
            total: fmtIstX(k.free_sundays_calendar_year, t.free_sundays_per_calendar_year),
        },
        {
            label: 'Days off in the first 26 weeks',
            h1: null,
            h2: null,
            total: fmtIstX(k.days_off_first_26_weeks_count, t.days_off_first_26_weeks),
        },
        {
            label: 'Granted vacation days (calendar year)',
            h1: null,
            h2: null,
            total: `${k.granted_vacation_days_year}${t.annual_vacation_days ? ' / ' + t.annual_vacation_days.value : ''}`,
        },
    ]
})

const weekDiff = (days) => {
    let worked = 0
    let wanted = 0
    Object.values(days).forEach((d) => {
        worked += d.worked_hours || 0
        wanted += d.wantedHours || 0
    })
    const diffMin = worked - wanted
    const sign = diffMin >= 0 ? '+' : '-'
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
    return `${h}h ${m}m`
}

const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${day}.${month}.${date.getFullYear()}`
}

const formatDateTime = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    return formatDate(value) + ' ' + String(date.getHours()).padStart(2, '0') + ':' + String(date.getMinutes()).padStart(2, '0')
}
</script>
