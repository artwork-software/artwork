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
                @next-time-range="goToNextAssignedDay"
                :user_to_edit_id="userToEditId"
            />
        </div>

        <!-- Keine Schichten im Zeitraum -->
        <div v-if="shiftsInRange.length === 0" class="rounded-xl border border-zinc-200 bg-white p-8 text-center">
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

            <!-- Räume & Schichten (über gesamten Zeitraum) -->
            <div class="grid grid-cols-1 gap-4">
                <div
                    v-for="bucket in roomBuckets"
                    :key="bucket.key"
                    class="rounded-xl border border-zinc-200 bg-white shadow-sm overflow-hidden"
                >
                    <!-- Raumkopf -->
                    <div class="flex items-center justify-between border-b border-zinc-200 px-3 py-2">
                        <div class="text-sm font-semibold text-zinc-900">
                            {{ bucket.title }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            {{ $t('Shifts') }}: {{ bucket.shifts.length }}
                        </div>
                    </div>

                    <!-- Schichtenliste -->
                    <div class="p-3 space-y-3">
                        <div v-for="s in bucket.shifts" :key="s._key" class="space-y-1">
                            <!-- kleine Kopfzeile je Schicht mit Datum + +1-Tag Hinweis -->
                            <div class="text-xs text-zinc-600">
                                {{ formatDateDMY(s._startAt) }}
                                · {{ s.start }}–{{ s.end }}
                                <span v-if="s._crossesMidnight" class="ml-1 inline-block rounded bg-zinc-100 px-1.5 py-0.5">
                                    → +1&nbsp;Tag
                                </span>
                            </div>

                            <SingleUserEventShift
                                :user-to-edit-id="userToEditId"
                                :first-project-shift-tab-id="firstProjectShiftTabId"
                                :event-type="eventTypes.find(et => et.id === (s?.event?.event_type_id ?? s.event?.event_type_id)) ?? null"
                                :shift="s"
                                :event="s?.event ?? null"
                                :type="type"
                                :project="s?.project ?? s.event?.project ?? null"
                            />
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
const shiftsInRange = computed(() => {
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
                    _day: day.date,
                    _startAt: startAt,
                    _endAt: endAt,
                    _crossesMidnight: endAt.toDateString() !== startAt.toDateString(),
                    _key: `${s.id}-${day.date}-${s.start}-${s.end}`
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
    shiftsInRange.value.forEach(s => {
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

/** ---------- Raum-Buckets (über gesamten Zeitraum) ---------- **/
const roomBuckets = computed(() => {
    const buckets = new Map()

    shiftsInRange.value.forEach(s => {
        const room = s?.room ?? s?.event?.room ?? null
        const key = room?.id ? String(room.id) : 'no-room'

        if (!buckets.has(key)) {
            buckets.set(key, {
                key,
                room,
                title: room?.name ?? page.props?.translations?.no_room ?? 'Ohne Raum',
                shifts: []
            })
        }
        buckets.get(key).shifts.push(s)
    })

    // sort innerhalb der Buckets nach Startzeit
    buckets.forEach(b => {
        b.shifts.sort((a, b) => a._startAt.getTime() - b._startAt.getTime())
    })

    // "Ohne Raum" nach unten
    const arr = Array.from(buckets.values())
    arr.sort((a, b) => (a.key === 'no-room') - (b.key === 'no-room'))
    return arr
})

/** ---------- Prev / Next belegter Tag (an Server melden) ---------- **/
const assignedDatesSorted = computed(() => {
    const entries = Object.values(daysWithData.value || {})
    const withShifts = entries.filter(d => Array.isArray(d.shifts) && d.shifts.length > 0)
    return withShifts.map(d => d.date).sort((a, b) => a.localeCompare(b))
})

const goToPrevAssignedDay = () => {
    const start = range.value.start
    if (!start) return
    const dates = assignedDatesSorted.value
    const idx = dates.findIndex(d => d >= start)
    const prev = idx > 0 ? dates[idx - 1] : null
    if (prev) patchServerDate(prev)
}

const goToNextAssignedDay = () => {
    const end = range.value.end
    if (!end) return
    const dates = assignedDatesSorted.value
    const idx = dates.findIndex(d => d > end)
    const next = idx !== -1 ? dates[idx] : null
    if (next) patchServerDate(next)
}

function patchServerDate(dateStr) {
    const userId = page.props?.auth?.user?.id
    if (!userId) return
    router.patch(
        route('update.user.worker.shift-plan.filters.update', userId),
        { start_date: dateStr, end_date: dateStr },
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
