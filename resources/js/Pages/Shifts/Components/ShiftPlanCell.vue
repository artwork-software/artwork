<template>
    <div
        class="shiftCell h-full overflow-hidden rounded-lg bg-surface-sunken/10 text-xs text-white hover:opacity-100 relative"
        :class="[ unavailableAssignmentConflict
          ? (unavailableAssignmentConflict.committed
              ? 'ring-2 ring-inset ring-danger-border bg-danger-border/20'
              : 'ring-2 ring-inset ring-warning-border bg-warning-border/20')
          : hasMultiShiftGroups && 'ring-2 ring-inset ring-danger-border',
    ]"
    >
        <!-- Innerer Scroll-Container: Streifen unten bleiben fix am Zellboden -->
        <div
            class="shiftCellScroll h-full overflow-y-auto"
            :class="[compactMode ? 'px-2 py-1' : 'p-2']"
            :style="assignmentsToday.length ? { paddingBottom: assignmentStripAreaHeight + 'px' } : undefined"
        >
        <div :class="classes">
            <!-- Abwesenheit -->
            <span
                v-if="isOnVacation"
                class="text-[#f08b32]"
            >
                {{ vacationLabel }}<template v-if="availabilitiesToday.length || cellParts.length || compensationDayToday">, </template>
            </span>

            <!-- Verfügbarkeit -->
            <template v-for="av in availabilitiesToday" :key="`av-top:${av.id}`">
                <span class="text-success">
                    {{ availabilityLabel(av) }}<template v-if="cellParts.length || compensationDayToday">, </template>
                </span>
            </template>

            <span v-if="compensationDayToday" class="text-special-teal">
                {{ compensationDayToday === 'full' ? t('Compensation day off') : t('Half compensation day off') }}<template v-if="compensationDayToday === 'half' && compensationHalfPeriod"> ({{ compensationHalfPeriod === 'morning' ? t('Morning') : t('Afternoon') }})</template><template v-if="cellParts.length">, </template>
            </span>

            <template v-for="part in cellParts" :key="part.key">
                <span :class="part.class" :title="part.title || undefined">
                    {{ part.text }}
                </span>
            </template>
        </div>
        </div>

        <!--
            Projektzuordnungs-Streifen: Per-Zellen-Segmente statt Overlay (Virtual2DGrid
            virtualisiert Spalten). Abgerundete Enden nur an Serien-Grenzen, dadurch
            wirken die Segmente benachbarter Tage wie ein durchlaufender Balken.
            Verbindlich = gefüllt, Wunsch = gestreift; Name + Zeitraum on Hover.
        -->
        <div
            v-if="assignmentsToday.length"
            class="absolute bottom-0 left-0 right-0 z-10"
        >
            <div
                v-for="assignment in visibleAssignmentsToday"
                :key="`pda:${assignment.id}`"
                class="h-[5px] mt-[2px]"
                :class="[ assignment.date === assignment.series_start ? 'rounded-l-full ml-0.5' : '',
                    assignment.date === assignment.series_end ? 'rounded-r-full mr-0.5' : '',
                    isAssignmentDimmed(assignment) ? 'opacity-30' : '',
                ]"
                :style="assignmentStripStyle(assignment)"
                :title="assignmentLabel(assignment, t('Wish'))"
            />
            <div
                v-if="overflowAssignmentsToday.length"
                class="h-[5px] mt-[2px] rounded-full mx-0.5 bg-text-subtle/70"
                :title="overflowAssignmentsToday.map(a => assignmentLabel(a, t('Wish'))).join('\n')"
            />
        </div>

        <!-- Violation indicators -->
        <div v-if="violationsToday.length || unavailableAssignmentConflict" class="absolute top-0.5 right-0.5 flex items-center gap-0.5">
            <!-- Eingeplant, aber nicht verfügbar (z.B. nachträglich krank gemeldet).
                 Rot, wenn eine betroffene Schicht festgeschrieben ist. -->
            <div
                v-if="unavailableAssignmentConflict"
                class="h-4 w-4 flex items-center justify-center"
                :title="unavailableAssignmentConflict.committed
                    ? t('Assigned but not available (committed shift)')
                    : t('Assigned but not available')"
            >
                <svg class="h-3.5 w-3.5" :class="unavailableAssignmentConflict.committed ? 'text-danger' : 'text-warning'" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div
                v-for="violation in violationsToday"
                :key="violation.id"
                class="h-4 w-4 flex items-center justify-center"
                :title="(violation.shift_rule?.name || '') + ': ' + (violation.shift_rule?.description || '')"
            >
                <!-- Bearbeitet (resolved): Warndreieck in Warnfarbe MIT grünem Haken-Badge -->
                <span v-if="violation.status === 'resolved'" class="relative inline-flex h-3.5 w-3.5">
                    <svg class="h-3.5 w-3.5" :style="{ color: violation.shift_rule?.warning_color || '#ff0000' }" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <svg class="absolute -bottom-1 -right-1 h-2.5 w-2.5 rounded-full bg-white text-success" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </span>
                <!-- Offener/ignorierter Verstoß: Warndreieck in Warnfarbe -->
                <svg v-else class="h-3.5 w-3.5" :style="{ color: violation.shift_rule?.warning_color || '#ff0000' }" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { assignmentStripStyle, assignmentLabel } from '@/Composeables/UseProjectDayAssignments.js'

