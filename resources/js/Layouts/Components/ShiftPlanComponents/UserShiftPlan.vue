<template>
    <div class="w-full mb-10">
        <!-- Funktionsleiste -->
        <div class="mb-6 -ml-4">
            <UserShiftPlanFunctionBar
                :type="type"
                :totalPlannedWorkingHours="totalPlannedWorkingHours"
                :weeklyWorkingHours="weeklyWorkingHours"
                :dateValue="dateValue"
                :eventTypes="eventTypes"
                @previousTimeRange="goToPrevAssignedDay"
                @nextTimeRange="goToNextAssignedDay"
                :user_to_edit_id="userToEditId"
            />
        </div>

        <!-- Keine Schichten im Zeitraum -->
        <div v-if="workItemsInRange.length === 0" class="rounded-xl border border-zinc-200 bg-white p-8 text-center">
            <p class="text-sm text-zinc-600">
                {{ $t('No shifts in the selected period.') }}
            </p>
        </div>

        <!-- Zeitraum-Ansicht -->
        <div v-else class="space-y-4">
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

            <!-- Schichten (chronologisch über gesamten Zeitraum) -->
            <div class="grid grid-cols-1 gap-4">
                <div class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden">
                    <!-- Schichtenliste -->
                    <div class="p-3 space-y-3">
                        <div v-for="i in workItemsInRange" :key="i._key" class="space-y-1">
                            <!-- kleine Kopfzeile je Eintrag mit Datum + +1-Tag Hinweis -->
                            <div class="text-xs text-zinc-600 flex items-center justify-between">
                                <div>
                                    {{ formatDateDMY(i._startAt) }}
                                    · {{ i.start }}–{{ i.end }}
                                    <span v-if="i._crossesMidnight" class="ml-1 inline-block rounded bg-zinc-100 px-1.5 py-0.5">
                                        → +1&nbsp;Tag
                                    </span>
                                </div>
                                <div v-if="i._type === 'shift'" class="text-zinc-500 font-medium">
                                    {{ i?.room?.name ?? i?.event?.room?.name ?? $page.props?.translations?.no_room ?? 'Ohne Raum' }}
                                </div>
                            </div>

                            <SingleUserEventShift
                                v-if="i._type === 'shift'"
                                :user-to-edit-id="userToEditId"
                                :first-project-shift-tab-id="firstProjectShiftTabId"
                                :event-type="eventTypes.find(et => et.id === (i?.event?.event_type_id ?? i.event?.event_type_id)) ?? null"
                                :shift="i"
                                :event="i?.event ?? null"
                                :type="type"
                                :project="i?.project ?? i.event?.project ?? null"
                            />

                            <div
                                v-else
                                class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden transition hover:shadow-md"
                            >
                                <div class="flex items-center justify-between gap-2 px-3 py-2 bg-zinc-100 text-zinc-900">
                                    <span class="truncate text-sm font-semibold">
                                        {{ $t('Individual time') }}: {{ i.title ?? '' }}
                                    </span>
                                </div>

                                <div class="px-3 py-3">
                                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-2">
                                        <span class="text-sm font-medium text-zinc-900">
                                            <template v-if="i.full_day">
                                                {{ $t('All day') }}
                                            </template>
                                            <template v-else>
                                                {{ i.start_time }} – {{ i.end_time }}
                                            </template>
                                        </span>
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
</template>

<script setup>
/**
 * Mein Einsatzplan – Zeitraum-Ansicht
 * - Zeigt ALLE eigenen Schichten im gewählten Zeitraum (dateValue[0]..dateValue[1])
 * - Übernacht-Schichten korrekt (Ende < Start ⇒ Ende + 1 Tag) und Zeitraum-Schnittmenge
 * - Gruppierung nach Raum (inkl. "Ohne Raum")
 */

import { computed, ref, watch, onMounted } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import UserShiftPlanFunctionBar from '@/Layouts/Components/ShiftPlanComponents/UserShiftPlanFunctionBar.vue'
import SingleUserEventShift from '@/Layouts/Components/ShiftPlanComponents/SingleUserEventShift.vue'
import {is} from "laravel-permission-to-vuejs";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    daysWithData: { type: Object, required: false, default: null }, // optional – sonst aus $page.props
    eventTypes: { type: Array, required: true },
    type: { type: String, required: true },
    weeklyWorkingHours: { type: [Number, String], required: false, default: null },
    totalPlannedWorkingHours: { type: String, required: false, default: null },
    dateValue: { type: Array, required: true }, // [start, end] im ISO-Format YYYY-MM-DD
    firstProjectShiftTabId: { type: Number, required: true },
    userToEditId: { type: Number, required: true }
})

const page = usePage()
const daysWithData = computed(() => props.daysWithData ?? (page.props?.daysWithData || {}))

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
