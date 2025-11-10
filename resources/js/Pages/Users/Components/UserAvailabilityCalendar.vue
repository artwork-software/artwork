<template>
    <div class="w-full">
        <!-- Headbar -->
        <div class="w-full mb-3">
            <div class="flex items-center justify-between rounded-2xl border border-zinc-200/70 bg-white/80 backdrop-blur px-3 py-2 sm:px-4 sm:py-3">
                <!-- Monatstitel -->
                <h2 class="text-lg sm:text-xl font-semibold tracking-tight text-zinc-900 select-none">
                    {{ dateToShow[0] }}
                </h2>

                <!-- Navigation -->
                <div class="flex items-center gap-1.5">
                    <button
                        class="ui-button"
                        @click="previousMonth"
                        aria-label="Previous month"
                    >
                        <ChevronLeftIcon class="h-5 w-5 text-blue-600" />
                    </button>
                    <button
                        class="ui-button"
                        @click="nextMonth"
                        aria-label="Next month"
                    >
                        <ChevronRightIcon class="h-5 w-5 text-blue-600" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid (Tabelle beibehalten für gleiche Funktion) -->
        <table class="w-full border-separate border-spacing-y-1">
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
                    <span class="inline-flex items-center rounded-xl bg-zinc-100 text-zinc-700 text-xs px-2 py-1">
                      KW {{ week.weekNumber }}
                    </span>
                </td>

                <!-- Tage -->
                <td v-for="day in week.days" :key="day.day_formatted" class="px-1 py-1">
                    <button
                        @click="showVacationsAndAvailabilities(day.day_formatted)"
                        class="w-full rounded-xl px-3 py-3 text-center select-none transition hover:bg-zinc-50"
                        :class="dayButtonClasses(day)"
                    >
                        {{ day.day }}
                    </button>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/solid'

// Props 1:1 beibehalten
const props = defineProps({
    calendarData: { type: Array, required: true },
    dateToShow: { type: Array, required: true }, // [Titel, { date: 'YYYY-MM-DD' }]
    showVacationsAndAvailabilitiesDate: { type: String, required: true },
})

// Wochentage (wie im Original, aber kompakt erzeugt)
const weekdayNames = computed(() => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])

// --- Logik: unverändert inhaltlich, nur als Functions ---
const previousMonth = () => {
    const currentMonth = new Date(props.dateToShow[1].date)
    router.reload({
        data: { month: subtractOneMonth(currentMonth) },
    })
}

const nextMonth = () => {
    const currentMonth = new Date(props.dateToShow[1].date)
    router.reload({
        data: { month: addOneMonth(currentMonth) },
    })
}

const showVacationsAndAvailabilities = (day) => {
    const currentMonth = new Date(props.dateToShow[1].date)
    const rightMonth = dayjs(currentMonth)
    router.reload({
        data: {
            showVacationsAndAvailabilities: day,
            vacationMonth: rightMonth.format('YYYY-MM-DD'),
        },
        preserveState: true,
    })
}

const addOneMonth = (dateObj) => dayjs(dateObj).add(1, 'month').format('YYYY-MM-DD')
const subtractOneMonth = (dateObj) => dayjs(dateObj).subtract(1, 'month').format('YYYY-MM-DD')

// Klassen für Tagesbutton: modern & mit Farbakzenten, kompatibel zu vorhandenem Datenmodell
const dayButtonClasses = (day) => {
    const selected = day.day_formatted === props.showVacationsAndAvailabilitiesDate
    const classes = ['text-sm']

    // Selected Day (oberste Priorität)
    if (selected) {
        classes.push('bg-blue-600 text-white hover:bg-blue-600/90 ring-1 ring-blue-600')
        return classes
    }

    // Today (nur wenn nicht ausgewählt)
    if (day.isToday) {
        classes.push('ring-1 ring-blue-400 text-blue-700 font-semibold')
    }

    // Optional bekannte Flags (falls vorhanden)
    if (day.notInMonth) {
        classes.push('text-zinc-400')
    } else if (day.onVacation) {
        classes.push('text-rose-600')
    } else if (day.hasAvailability) {
        classes.push('text-blue-600')
    } else {
        classes.push('text-zinc-700')
    }

    return classes
}
</script>