const { t } = useI18n()

const props = defineProps({
    user: { type: Object, required: true },
    day: { type: Object, required: true },
    classes: { type: Array, default: () => [] },
})

const page = usePage()

/** Kompakte Ansicht: weniger vertikales Padding, damit eine Textzeile ohne Scrollbalken in die 32px-Zeile passt */
const compactMode = computed(() => !!page.props.auth?.user?.compact_mode)

/**
 * Vacation types: Typ-Label-Mapping
 */
const vacationTypeMap = {
    AVAILABLE: 'Verfügbar',
    OFF_WORK: 'Arbeitsfreier Tag',
    NOT_AVAILABLE: 'Nicht Verfügbar',
    FREE_WORK: 'Frei',
}

/** Abwesenheit am Tag (Vacation-Einträge) */
const vacationToday = computed(() => {
    const list = props.user?.vacations ?? []
    return list.find(v => v?.date === props.day.withoutFormat) ?? null
})

const isOnVacation = computed(() => !!vacationToday.value)

const vacationLabel = computed(() => {
    const v = vacationToday.value
    if (!v) return t('not available')
    const label = vacationTypeMap[v.type] || t('not available')
    if (!v.full_day && v.start_time && v.end_time) {
        return `${v.start_time} - ${v.end_time} ${label}`
    }
    return label
})

/** Verfügbarkeit-Label: Zeitraum + Kommentar */
function availabilityLabel(av) {
    const parts = []
    if (!av.full_day && av.start_time && av.end_time) {
        parts.push(`${av.start_time} - ${av.end_time}`)
    }
    if (av.comment) {
        parts.push(`„${av.comment}"`)
    }
    return parts.length ? parts.join(' ') : t('Available')
}

/** ID des Users der Zelle (für italic-Prüfung) */
const cellUserId = computed(() => props.user?.element?.id)
const cellUserType = computed(() => props.user?.type)

/** Robust: ShiftGroup-ID aus allen gängigen Varianten */
function getShiftGroupId(shift) {
    if (shift?.shiftGroup?.id != null) return shift.shiftGroup.id
    if (shift?.shift_group_id != null) return shift.shift_group_id
    if (shift?.shiftGroupId != null) return shift.shiftGroupId
    if (shift?.group_id != null) return shift.group_id
    if (shift?.group?.id != null) return shift.group.id
    if (shift?.shift_group?.id != null) return shift.shift_group.id
    return null
}

/** Alle Schichten am Tag – nur Schichten die an diesem Tag starten (Folgetage werden nicht angezeigt) */
const shiftsToday = computed(() => {
    const list = props.user?.element?.shifts ?? []
    const dayA = props.day.fullDay
    const dayB = props.day.withoutFormat

    return list.filter(s => {
        const start = s?.start_of_shift
        return start === dayA || start === dayB
    })
})

/** Individual times am Tag (einmal filtern) */
const individualTimesToday = computed(() => {
    const list = props.user?.individual_times ?? []
    const d = props.day.withoutFormat
    return list.filter(it =>
        Array.isArray(it?.days_of_individual_time) &&
        it.days_of_individual_time.includes(d)
    )
})

/** Kommentar am Tag (nur 1x sauber lesen) */
const shiftCommentToday = computed(() => {
    return props.user?.shift_comments?.[props.day.withoutFormat]?.[0] ?? null
})

/** Availabilities am Tag (nur 1x) */
const availabilitiesToday = computed(() => {
    return props.user?.availabilities?.[props.day.fullDay] ?? []
})

