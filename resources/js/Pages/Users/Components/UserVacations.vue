<template>
    <div class="my-5">
        <!-- Kopf mit Neuer-Eintrag-Button -->
        <div v-if="canManage" class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-text ">
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
            <h4 class="mb-2 text-sm font-semibold text-text-muted dark:text-text-subtle">
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
            <h4 class="mb-2 text-sm font-semibold text-text-muted dark:text-text-subtle">
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

        <!-- Projektwünsche -->
        <div v-if="wishEntries.length > 0" class="mb-6">
            <h4 class="mb-2 text-sm font-semibold text-text-muted dark:text-text-subtle italic">
                {{ $t('Project wishes') }}
            </h4>
            <div
                v-for="entry in wishEntries"
                :key="`wish-${entry.groupId}`"
                class="mb-2 flex items-center justify-between gap-2 rounded-lg border border-dashed border-success-border bg-success-surface px-3 py-2"
            >
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span
                            class="inline-block size-2 shrink-0 rounded-full"
                            :style="{ backgroundColor: colorForProjectId(entry.projectId) }"
                        />
                        <span class="truncate text-sm italic text-text ">{{ entry.projectName }}</span>
                    </div>
                    <div class="text-xs text-text-subtle dark:text-text-subtle mt-0.5">
                        <template v-if="entry.isFullPeriod || entry.dates.length > 3">
                            {{ formatAssignmentDate(entry.startDate) }} - {{ formatAssignmentDate(entry.endDate) }}
                            <template v-if="entry.isFullPeriod"> &middot; {{ $t('Entire project period') }}</template>
                        </template>
                        <template v-else>
                            {{ entry.dates.map(formatAssignmentDate).join(', ') }}
                        </template>
                    </div>
                </div>
                <button
                    v-if="canManage"
                    type="button"
                    class="shrink-0 rounded-md p-1.5 text-text-subtle hover:text-danger hover:bg-danger-surface transition"
                    :title="$t('Remove entire wish')"
                    @click="deleteWish(entry)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="hasNoEntries" class="mb-6 rounded-lg border border-dashed border-border p-4">
            <p class="text-sm text-text-subtle dark:text-text-subtle">
                {{ $t('No entry has yet been made for this month.') }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { PlusIcon } from '@heroicons/vue/outline'
import dayjs from 'dayjs'
import axios from 'axios'
import SingleUserVacation from '@/Pages/Users/Components/SingleUserVacation.vue'
import { can, is } from 'laravel-permission-to-vuejs'
import { colorForProjectId, formatAssignmentDate } from '@/Composeables/UseProjectDayAssignments.js'

const props = defineProps({
    user: { type: Object, required: true },
    vacations: { type: Array, default: () => [] },
    type: { type: String, default: '' },
    availabilities: { type: Array, default: () => [] },
    showVacationsAndAvailabilitiesDate: { type: String, default: '' },
    projectWishes: { type: Array, default: () => [] },
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

/** Projektwünsche: ein Eintrag pro Anlage-Vorgang (group_id), Zeitraum aus den Serien-Grenzen */
const wishEntries = computed(() => {
    const byGroup = new Map()
    for (const wish of props.projectWishes ?? []) {
        if (!byGroup.has(wish.group_id)) {
            byGroup.set(wish.group_id, {
                groupId: wish.group_id,
                id: wish.id,
                projectId: wish.project_id,
                projectName: wish.project_name,
                isFullPeriod: !!wish.is_full_period,
                startDate: wish.series_start,
                endDate: wish.series_end,
                dates: [],
            })
        }
        byGroup.get(wish.group_id).dates.push(wish.date)
    }
    return [...byGroup.values()].sort((a, b) => a.startDate.localeCompare(b.startDate))
})

const deleteWish = async (entry) => {
    try {
        await axios.delete(route('project-day-assignments.destroy', { projectDayAssignment: entry.id }), {
            params: { whole_group: true },
        })
    } catch (e) {
        console.error('Deleting project wish failed', e)
    } finally {
        // Reload auch im Fehlerfall: zeigt den tatsächlichen Serverstand statt
        // eines stummen, evtl. veralteten Eintrags
        router.reload({ only: ['projectWishes'] })
    }
}

const hasNoEntries = computed(
    () => vacationEntries.value.length === 0
        && availabilityEntries.value.length === 0
        && wishEntries.value.length === 0
)

const entryKey = (entry) => `${entry.type}-${entry.seriesId ?? `single-${entry.id}`}`
</script>
