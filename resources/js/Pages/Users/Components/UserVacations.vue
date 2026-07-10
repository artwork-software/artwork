<template>
    <div class="my-5">
        <!-- Kopf mit Neuer-Eintrag-Button -->
        <div v-if="canManage" class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200">
                {{ $t('Availability & absence') }}
            </h3>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-artwork-buttons-create px-3 py-1.5 text-sm font-medium text-white hover:bg-artwork-buttons-hover transition"
                @click="$emit('create')"
            >
                <PlusIcon class="h-4 w-4" />
                {{ $t('New entry') }}
            </button>
        </div>

        <!-- Abwesenheiten -->
        <div v-if="vacationEntries.length > 0" class="mb-6">
            <h4 class="mb-2 text-sm font-semibold text-zinc-600 dark:text-zinc-400">
                {{ $t('Absences') }}
            </h4>
            <SingleUserVacation
                v-for="entry in vacationEntries"
                :key="entryKey(entry)"
                :entry="entry"
                :user="user"
                :type="type"
            />
        </div>

        <!-- Verfügbarkeiten -->
        <div v-if="availabilityEntries.length > 0" class="mb-6">
            <h4 class="mb-2 text-sm font-semibold text-zinc-600 dark:text-zinc-400">
                {{ $t('Registered availability') }}
            </h4>
            <SingleUserVacation
                v-for="entry in availabilityEntries"
                :key="entryKey(entry)"
                :entry="entry"
                :user="user"
                :type="type"
            />
        </div>

        <!-- Empty state -->
        <div v-if="hasNoEntries" class="mb-6 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 p-4">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $t('No entry has yet been made for this month.') }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { PlusIcon } from '@heroicons/vue/outline'
import dayjs from 'dayjs'
import SingleUserVacation from '@/Pages/Users/Components/SingleUserVacation.vue'
import { can, is } from 'laravel-permission-to-vuejs'

const props = defineProps({
    user: { type: Object, required: true },
    vacations: { type: Array, default: () => [] },
    type: { type: String, default: '' },
    availabilities: { type: Array, default: () => [] },
    showVacationsAndAvailabilitiesDate: { type: String, default: '' },
})

defineEmits(['create'])

const page = usePage()

const canManage = computed(() =>
    can('can manage workers') ||
    is('artwork admin') ||
    (props.type !== 'freelancer' && props.user?.id === page.props?.auth?.user?.id) ||
    can('can manage availability')
)

const toDateString = (value) => {
    const date = dayjs(value)
    return date.isValid() ? date.format('YYYY-MM-DD') : ''
}

/**
 * Gruppiert die tagbasierten Zeilen zu Anzeige-Einträgen:
 * - ohne Serie -> einzelner Tag (kind: single)
 * - tägliche Serie -> ein Zeitraum-Eintrag (kind: range)
 * - wöchentliche Serie -> ein Wiederholungs-Eintrag (kind: weekly)
 * series_start_date/series_end_date kommen aus dem Backend über alle Serientage
 * (auch außerhalb des angezeigten Monats).
 */
const buildEntries = (rows, dataType) => {
    const singles = []
    const seriesGroups = new Map()

    for (const row of rows || []) {
        if (row.series_id && row.series) {
            if (!seriesGroups.has(row.series_id)) {
                seriesGroups.set(row.series_id, [])
            }
            seriesGroups.get(row.series_id).push(row)
        } else {
            singles.push(row)
        }
    }

    const entries = singles.map((row) => ({
        kind: 'single',
        type: dataType,
        id: row.id,
        seriesId: null,
        startDate: toDateString(row.date),
        endDate: toDateString(row.date),
        dateCasted: row.date_casted,
        fullDay: !!row.full_day,
        startTime: row.start_time,
        endTime: row.end_time,
        comment: row.comment,
        hasConflicts: !!row.has_conflicts,
        conflicts: row.conflicts || [],
    }))

    for (const rowsOfSeries of seriesGroups.values()) {
        rowsOfSeries.sort((a, b) => toDateString(a.date).localeCompare(toDateString(b.date)))
        const first = rowsOfSeries[0]
        const frequency = first.series?.frequency
        const kind = frequency === 'weekly' ? 'weekly' : 'range'

        const rowDates = rowsOfSeries.map((r) => toDateString(r.date))
        const startDate = toDateString(first.series_start_date) || rowDates[0]
        const endDate = toDateString(first.series_end_date) || rowDates[rowDates.length - 1]

        entries.push({
            kind,
            type: dataType,
            id: first.id,
            seriesId: first.series_id,
            startDate,
            endDate,
            seriesEndDate: toDateString(first.series?.end_date) || endDate,
            dateCasted: first.date_casted,
            fullDay: !!first.full_day,
            startTime: first.start_time,
            endTime: first.end_time,
            comment: first.comment,
            hasConflicts: rowsOfSeries.some((r) => r.has_conflicts),
            conflicts: rowsOfSeries.flatMap((r) => r.conflicts || []),
        })
    }

    entries.sort((a, b) => a.startDate.localeCompare(b.startDate))
    return entries
}

const vacationEntries = computed(() => buildEntries(props.vacations, 'vacation'))
const availabilityEntries = computed(() => buildEntries(props.availabilities, 'available'))

const hasNoEntries = computed(
    () => vacationEntries.value.length === 0 && availabilityEntries.value.length === 0
)

const entryKey = (entry) => `${entry.type}-${entry.seriesId ?? `single-${entry.id}`}`
</script>