/** Prüft ob ein Eintrag vom User der Zelle selbst erstellt wurde (italic) */
function isSelfCreated(createdBy) {
    // == statt === um Typ-Mismatches (String vs Number) zu vermeiden
    return createdBy != null && cellUserId.value != null && createdBy == cellUserId.value && cellUserType.value === 0
}

/** Render-Parts: ein Array, das das Template nur noch "abspult" */
const cellParts = computed(() => {
    const parts = []

    // Shifts
    for (const s of shiftsToday.value) {
        const craftSuffix =
            s?.craftAbbreviation !== s?.craftAbbreviationUser && s?.craftAbbreviationUser
                ? s?.craftAbbreviation
                    ? ` [${s.craftAbbreviationUser} ${t('in')} ${s.craftAbbreviation}]`
                    : ` [${s.craftAbbreviationUser}]`
                : ''

        // Determine time display for multi-day shifts
        let timeDisplay = `${s.startPivot} - ${s.endPivot}`
        const daysOfShift = s?.days_of_shift
        if (Array.isArray(daysOfShift) && daysOfShift.length > 1 && s.startPivot && s.endPivot) {
            // Mehrtägige Schicht: volle Zeitspanne mit Pfeil anzeigen
            timeDisplay = `${s.startPivot} - ${s.endPivot} →`
        }

        // Zu-/Absage der Person: grüne/rote Unterstreichung (lesbar auf jeder
        // Zellfarbe) + Erklärung im Hover-Titel
        let confirmationClass = ''
        let confirmationTitle = null
        if (page.props.shift_confirmation_enabled && s?.confirmationStatus) {
            const accepted = s.confirmationStatus === 'accepted'
            confirmationClass = accepted
                ? 'underline decoration-success decoration-2'
                : 'underline decoration-danger decoration-2'
            const dateLabel = s.confirmationAt
                ? new Date(s.confirmationAt).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
                : '–'
            confirmationTitle = accepted
                ? t('Accepted on {date}', { date: dateLabel })
                : t('Declined on {date}', { date: dateLabel })
            if (s.confirmationComment) {
                confirmationTitle += ` – „${s.confirmationComment}"`
            }
        }

        parts.push({
            key: `shift:${s.id}`,
            text: `${timeDisplay} ${s?.roomName ?? ''}${craftSuffix}, `,
            class: confirmationClass,
            title: confirmationTitle,
        })
    }

    // Individual Times
    for (const it of individualTimesToday.value) {
        let time = 'All day'
        if (it?.start_time && it?.end_time) {
            const currentDay = props.day.withoutFormat
            const startDate = it?.start_date
            const endDate = it?.end_date

            // Check if individual time spans multiple days
            if (startDate && endDate && startDate !== endDate) {
                if (currentDay === startDate) {
                    // First day: show start_time - 00:00
                    time = `${it.start_time} - 00:00`
                } else if (currentDay === endDate) {
                    // Last day: show 00:00 - end_time
                    time = `00:00 - ${it.end_time}`
                } else {
                    // Middle day (if any): show full day
                    time = '00:00 - 00:00'
                }
            } else {
                time = `${it.start_time} - ${it.end_time}`
            }
        }

        parts.push({
            key: `it:${it.id}`,
            text: `${time} ${it?.title ?? ''}, `,
            class: '',
        })
    }

    // Comment
    const comment = shiftCommentToday.value
    if (comment?.comment) {
        parts.push({
            key: 'comment',
            text: comment.comment,
            class: isSelfCreated(comment.created_by) ? 'italic' : '',
        })
    }

    return parts
})

/** Compensation day offs am Tag */
const compensationDayToday = computed(() => {
    const dayOffs = props.user?.compensation_day_offs?.[props.day.withoutFormat]
    if (!dayOffs) return null
    const arr = Array.isArray(dayOffs) ? dayOffs : Object.values(dayOffs)
    if (!arr.length) return null
    // Sum up values for the day
    const totalValue = arr.reduce((sum, d) => sum + parseFloat(d.value || 0), 0)
    return totalValue >= 1.0 ? 'full' : 'half'
})

/** Vormittag/Nachmittag bei halbem freien Tag */
const compensationHalfPeriod = computed(() => {
    const dayOffs = props.user?.compensation_day_offs?.[props.day.withoutFormat]
    if (!dayOffs) return null
    const arr = Array.isArray(dayOffs) ? dayOffs : Object.values(dayOffs)
    const half = arr.find(d => d.half_day_period === 'morning' || d.half_day_period === 'afternoon')
    return half ? half.half_day_period : null
})

