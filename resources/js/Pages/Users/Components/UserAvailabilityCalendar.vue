<template>
    <div class="w-full">
        <!-- Headbar -->
        <div class="w-full mb-3">
            <div class="flex items-center justify-between rounded-2xl border border-zinc-200/70 dark:border-zinc-700 bg-white/80 dark:bg-zinc-800/80 backdrop-blur px-3 py-2 sm:px-4 sm:py-3">
                <!-- Monatstitel -->
                <h2 class="text-lg sm:text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100 select-none">
                    {{ dateToShow[0] }}
                </h2>

                <!-- Navigation -->
                <div class="flex items-center gap-1.5">
                    <button
                        class="ui-button"
                        @click="previousMonth"
                        :aria-label="$t('Previous month')"
                    >
                        <ChevronLeftIcon class="h-5 w-5 text-blue-600" />
                    </button>
                    <button
                        class="ui-button !px-2 text-sm text-blue-600"
                        @click="goToToday"
                    >
                        {{ $t('Today') }}
                    </button>
                    <button
                        class="ui-button"
                        @click="nextMonth"
                        :aria-label="$t('Next month')"
                    >
                        <ChevronRightIcon class="h-5 w-5 text-blue-600" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid -->
        <table class="w-full border-separate border-spacing-y-1 select-none">
            <thead>
            <tr class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
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
                    <span class="inline-flex items-center rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs px-2 py-1">
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
                            class="absolute top-1 right-1 h-2 w-2 rounded-full bg-amber-500"
                        />
                    </button>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Legende -->
        <div class="flex items-center flex-wrap gap-x-4 gap-y-1 mt-2 px-2 text-xs text-zinc-500 dark:text-zinc-400">
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-emerald-200 dark:bg-emerald-800"></span>
                <span>{{ $t('Available') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded bg-rose-200 dark:bg-rose-900"></span>
                <span>{{ $t('Absent') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded border-b-4 border-zinc-400 bg-transparent"></span>
                <span>{{ $t('Partial day') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                <span>{{ $t('Conflict with your shift!') }}</span>
            </div>
        </div>
        <p v-if="interactive" class="mt-1.5 px-2 text-xs text-zinc-400 dark:text-zinc-500">
            {{ $t('Click a day or drag across several days to create an entry.') }}
        </p>
    </div>
</template>

<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/solid'

const props = defineProps({
    calendarData: { type: Array, required: true },
    dateToShow: { type: Array, required: true }, // [Titel, { date: 'YYYY-MM-DD' }]
    showVacationsAndAvailabilitiesDate: { type: String, default: '' },
    interactive: { type: Boolean, default: true },
})

const emit = defineEmits(['select-range'])

const weekdayNames = computed(() => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])

// Nur die Verfügbarkeits-Props neu laden – der Einsatzplan bleibt unberührt
const AVAILABILITY_PROPS = ['calendarData', 'dateToShow', 'vacations', 'availabilities', 'createShowDate']

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
        classes.push('text-zinc-300 dark:text-zinc-600 cursor-default')
        return classes
    }

    if (props.interactive) {
        classes.push('cursor-pointer')
    }

    if (inSelection(day)) {
        classes.push('ring-2 ring-blue-500 bg-blue-100 text-blue-900 dark:bg-blue-900/50 dark:text-blue-100')
        return classes
    }

    if (day.isToday) {
        classes.push('ring-1 ring-blue-500 font-semibold')
    }

    const bothTypes = day.onVacation && day.hasAvailability
    if (bothTypes) {
        // Split-Hintergrund kommt aus dayStyle()
        classes.push('text-zinc-800 dark:text-zinc-100')
    } else if (day.onVacation) {
        classes.push(
            day.vacationFullDay
                ? 'bg-rose-100 text-rose-900 dark:bg-rose-900/40 dark:text-rose-200'
                : 'border-b-4 !rounded-b-none border-rose-500 text-rose-800 dark:text-rose-300'
        )
    } else if (day.hasAvailability) {
        classes.push(
            day.availabilityFullDay
                ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200'
                : 'border-b-4 !rounded-b-none border-emerald-500 text-emerald-800 dark:text-emerald-300'
        )
    } else {
        classes.push('text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700/60')
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
