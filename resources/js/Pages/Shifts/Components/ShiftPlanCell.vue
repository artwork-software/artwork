<template>
    <div
        class="shiftCell h-full cursor-pointer overflow-y-auto rounded-lg bg-gray-50/10 p-2 text-xs text-white hover:opacity-100"
        :class="[
      hasMultiShiftGroups && 'ring-2 ring-inset ring-rose-400',
    ]"
    >
        <div :class="classes">
            <!-- Urlaub zuerst anzeigen, dann restliche Teile -->
            <span
                v-if="isOnVacation"
                :class="vacationIsItalic ? 'italic' : ''"
                class="text-[#f08b32]"
            >
                {{ vacationLabel }}<template v-if="cellParts.length">, </template>
            </span>

            <template v-for="part in cellParts" :key="part.key">
                <span :class="part.class">
                    {{ part.text }}
                </span>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    user: { type: Object, required: true },
    day: { type: Object, required: true },
    classes: { type: Array, default: () => [] },
})

const page = usePage()

/**
 * Vacation types: als Konstante statt ref (keine Reaktivität nötig)
 */
const vacationTypes = [
    { name: 'Verfügbar', type: 'AVAILABLE' },
    { name: 'Arbeitsfreier Tag', type: 'OFF_WORK' },
    { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE' },
    { name: 'Frei', type: 'FREE_WORK' },
]

const vacationTypeMap = computed(() => {
    const map = Object.create(null)
    for (const v of vacationTypes) map[v.type] = v.name
    return map
})

/** Urlaub am Tag nur 1x ermitteln */
const vacationToday = computed(() => {
    const list = props.user?.vacations ?? []
    return list.find(v => v?.date === props.day.withoutFormat) ?? null
})

const isOnVacation = computed(() => !!vacationToday.value)

const vacationLabel = computed(() => {
    const v = vacationToday.value
    if (!v) return 'On Vacation'
    return vacationTypeMap.value?.[v.type] || 'On Vacation'
})

/** Prüft ob die Vacation vom User der Zelle selbst eingetragen wurde */
const vacationIsItalic = computed(() => {
    const v = vacationToday.value
    if (!v) return false
    const cellUserId = props.user?.element?.id
    // type 0 = User, bei Freelancern/ServiceProvidern ist created_by nie gleich element.id
    return v.created_by != null && cellUserId != null && v.created_by == cellUserId && props.user?.type === 0
})

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

/** Alle Schichten am Tag (einmal filtern, dann überall verwenden) */
const shiftsToday = computed(() => {
    const list = props.user?.element?.shifts ?? []
    const dayA = props.day.fullDay
    const dayB = props.day.withoutFormat

    return list.filter(s => {
        const start = s?.start_of_shift
        const byStart = start === dayA || start === dayB

        const days = s?.days_of_shift
        const byDays =
            Array.isArray(days) && (days.includes(dayA) || days.includes(dayB))

        return byStart || byDays
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
                ? ` [${s.craftAbbreviationUser}]`
                : ''

        // Determine time display for multi-day shifts
        let timeDisplay = `${s.startPivot} - ${s.endPivot}`
        const daysOfShift = s?.days_of_shift
        if (Array.isArray(daysOfShift) && daysOfShift.length > 1 && s.startPivot && s.endPivot) {
            const currentDayFormatted = props.day.fullDay // Format: d.m.Y
            const firstDay = daysOfShift[0]
            const lastDay = daysOfShift[daysOfShift.length - 1]

            if (currentDayFormatted === firstDay) {
                // First day: show startPivot - 00:00
                timeDisplay = `${s.startPivot} - 00:00`
            } else if (currentDayFormatted === lastDay) {
                // Last day: show 00:00 - endPivot
                timeDisplay = `00:00 - ${s.endPivot}`
            } else {
                // Middle day: show full day
                timeDisplay = '00:00 - 00:00'
            }
        }

        parts.push({
            key: `shift:${s.id}`,
            text: `${timeDisplay} ${s?.roomName ?? ''}${craftSuffix}, `,
            class: '',
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

    // Availabilities (nur Comments anzeigen, wie vorher)
    for (const a of availabilitiesToday.value) {
        if (!a?.comment) continue
        // Availabilities werden immer vom User selbst eingetragen → immer italic
        parts.push({
            key: `av:${a.id}`,
            text: `„${a.comment}" `,
            class: 'text-green-500 italic',
        })
    }

    return parts
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