/** Projektzuordnungen am Tag (verbindlich + Wunsch), sortiert für stabile Lanes */
const assignmentsToday = computed(() => {
    const list = props.user?.project_assignments?.[props.day.withoutFormat] ?? []
    if (!list.length) return []
    return [...list].sort((a, b) =>
        a.series_start === b.series_start
            ? String(a.group_id).localeCompare(String(b.group_id))
            : a.series_start < b.series_start ? -1 : 1
    )
})

const MAX_ASSIGNMENT_LANES = 2
const visibleAssignmentsToday = computed(() => assignmentsToday.value.slice(0, MAX_ASSIGNMENT_LANES))
const overflowAssignmentsToday = computed(() => assignmentsToday.value.slice(MAX_ASSIGNMENT_LANES))

/** Höhe des Streifen-Bereichs (Lanes à 5px + 2px Abstand) als Padding fürs Scroll-Div */
const assignmentStripAreaHeight = computed(() => {
    const lanes = Math.min(assignmentsToday.value.length, MAX_ASSIGNMENT_LANES)
        + (overflowAssignmentsToday.value.length ? 1 : 0)
    return lanes * 7 + 2
})

/** Projektmodus (DP-07/2.20): Streifen fremder Projekte dimmen */
const isAssignmentDimmed = (assignment) => {
    const settings = page.props.shift_plan_settings ?? page.props.auth?.user?.calendar_settings
    if (!settings?.use_project_time_period || !settings?.time_period_project_id) return false
    return assignment.project_id !== settings.time_period_project_id
}

/** Violations am Tag */
const violationsToday = computed(() => {
    const violations = props.user?.violations?.[props.day.withoutFormat]
    if (!violations) return []
    return Array.isArray(violations) ? violations : Object.values(violations)
})

/**
 * Eingeplant, aber nicht verfügbar: Person hat am Tag eine Schicht UND einen
 * Abwesenheits-Eintrag (Typ != AVAILABLE, z.B. Krankheit). Die Zuweisung bleibt
 * bestehen (Stundenabrechnung bei festgeschriebenen Schichten) — die Zelle wird
 * nur hervorgehoben, damit Planende den Belegungsbedarf sehen.
 */
const unavailableAssignmentConflict = computed(() => {
    if (!shiftsToday.value.length) return null

    const conflictingVacations = (props.user?.vacations ?? []).filter(
        v => v?.date === props.day.withoutFormat && v?.type !== 'AVAILABLE'
    )
    if (!conflictingVacations.length) return null

    const vacationHitsShift = (v, s) => {
        if (v.full_day || !v.start_time || !v.end_time) return true
        const start = s.startPivot || s.start
        const end = s.endPivot || s.end
        if (!start || !end) return true
        // Schicht über Mitternacht: Konflikt, wenn Abwesenheit den Abend- oder Morgenteil trifft
        if (end <= start) return v.end_time > start || v.start_time < end
        return v.start_time < end && v.end_time > start
    }

    let hasConflict = false
    let committed = false
    for (const s of shiftsToday.value) {
        if (!conflictingVacations.some(v => vacationHitsShift(v, s))) continue
        hasConflict = true
        if (s.isCommitted ?? s.is_committed) {
            committed = true
            break
        }
    }

    return hasConflict ? { committed } : null
})

/** Rahmenregel: mind. 2 unterschiedliche Gruppen am Tag */
const hasMultiShiftGroups = computed(() => {
    if (!page.props?.warn_multiple_assignments) return false

    const ids = new Set()
    for (const s of shiftsToday.value) {
        const id = getShiftGroupId(s)
        if (id != null) ids.add(id)
        if (ids.size >= 2) return true // early-exit = schneller
    }
    return false
})
</script>

<style scoped>
/*
 * Zellen der Mitarbeiter-Übersicht: nur vertikal scrollen (horizontaler Balken
 * würde bei klassischen OS-Scrollbalken einen Großteil der Zellhöhe fressen).
 * 6px statt Browser-Standard (~15px), aber bewusst breiter als die 2px des
 * Hauptkalenders, damit er gut sichtbar und greifbar bleibt.
 */
.shiftCellScroll {
    overflow-x: hidden;
    scrollbar-color: #d4d4d4 transparent;
    scrollbar-width: thin;
}
.shiftCellScroll::-webkit-scrollbar { width: 6px; }
.shiftCellScroll::-webkit-scrollbar-thumb { background-color: #d4d4d4; border-radius: 10px; }
.shiftCellScroll::-webkit-scrollbar-track { background-color: transparent; }
</style>
