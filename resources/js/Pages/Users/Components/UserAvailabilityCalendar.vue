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

        <!-- Legende -->
        <div class="flex items-center gap-4 mt-2 px-2 text-xs text-zinc-500">
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded-full ring-2 ring-zinc-700"></span>
                <span>{{ $t('Availability') }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-4 h-4 rounded-none ring-2 ring-zinc-700"></span>
                <span>{{ $t('Absence') }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/solid'

const page = usePage()

// Props 1:1 beibehalten
const props = defineProps({
    calendarData: { type: Array, required: true },
    dateToShow: { type: Array, required: true }, // [Titel, { date: 'YYYY-MM-DD' }]
    showVacationsAndAvailabilitiesDate: { type: String, required: true },
})

// Wochentage (wie im Original, aber kompakt erzeugt)
const weekdayNames = computed(() => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])

// Patch date range to server (same approach as UserShiftPlan)
function patchServerDateRange(startDateStr, endDateStr) {
    const userId = page.props?.auth?.user?.id
    if (!userId) return

    router.patch(
        route('update.user.worker.shift-plan.filters.update', userId),
        { start_date: startDateStr, end_date: endDateStr },
        { preserveState: true, preserveScroll: true }
    )
}

function setRangeToMonth(month) {
    // month is a dayjs object
    const start = month.startOf('month').format('YYYY-MM-DD')
    const end = month.endOf('month').format('YYYY-MM-DD')
    patchServerDateRange(start, end)
}

const previousMonth = () => {
    const current = dayjs(props.dateToShow[1].date)
    setRangeToMonth(current.subtract(1, 'month'))
}

const nextMonth = () => {
    const current = dayjs(props.dateToShow[1].date)
    setRangeToMonth(current.add(1, 'month'))
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
    } else if (day.onVacation && day.hasAvailability) {
        // Both present: square border (vacation takes priority) with thicker ring
        classes.push('text-zinc-700 ring-[3px] ring-zinc-700 !rounded-none')
    } else if (day.onVacation) {
        // Square border for vacation/absence
        classes.push('text-zinc-700 ring-2 ring-zinc-700 !rounded-none')
    } else if (day.hasAvailability) {
        // Round border for availability
        classes.push('text-zinc-700 ring-2 ring-zinc-700 !rounded-full')
    } else {
        classes.push('text-zinc-700')
    }

    return classes
}
</script>
