<template>
    <div class="w-full mb-10">
        <!-- Funktionsleiste -->
        <div class="mb-6 -ml-4">
            <UserShiftPlanFunctionBar
                :type="type"
                :totalPlannedWorkingHours="totalPlannedWorkingHours"
                :weeklyWorkingHours="weeklyWorkingHours"
                :dateValue="dateValue"
                :crafts="crafts"
                @previousTimeRange="goToPrevAssignedDay"
                @nextTimeRange="goToNextAssignedDay"
                @openHistoryModal="showHistoryModal = true"
                :user_to_edit_id="userToEditId"
            />
        </div>

        <!-- Zeitraum-Ansicht -->
        <div class="space-y-4">
            <!-- Kopfzeile (Zeitraum + Summen) -->
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-zinc-900">
                    <span class="mr-2">{{ rangeLabel }}</span>
                </div>

                <div class="text-xs text-zinc-600">
                    <span>(</span>
                    <span>{{ totalWorkTimeInRange }}</span>
                    <span> | {{ totalBreakTimeInRange }}</span>
                    <span>)</span>
                </div>
            </div>

            <!-- Keine Schichten im Zeitraum -->
            <div v-if="workItemsInRange.length === 0" class="rounded-xl border border-zinc-200 bg-white p-4 text-center">
                <p class="text-sm text-zinc-600">
                    {{ $t('No shifts in the selected period.') }}
                </p>
            </div>

            <!-- Projektgruppen-Pills (unique im Zeitraum) -->
            <div v-if="uniqueGroupsForRange.length" class="flex flex-wrap gap-2">
                <Link
                    v-for="group in uniqueGroupsForRange"
                    :key="group.id"
                    :href="route('projects.tab', { project: group.id, projectTab: firstProjectShiftTabId })"
                    :disabled="linkDisabledForGroup(group)"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-2 py-1 text-xs font-semibold text-white
                        enabled:hover:bg-zinc-800 enabled:transition disabled:opacity-50"
                >
                    <PropertyIcon v-if="group.icon" :name="group.icon" class="size-4" aria-hidden="true" />
                    <span class="truncate max-w-[14rem]">{{ group.name }}</span>
                </Link>
            </div>

            <!-- Wochen-Zeilen: pro Kalenderwoche eine Zeile mit 7 Tagesspalten -->
            <div class="space-y-4">
                <div
                    v-for="week in weeks"
                    :key="week.key"
                    class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden"
                >
                    <!-- Wochen-Kopf -->
                    <div class="flex items-center justify-between gap-3 px-3 py-2 bg-zinc-50 border-b border-zinc-200">
                        <div class="text-sm font-semibold text-zinc-900">
                            {{ $t('KW') }} {{ week.weekNumber }}
                            <span class="ml-2 text-xs font-normal text-zinc-500">{{ week.rangeLabel }}</span>
                        </div>
                        <div class="text-xs text-zinc-600">
                            ({{ week.totalWork }} | {{ week.totalBreak }})
                        </div>
                    </div>

                    <!-- Tagesspalten -->
                    <div class="overflow-x-auto">
                        <div class="grid grid-cols-7 divide-x divide-zinc-200 min-w-[1190px]">
                            <div
                                v-for="day in week.days"
                                :key="day.date"
                                class="flex flex-col min-h-[10rem]"
                                :class="{ 'bg-zinc-50/80 opacity-60': !isInRequestedRange(day.date) }"
                            >
                                <!-- Tages-Kopf -->
                                <div
                                    class="px-2 py-1.5 border-b border-zinc-100 flex items-center justify-between gap-1"
                                    :class="isToday(day.date) ? 'bg-blue-50' : 'bg-white'"
                                >
                                    <span class="text-xs font-semibold" :class="isToday(day.date) ? 'text-blue-700' : 'text-zinc-800'">
                                        {{ weekdayShort(day.date) }} {{ formatDayShort(day.date) }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <div v-if="violationsForDay(day).length" class="flex items-center gap-0.5">
                                            <div
                                                v-for="violation in violationsForDay(day)"
                                                :key="violation.id"
                                                class="flex h-4 w-4 items-center justify-center"
                                                :aria-label="violationTooltip(violation)"
                                                v-tooltip.bottom="{
                                                    value: violationTooltip(violation),
                                                    appendTo: 'body',
                                                    class: 'aw-tooltip',
                                                    position: 'bottom',
                                                    useTranslation: false
                                                }"
                                            >
                                                <span v-if="violation.status === 'resolved'" class="relative inline-flex h-3.5 w-3.5">
                                                    <svg class="h-3.5 w-3.5" :style="{ color: violation.shift_rule?.warning_color || '#ff0000' }" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    <svg class="absolute -bottom-1 -right-1 h-2.5 w-2.5 rounded-full bg-white text-green-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                                <svg v-else class="h-3.5 w-3.5" :style="{ color: violation.shift_rule?.warning_color || '#ff0000' }" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                        <span v-if="hasWorkTime(day)" class="text-[10px] text-zinc-500">
                                            {{ day.totalWorkTime }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Feiertage -->
                                <div v-if="day.holidays?.length" class="px-2 pt-1.5 space-y-1">
                                    <div
                                        v-for="holiday in day.holidays"
                                        :key="`${day.date}-holiday-${holiday.id}`"
                                        class="flex items-center gap-1.5 rounded-md px-1.5 py-1 text-[11px] font-medium"
                                        :style="{
                                            backgroundColor: (holiday.color ?? '#fde68a') + '33',
                                            color: '#3f3f46'
                                        }"
                                        :title="holiday.subdivisions?.length ? holiday.subdivisions.join(', ') : holiday.name"
                                    >
                                        <span
                                            class="inline-block size-2 shrink-0 rounded-full"
                                            :style="{ backgroundColor: holiday.color ?? '#f59e0b' }"
                                        />
                                        <span class="break-words min-w-0">{{ holiday.name }}</span>
                                    </div>
                                </div>

                                <!-- Tagesdienste -->
                                <div v-if="day.dayServices?.length" class="px-2 pt-1.5 flex flex-wrap gap-1">
                                    <span
                                        v-for="dayService in day.dayServices"
                                        :key="`${day.date}-day-service-${dayService.id}`"
                                        class="inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-1.5 py-0.5 text-[11px] text-zinc-800"
                                        :title="dayService.name"
                                    >
                                        <PropertyIcon
                                            v-if="dayService.icon"
                                            :name="dayService.icon"
                                            class="size-3.5 shrink-0"
                                            :style="{ color: dayService.hex_color ?? '#3f3f46' }"
                                        />
                                        <span class="break-words min-w-0">{{ dayService.name }}</span>
                                    </span>
                                </div>

                                <!-- Schichten / Individualzeiten des Tages -->
                                <div class="p-2 space-y-2 flex-1">
                                    <template v-if="itemsForDay(day).length">
                                        <div v-for="i in itemsForDay(day)" :key="i._key" class="space-y-1">
                                            <div class="text-[11px] text-zinc-600">
                                                {{ i.start }}–{{ i.end }}
                                                <span v-if="i._crossesMidnight" class="ml-1 inline-block rounded bg-zinc-100 px-1 py-0.5">
                                                    → +1&nbsp;Tag
                                                </span>
                                            </div>

                                            <SingleUserShift
                                                v-if="i._type === 'shift'"
                                                :user-to-edit-id="userToEditId"
                                                :first-project-shift-tab-id="firstProjectShiftTabId"
                                                :shift="i"
                                                :type="type"
                                                :project="i?.project ?? i.event?.project ?? null"
                                            />

                                            <div
                                                v-else
                                                class="rounded-lg border border-zinc-200 bg-white shadow-sm overflow-hidden transition hover:shadow-md"
                                            >
                                                <div class="flex items-start justify-between gap-2 px-2 py-1.5 bg-zinc-100 text-zinc-900">
                                                    <span class="break-words min-w-0 text-xs font-semibold">
                                                        {{ $t('Individual time') }}: {{ i.title ?? '' }}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        @click="openEditIndividualTimeModal(i)"
                                                        class="p-1 rounded hover:bg-zinc-200 transition"
                                                        :title="$t('Edit')"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <div class="px-2 py-2 space-y-1.5">
                                                    <span class="text-xs font-medium text-zinc-900">
                                                        <template v-if="i.full_day">
                                                            {{ $t('All day') }}
                                                        </template>
                                                        <template v-else>
                                                            {{ i.start_time }} – {{ i.end_time }}
                                                        </template>
                                                    </span>

                                                    <!-- Kommentare/Notizen des Tages (aus der Schichtplan-Zelle) -->
                                                    <div v-if="i._showDayComments && day.comments?.length" class="space-y-1">
                                                        <div
                                                            v-for="comment in day.comments"
                                                            :key="`${day.date}-comment-${comment.id}`"
                                                            class="flex items-start gap-1.5 rounded-md bg-amber-50 border border-amber-100 px-1.5 py-1"
                                                        >
                                                            <PropertyIcon name="IconNote" class="size-3.5 shrink-0 mt-0.5 text-amber-600" aria-hidden="true" />
                                                            <span class="break-words min-w-0 text-[11px] text-zinc-700">{{ comment.comment }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <div
                                        v-else-if="!day.holidays?.length && !day.dayServices?.length && !day.comments?.length"
                                        class="h-full flex items-center justify-center text-xs text-zinc-300 select-none"
                                    >
                                        –
                                    </div>

                                    <!-- Kommentare/Notizen ohne Individualzeit am selben Tag trotzdem anzeigen -->
                                    <div v-if="day.comments?.length && !hasIndividualTimes(day)" class="space-y-1">
                                        <div
                                            v-for="comment in day.comments"
                                            :key="`${day.date}-comment-${comment.id}`"
                                            class="flex items-start gap-1.5 rounded-md bg-amber-50 border border-amber-100 px-1.5 py-1"
                                        >
                                            <PropertyIcon name="IconNote" class="size-3.5 shrink-0 mt-0.5 text-amber-600" aria-hidden="true" />
                                            <span class="break-words min-w-0 text-[11px] text-zinc-700">{{ comment.comment }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug bei Bedarf -->
    <!--
    <pre class="text-xs text-zinc-500 mt-6">
        {{ shiftsInRange }}
    </pre>
    -->

    <!-- Edit Individual Time Modal -->
    <EditIndividualTimeModal
        v-if="showEditIndividualTimeModal && selectedIndividualTime"
        :individual-time="selectedIndividualTime"
        @closed="closeEditIndividualTimeModal"
    />

    <!-- Schichtverlauf – im Einsatzplan eines Users mit dessen Namen vorbelegt -->
    <ShiftHistoryModal
        v-if="showHistoryModal"
        :crafts="crafts"
        :initial-start-date="dateValue[0]"
        :initial-end-date="dateValue[1]"
        :prefill-search="prefillSearchName"
        @close="showHistoryModal = false"
    />
</template>

<script setup>
/**
 * Mein Einsatzplan – Zeitraum-Ansicht
 * - Zeigt ALLE eigenen Schichten im gewählten Zeitraum (dateValue[0]..dateValue[1])
 * - Übernacht-Schichten korrekt (Ende < Start ⇒ Ende + 1 Tag) und Zeitraum-Schnittmenge
 * - Gruppierung nach Raum (inkl. "Ohne Raum")
 */

import { computed, defineAsyncComponent, ref, watch, onMounted } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import UserShiftPlanFunctionBar from '@/Layouts/Components/ShiftPlanComponents/UserShiftPlanFunctionBar.vue'
import SingleUserShift from '@/Layouts/Components/ShiftPlanComponents/SingleUserEventShift.vue'
import EditIndividualTimeModal from '@/Layouts/Components/ShiftPlanComponents/EditIndividualTimeModal.vue'
import {is} from "laravel-permission-to-vuejs";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import { provideShiftPlanLookups } from '@/Composeables/useShiftPlanLookups.js';

const ShiftHistoryModal = defineAsyncComponent({
    loader: () => import('@/Pages/Shifts/Components/ShiftHistoryModal.vue'),
})

const showEditIndividualTimeModal = ref(false)
const selectedIndividualTime = ref(null)
const showHistoryModal = ref(false)

function openEditIndividualTimeModal(individualTime) {
    selectedIndividualTime.value = individualTime
    showEditIndividualTimeModal.value = true
}

function closeEditIndividualTimeModal() {
    showEditIndividualTimeModal.value = false
    selectedIndividualTime.value = null
}

const props = defineProps({
    daysWithData: { type: Object, required: false, default: null }, // optional – sonst aus $page.props
    crafts: { type: Array, required: true },
    type: { type: String, required: true },
    weeklyWorkingHours: { type: [Number, String], required: false, default: null },
    totalPlannedWorkingHours: { type: String, required: false, default: null },
    dateValue: { type: Array, required: true }, // [start, end] im ISO-Format YYYY-MM-DD
    firstProjectShiftTabId: { type: Number, required: true },
    userToEditId: { type: Number, required: true }
})

const page = usePage()
const daysWithData = computed(() => props.daysWithData ?? (page.props?.daysWithData || {}))

// Name, mit dem die Schichtverlauf-Suche im Einsatzplan vorbelegt wird: der Name des
// betrachteten Users (user_to_edit). Fallbacks: zusammengesetzter Name, generischer
// Name (Dienstleister/Freelancer) oder – zur Sicherheit – der eingeloggte User.
const prefillSearchName = computed(() => {
    const nameOf = (u) => {
        if (!u) return ''
        return (
            u.full_name ||
            [u.first_name, u.last_name].filter(Boolean).join(' ').trim() ||
            u.name ||
            ''
        )
    }
    return (
        nameOf(page.props?.user_to_edit) ||
        nameOf(page.props?.serviceProvider) ||
        nameOf(page.props?.freelancer) ||
        nameOf(page.props?.auth?.user)
    )
})

// Provide shiftPlanLookups so child components (e.g. SingleUserEventShift) can inject them
const { setLookups } = provideShiftPlanLookups()

function buildCraftsLookup(crafts) {
    if (!Array.isArray(crafts)) return {}
    const craftsById = {}
    for (const craft of crafts) {
        if (craft?.id != null) craftsById[craft.id] = craft
    }
    return { craftsById }
}

setLookups(buildCraftsLookup(props.crafts))
watch(() => props.crafts, (newCrafts) => {
    setLookups(buildCraftsLookup(newCrafts))
})

/** ---------- Zeitraum / Helpers ---------- **/
const range = computed(() => {
    const start = Array.isArray(props.dateValue) && props.dateValue[0] ? props.dateValue[0] : null
    const end   = Array.isArray(props.dateValue) && props.dateValue[1] ? props.dateValue[1] : start
    return {
        start,
        end: end ?? start,
        startAt: start ? new Date(`${start}T00:00:00`) : null,
        endAt: (end ?? start) ? new Date(`${end ?? start}T23:59:59`) : null
    }
})

const rangeLabel = computed(() => {
    if (!range.value.start || !range.value.end) return ''
    const s = new Date(`${range.value.start}T00:00:00`)
    const e = new Date(`${range.value.end}T00:00:00`)
    return `${formatDateDMY(s)} – ${formatDateDMY(e)}`
})

function toDateTime(dateISO, timeHHMM) {
    return new Date(`${dateISO}T${timeHHMM}:00`)
}
function addDays(d, days) {
    const x = new Date(d)
    x.setDate(x.getDate() + days)
    return x
}

function toISODate(d) {
    const yyyy = d.getFullYear()
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    return `${yyyy}-${mm}-${dd}`
}
function intersects(aStart, aEnd, bStart, bEnd) {
    return aEnd >= bStart && aStart <= bEnd
}
function formatDateDMY(d) {
    const dd = String(d.getDate()).padStart(2, '0')
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const yyyy = d.getFullYear()
    return `${dd}.${mm}.${yyyy}`
}
function parseHHMM(str) {
    if (!str) return 0
    const [h, m] = String(str).split(':').map(n => parseInt(n || '0', 10))
    return (h || 0) * 60 + (m || 0)
}
function fmtMinutes(total) {
    const sign = total < 0 ? '-' : ''
    const abs = Math.abs(total)
    const h = Math.floor(abs / 60)
    const m = abs % 60
    return `${sign}${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`
}
function formatDayShort(dateISO) {
    const [, m, d] = String(dateISO).split('-')
    return `${d}.${m}.`
}
function weekdayShort(dateISO) {
    const d = new Date(`${dateISO}T00:00:00`)
    const locale = document.documentElement.lang || 'de-DE'
    return d.toLocaleDateString(locale, { weekday: 'short' })
}
function isToday(dateISO) {
    return dateISO === toISODate(new Date())
}
function isInRequestedRange(dateISO) {
    const r = range.value
    if (!r.start || !r.end) return true
    return dateISO >= r.start && dateISO <= r.end
}
function hasWorkTime(day) {
    return parseHHMM(day?.totalWorkTime) > 0
}
function violationsForDay(day) {
    if (!day?.violations) return []
    return Array.isArray(day.violations) ? day.violations : Object.values(day.violations)
}
function violationTooltip(violation) {
    return [violation?.shift_rule?.name, violation?.shift_rule?.description]
        .filter(Boolean)
        .join(': ')
}
function hasIndividualTimes(day) {
    return Array.isArray(day?.individualTimes) && day.individualTimes.length > 0
}
function isoWeekNumber(date) {
    // ISO 8601: Woche des Donnerstags derselben Woche
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
    const dayNum = (d.getUTCDay() + 6) % 7
    d.setUTCDate(d.getUTCDate() - dayNum + 3)
    const firstThursday = new Date(Date.UTC(d.getUTCFullYear(), 0, 4))
    const firstDayNum = (firstThursday.getUTCDay() + 6) % 7
    firstThursday.setUTCDate(firstThursday.getUTCDate() - firstDayNum + 3)
    return 1 + Math.round((d - firstThursday) / (7 * 24 * 60 * 60 * 1000))
}

/** ---------- Wochen-Zeilen (eine Zeile pro Kalenderwoche, Mo–So) ---------- **/
function emptyDay(dateISO) {
    return {
        date: dateISO,
        shifts: [],
        individualTimes: [],
        dayServices: [],
        holidays: [],
        comments: [],
        totalWorkTime: '00:00',
        totalBreakTime: '00:00',
    }
}

const weeks = computed(() => {
    const days = Object.values(daysWithData.value || {})
        .filter(d => d?.date)
        .sort((a, b) => String(a.date).localeCompare(String(b.date)))

    const weekMap = new Map()
    for (const day of days) {
        const d = new Date(`${day.date}T00:00:00`)
        const dow = (d.getDay() + 6) % 7 // 0 = Montag
        const monday = addDays(d, -dow)
        const key = toISODate(monday)
        if (!weekMap.has(key)) {
            weekMap.set(key, { key, monday, days: new Array(7).fill(null) })
        }
        weekMap.get(key).days[dow] = day
    }

    const result = Array.from(weekMap.values()).sort((a, b) => a.key.localeCompare(b.key))
    for (const week of result) {
        for (let i = 0; i < 7; i++) {
            if (!week.days[i]) {
                week.days[i] = emptyDay(toISODate(addDays(week.monday, i)))
            }
        }
        week.weekNumber = isoWeekNumber(week.monday)
        week.rangeLabel = `${formatDateDMY(week.monday)} – ${formatDateDMY(addDays(week.monday, 6))}`
        week.totalWork = fmtMinutes(week.days.reduce((sum, d) => sum + parseHHMM(d.totalWorkTime), 0))
        week.totalBreak = fmtMinutes(week.days.reduce((sum, d) => sum + parseHHMM(d.totalBreakTime), 0))
    }
    return result
})

/** ---------- Items (Schichten + Individualzeiten) eines einzelnen Tages ---------- **/
function itemsForDay(day) {
    const out = []

    for (const s of (Array.isArray(day?.shifts) ? day.shifts : [])) {
        if (!s?.start || !s?.end) continue

        const startAt = toDateTime(day.date, s.start)
        let endAt = toDateTime(day.date, s.end)
        if (endAt < startAt) endAt = addDays(endAt, 1)

        out.push({
            ...s,
            _type: 'shift',
            _day: day.date,
            _startAt: startAt,
            _endAt: endAt,
            _project: s.project,
            _crossesMidnight: endAt.toDateString() !== startAt.toDateString(),
            _key: `${s.id}-${day.date}-${s.start}-${s.end}`
        })
    }

    let isFirstIndividualTime = true
    for (const it of (Array.isArray(day?.individualTimes) ? day.individualTimes : [])) {
        const startTime = it?.full_day ? '00:00' : (it?.start_time ?? '00:00')
        const endTime = it?.full_day ? '23:59' : (it?.end_time ?? '23:59')
        const startAt = toDateTime(day.date, String(startTime).slice(0, 5))
        let endAt = toDateTime(day.date, String(endTime).slice(0, 5))
        if (endAt < startAt) endAt = addDays(endAt, 1)

        out.push({
            ...it,
            start: String(startTime).slice(0, 5),
            end: String(endTime).slice(0, 5),
            _type: 'individual_time',
            _day: day.date,
            _startAt: startAt,
            _endAt: endAt,
            _crossesMidnight: endAt.toDateString() !== startAt.toDateString(),
            // Tages-Kommentare nur an der ersten Individualzeit anzeigen (nicht mehrfach pro Tag)
            _showDayComments: isFirstIndividualTime,
            _key: `it-${it.id}-${day.date}-${startTime}-${endTime}`
        })
        isFirstIndividualTime = false
    }

    out.sort((a, b) => a._startAt.getTime() - b._startAt.getTime())
    return out
}

/** ---------- Shifts über Zeitraum einsammeln ---------- **/
const workItemsInRange = computed(() => {
    const r = range.value
    if (!r.startAt || !r.endAt) return []

    const entries = Object.values(daysWithData.value || {})
        .filter(d => d?.date && d.date >= r.start && d.date <= r.end)

    const out = []
    for (const day of entries) {
        const shifts = Array.isArray(day.shifts) ? day.shifts : []
        for (const s of shifts) {
            if (!s?.start || !s?.end) continue

            const startAt = toDateTime(day.date, s.start)
            let endAt = toDateTime(day.date, s.end)

            // Übernacht-Schicht: Ende < Start ⇒ +1 Tag
            if (endAt < startAt) endAt = addDays(endAt, 1)

            // Nur hinzufügen, wenn Schicht und Zeitraum sich schneiden
            if (intersects(startAt, endAt, r.startAt, r.endAt)) {
                out.push({
                    ...s,
                    _type: 'shift',
                    _day: day.date,
                    _startAt: startAt,
                    _endAt: endAt,
                    _project: s.project,
                    _crossesMidnight: endAt.toDateString() !== startAt.toDateString(),
                    _key: `${s.id}-${day.date}-${s.start}-${s.end}`
                })
            }
        }

        const individualTimes = Array.isArray(day.individualTimes) ? day.individualTimes : []
        for (const it of individualTimes) {
            const startTime = it?.full_day ? '00:00' : (it?.start_time ?? '00:00')
            const endTime = it?.full_day ? '23:59' : (it?.end_time ?? '23:59')
            const startAt = toDateTime(day.date, String(startTime).slice(0, 5))
            let endAt = toDateTime(day.date, String(endTime).slice(0, 5))

            // Übernacht (sollte bei Individualzeiten eigentlich nicht vorkommen) – wir behandeln es trotzdem konsistent.
            if (endAt < startAt) endAt = addDays(endAt, 1)

            if (intersects(startAt, endAt, r.startAt, r.endAt)) {
                out.push({
                    ...it,
                    start: String(startTime).slice(0, 5),
                    end: String(endTime).slice(0, 5),
                    _type: 'individual_time',
                    _day: day.date,
                    _startAt: startAt,
                    _endAt: endAt,
                    _crossesMidnight: endAt.toDateString() !== startAt.toDateString(),
                    _key: `it-${it.id}-${day.date}-${startTime}-${endTime}`
                })
            }
        }
    }

    // sort global nach Start
    out.sort((a, b) => a._startAt.getTime() - b._startAt.getTime())
    return out
})

/** ---------- Summen (Arbeitszeit/Pause) über Zeitraum ---------- **/
const totalWorkTimeInRange = computed(() => {
    const r = range.value
    if (!r.start || !r.end) return '00:00'
    const entries = Object.values(daysWithData.value || {})
        .filter(d => d?.date && d.date >= r.start && d.date <= r.end)
    const minutes = entries.reduce((sum, d) => sum + parseHHMM(d.totalWorkTime), 0)
    return fmtMinutes(minutes)
})
const totalBreakTimeInRange = computed(() => {
    const r = range.value
    if (!r.start || !r.end) return '00:00'
    const entries = Object.values(daysWithData.value || {})
        .filter(d => d?.date && d.date >= r.start && d.date <= r.end)
    const minutes = entries.reduce((sum, d) => sum + parseHHMM(d.totalBreakTime), 0)
    return fmtMinutes(minutes)
})

/** ---------- Gruppen-Pills (unique im Zeitraum) ---------- **/
const uniqueGroupsForRange = computed(() => {
    const map = new Map()
    workItemsInRange.value
        .filter(s => s?._type === 'shift')
        .forEach(s => {
        const project = s?.project ?? s?.event?.project
        if (!project) return

        if (project.is_group && !map.has(project.id)) map.set(project.id, project)

        if (!project.is_group && Array.isArray(project.groups)) {
            project.groups.forEach(g => {
                if (!map.has(g.id)) map.set(g.id, g)
            })
        }
        })
    return Array.from(map.values())
})


/** ---------- Prev / Next Zeitspanne (an Server melden) ---------- **/
const goToPrevAssignedDay = () => {
    const r = range.value
    if (!r.start || !r.end) return

    const startAt = new Date(`${r.start}T00:00:00`)
    const endAt = new Date(`${r.end}T00:00:00`)
    const spanDays = Math.round((endAt.getTime() - startAt.getTime()) / (1000 * 60 * 60 * 24)) + 1
    if (!Number.isFinite(spanDays) || spanDays <= 0) return

    const newStart = addDays(startAt, -spanDays)
    const newEnd = addDays(endAt, -spanDays)
    patchServerDateRange(toISODate(newStart), toISODate(newEnd))
}

const goToNextAssignedDay = () => {
    const r = range.value
    if (!r.start || !r.end) return

    const startAt = new Date(`${r.start}T00:00:00`)
    const endAt = new Date(`${r.end}T00:00:00`)
    const spanDays = Math.round((endAt.getTime() - startAt.getTime()) / (1000 * 60 * 60 * 24)) + 1
    if (!Number.isFinite(spanDays) || spanDays <= 0) return

    const newStart = addDays(startAt, spanDays)
    const newEnd = addDays(endAt, spanDays)
    patchServerDateRange(toISODate(newStart), toISODate(newEnd))
}

function patchServerDateRange(startDateStr, endDateStr) {
    const userId = page.props?.auth?.user?.id
    if (!userId) return
    router.patch(
        route('update.user.worker.shift-plan.filters.update', userId),
        {
            start_date: startDateStr,
            end_date: endDateStr
        },
        { preserveState: true, preserveScroll: true }
    )
}

/** ---------- Gruppen-Link deaktiviert, falls weder Admin noch Mitglied ---------- **/
const linkDisabledForGroup = (group) => {
    const currentUser = page.props?.auth?.user
    if (!currentUser) return true

    const isAdmin = is('artwork admin')

    const inGroup =
        Array.isArray(group?.users) &&
        group.users.some(u => u?.id === currentUser.id)

    // Falls keine Userliste zur Gruppe existiert, nicht deaktivieren
    if (!Array.isArray(group?.users)) return false

    return !(isAdmin || inGroup)
}
</script>
