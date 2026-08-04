<template>
    <div class="w-full">
        <!-- Headbar -->
        <div class="w-full mb-3">
            <div class="flex items-center justify-between rounded-2xl border border-border-subtle bg-white/80 backdrop-blur px-3 py-2 sm:px-4 sm:py-3">
                <!-- Monatstitel -->
                <h2 class="text-lg sm:text-xl font-semibold tracking-tight text-text select-none">
                    {{ dateToShow[0] }}
                </h2>

                <!-- Navigation -->
                <div class="flex items-center gap-1.5">
                    <BaseUIButton
                        hide-icon
                        @click="previousMonth"
                        :aria-label="$t('Previous month')"
                    >
                        <IconChevronLeft class="h-5 w-5 text-accent-600" />
                    </BaseUIButton>
                    <button
                        class="ui-button !px-2 text-sm text-accent-600"
                        @click="goToToday"
                    >
                        {{ $t('Today') }}
                    </button>
                    <BaseUIButton
                        hide-icon
                        @click="nextMonth"
                        :aria-label="$t('Next month')"
                    >
                        <IconChevronRight class="h-5 w-5 text-accent-600" />
                    </BaseUIButton>
                </div>
            </div>
        </div>

        <!-- Grid -->
        <table class="w-full border-separate border-spacing-y-1 select-none">
            <thead>
            <tr class="text-xs font-semibold text-text-muted">
                <th class="p-2 w-16"></th>
                <th v-for="name in weekdayNames" :key="name" class="p-2 text-center">
                    {{ $t(name) }}
                </th>
            </tr>
            </thead>

            <tbody>
            <tr v-for="week in calendarData" :key="week.weekNumber" class="align-middle">
                <!-- KW -->
                <td class="px-2 py-3 text-center">
                    <span class="inline-flex items-center rounded-xl bg-surface-sunken text-text-muted text-xs px-2 py-1">
                      KW {{ week.weekNumber }}
                    </span>
                </td>

                <!-- Tage -->
                <td v-for="day in week.days" :key="day.day_formatted" class="px-1 py-1">
                    <button
                        type="button"
                        :data-avail-day="day.notInMonth ? null : day.day_formatted"
                        class="relative w-full rounded-xl px-2 py-3 text-center text-sm transition"
                        :class="dayClasses(day)"
                        :style="dayStyle(day)"
                        :disabled="day.notInMonth || !interactive"
                        @pointerdown="onDayPointerDown($event, day)"
                        @click="onDayClick($event, day)"
                    >
                        {{ day.day }}
                        <span
                            v-if="day.hasConflict && !day.notInMonth"
                            class="absolute top-1 right-1 h-2 w-2 rounded-full bg-warning"
                        />
                        <!-- Projektwünsche: Streifen am unteren Zellrand, laufen über die Serie hinweg -->
                        <template v-if="!day.notInMonth">
                            <span
                                v-for="(wish, wishIndex) in wishesForDay(day).slice(0, 2)"
                                :key="`wish-${wish.id}`"
                                class="absolute h-[4px] pointer-events-none"
                                :class="[
                                    wish.date === wish.series_start ? 'rounded-l-full left-1' : 'left-0',
                                    wish.date === wish.series_end ? 'rounded-r-full right-1' : 'right-0',
                                ]"
                                :style="{ bottom: (2 + wishIndex * 6) + 'px', ...assignmentStripStyle(wish) }"
                                :title="assignmentLabel(wish, $t('Wish'))"
                            />
                        </template>
                    </button>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Legende -->
        <div class="flex items-center flex-wrap gap-x-4 gap-y-1 mt-2 px-2 text-xs text-text-subtle">
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-success-surface"></span>
                <span>{{ $t('Available') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-danger-surface"></span>
                <span>{{ $t('Absent') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded border-b-4 border-border-strong bg-transparent"></span>
                <span>{{ $t('Partial day') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-warning"></span>
                <span>{{ $t('Conflict with your shift!') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-[4px] rounded-full border border-success-border border-dashed bg-success-surface"></span>
                <span>{{ $t('Project wish') }}</span>
            </div>
        </div>
        <p v-if="interactive" class="mt-1.5 px-2 text-xs text-text-subtle">
            {{ $t('Click a day or drag across several days to create an entry.') }}
        </p>
    </div>
</template>

<script setup>
import {IconChevronLeft, IconChevronRight} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import { computed, ref, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { assignmentStripStyle, assignmentLabel } from '@/Composeables/UseProjectDayAssignments.js'

const props = defineProps({
    calendarData: { type: Array, required: true },
    dateToShow: { type: Array, required: true }, // [Titel, { date: 'YYYY-MM-DD' }]
    showVacationsAndAvailabilitiesDate: { type: String, default: '' },
    interactive: { type: Boolean, default: true },
    projectWishes: { type: Array, default: () => [] },
})

const emit = defineEmits(['select-range'])

const weekdayNames = computed(() => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])

// Nur die Verfügbarkeits-Props neu laden – der Einsatzplan bleibt unberührt
const AVAILABILITY_PROPS = ['calendarData', 'dateToShow', 'vacations', 'availabilities', 'createShowDate', 'projectWishes']

// Projektwünsche je Tag (Map Y-m-d => Einträge)
const wishesByDate = computed(() => {
    const map = new Map()
    for (const wish of props.projectWishes ?? []) {
        if (!map.has(wish.date)) map.set(wish.date, [])
        map.get(wish.date).push(wish)
    }
    return map
})

const wishesForDay = (day) => wishesByDate.value.get(day.day_formatted) ?? []

const currentMonth = computed(() => dayjs(props.dateToShow[1]?.date ?? props.dateToShow[1]))

const reloadMonth = (month) => {
    router.reload({
        data: { month: month.startOf('month').format('YYYY-MM-DD') },
        only: AVAILABILITY_PROPS,
        preserveState: true,
        preserveScroll: true,
    })
}

const previousMonth = () => reloadMonth(currentMonth.value.subtract(1, 'month'))
const nextMonth = () => reloadMonth(currentMonth.value.add(1, 'month'))
const goToToday = () => reloadMonth(dayjs())

// --- Drag-/Klick-Auswahl ---------------------------------------------------
const dragStart = ref(null)
const dragEnd = ref(null)
const isDragging = ref(false)

const selectionRange = computed(() => {
    if (!dragStart.value || !dragEnd.value) return null
    const [start, end] = [dragStart.value, dragEnd.value].sort()
    return { start, end }
})

const inSelection = (day) => {
    const range = selectionRange.value
    return range && day.day_formatted >= range.start && day.day_formatted <= range.end
}

const onDayPointerDown = (event, day) => {
    if (day.notInMonth || !props.interactive) return
    event.preventDefault()
    dragStart.value = day.day_formatted
    dragEnd.value = day.day_formatted
    isDragging.value = true
    window.addEventListener('pointermove', onPointerMove)
    window.addEventListener('pointerup', onPointerUp)
}

const onPointerMove = (event) => {
    if (!isDragging.value) return
    const element = document.elementFromPoint(event.clientX, event.clientY)
    const dayElement = element?.closest?.('[data-avail-day]')
    const date = dayElement?.getAttribute('data-avail-day')
    if (date) {
        dragEnd.value = date
    }
}

const onPointerUp = () => {
    cleanupDragListeners()
    if (!isDragging.value) return
    isDragging.value = false
    const range = selectionRange.value
    dragStart.value = null
    dragEnd.value = null
    if (range) {
        emit('select-range', range)
    }
}

const cleanupDragListeners = () => {
    window.removeEventListener('pointermove', onPointerMove)
    window.removeEventListener('pointerup', onPointerUp)
}

// Tastatur-Aktivierung (Enter/Space löst click ohne vorheriges pointerdown aus)
const onDayClick = (event, day) => {
    if (day.notInMonth || !props.interactive || event.detail !== 0) return
    emit('select-range', { start: day.day_formatted, end: day.day_formatted })
}

onBeforeUnmount(cleanupDragListeners)

// --- Darstellung -----------------------------------------------------------
const dayClasses = (day) => {
    const classes = []

    if (day.notInMonth) {
        classes.push('text-text-subtle cursor-default')
        return classes
    }

    if (props.interactive) {
        classes.push('cursor-pointer')
    }

    if (inSelection(day)) {
        classes.push('ring-2 ring-accent-500 bg-accent-100 text-accent-900')
        return classes
    }

    if (day.isToday) {
        classes.push('ring-1 ring-accent-500 font-semibold')
    }

    const bothTypes = day.onVacation && day.hasAvailability
    if (bothTypes) {
        // Split-Hintergrund kommt aus dayStyle()
        classes.push('text-text')
    } else if (day.onVacation) {
        classes.push(
            day.vacationFullDay
                ? 'bg-danger-surface text-danger'
                : 'border-b-4 !rounded-b-none border-danger text-danger'
        )
    } else if (day.hasAvailability) {
        classes.push(
            day.availabilityFullDay
                ? 'bg-success-surface text-success'
                : 'border-b-4 !rounded-b-none border-success text-success'
        )
    } else {
        classes.push('text-text-muted hover:bg-surface-sunken')
    }

    return classes
}

const dayStyle = (day) => {
    if (day.notInMonth || inSelection(day) || !(day.onVacation && day.hasAvailability)) {
        return null
    }
    // Tag mit Verfügbarkeit UND Abwesenheit: zweifarbig geteilt
    return {
        background: 'linear-gradient(135deg, rgb(209 250 229) 0 50%, rgb(255 228 230) 50% 100%)',
    }
}
</script>
