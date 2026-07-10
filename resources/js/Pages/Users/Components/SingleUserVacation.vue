<template>
    <div
        class="rounded-lg border text-sm mb-2 overflow-hidden"
        :class="entry.hasConflicts
            ? 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-700'
            : 'bg-white border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700'"
    >
        <div class="flex items-stretch">
            <!-- Farbbalken nach Typ -->
            <div class="w-1.5 shrink-0" :class="entry.type === 'available' ? 'bg-emerald-500' : 'bg-rose-500'"></div>

            <div class="flex-1 py-3 px-3 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-semibold" :class="entry.type === 'available'
                        ? 'text-emerald-800 dark:text-emerald-300'
                        : 'text-rose-800 dark:text-rose-300'">
                        {{ entry.type === 'available' ? $t('Available') : $t('Absent') }}
                    </span>
                    <IconRepeat v-if="entry.kind === 'weekly'" class="h-4 w-4 text-zinc-500" />
                    <span
                        v-if="entry.hasConflicts"
                        class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:text-amber-200"
                    >
                        <IconAlertTriangle class="h-3.5 w-3.5" />
                        {{ $t('Conflict with your shift!') }}
                    </span>
                </div>

                <div class="mt-0.5 text-zinc-700 dark:text-zinc-300">
                    {{ dateLabel }}
                    <span v-if="!entry.fullDay">, {{ entry.startTime }} - {{ entry.endTime }}</span>
                </div>

                <p v-if="entry.comment" class="mt-1 text-zinc-500 dark:text-zinc-400 truncate">
                    &bdquo;{{ translatedComment }}&rdquo;
                </p>

                <!-- Konflikt-Details -->
                <div v-if="entry.hasConflicts" class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 space-y-0.5">
                    <p v-for="conflict in entry.conflicts" :key="conflictKey(conflict)">
                        {{ $t('{username} has scheduled you on {date} {start} - {end}, contrary to your original entry.', { username: conflict.user_name, date: conflict.date_casted, start: conflict.start_time, end: conflict.end_time }) }}
                    </p>
                </div>
            </div>

            <!-- Aktionen: immer sichtbar -->
            <div v-if="canManage" class="flex flex-col justify-center gap-1 pr-2">
                <button
                    type="button"
                    class="ui-button"
                    :aria-label="$t('Edit')"
                    @click="showEditModal = true"
                >
                    <IconEdit class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    class="ui-button"
                    :aria-label="$t('Delete')"
                    @click="showDeleteConfirmModal = true"
                >
                    <IconTrash class="w-4 h-4 text-red-500" />
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <AddEditVacationsModal
        v-if="showEditModal"
        :edit-entry="entry"
        :user="user"
        :type="type"
        @closed="showEditModal = false"
    />

    <!-- Delete Confirm -->
    <ConfirmDeleteModal
        v-if="showDeleteConfirmModal"
        :title="$t('Delete entry')"
        :button="$t('Delete')"
        :description="deleteDescription"
        :is-series-delete="false"
        :is_budget="false"
        @closed="showDeleteConfirmModal = false"
        @delete="deleteEntry"
    />
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import AddEditVacationsModal from '@/Pages/Users/Components/AddEditVacationsModal.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import { can, is } from 'laravel-permission-to-vuejs'
import { IconAlertTriangle, IconEdit, IconRepeat, IconTrash } from '@tabler/icons-vue'

const props = defineProps({
    // Gruppierter Eintrag aus UserVacations (kind: single | range | weekly)
    entry: { type: Object, required: true },
    user: { type: Object, required: true },
    type: { type: String, default: '' },
})

const showEditModal = ref(false)
const showDeleteConfirmModal = ref(false)

const { proxy } = getCurrentInstance()
const page = usePage()

const canManage = computed(() =>
    can('can manage workers') ||
    is('artwork admin') ||
    (props.type !== 'freelancer' && props.user?.id === page.props?.auth?.user?.id) ||
    can('can manage availability')
)

const formatDate = (value) => {
    if (!value) return '-'
    const date = dayjs(value)
    return date.isValid() ? date.format('DD.MM.YYYY') : value
}

const WEEKDAY_KEYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const dateLabel = computed(() => {
    if (props.entry.kind === 'range') {
        return `${formatDate(props.entry.startDate)} – ${formatDate(props.entry.endDate)}`
    }
    if (props.entry.kind === 'weekly') {
        const weekday = proxy.$t(WEEKDAY_KEYS[dayjs(props.entry.startDate).day()])
        return `${proxy.$t('Every')} ${weekday}, ${proxy.$t('until')} ${formatDate(props.entry.seriesEndDate)}`
    }
    return props.entry.dateCasted || formatDate(props.entry.startDate)
})

const SYSTEM_COMMENTS = ['OFF_WORK', 'NOT_AVAILABLE', 'FREE_WORK']
const translatedComment = computed(() =>
    SYSTEM_COMMENTS.includes(props.entry.comment) ? proxy.$t(props.entry.comment) : props.entry.comment
)

const deleteDescription = computed(() => {
    if (props.entry.kind === 'range') {
        return proxy.$t('The entire period will be deleted. Do you want to continue?')
    }
    if (props.entry.kind === 'weekly') {
        return proxy.$t('The entire series will be deleted. Do you want to continue?')
    }
    return proxy.$t('Do you really want to delete this entry?')
})

const conflictKey = (c) => `${c?.date_casted}-${c?.start_time}-${c?.end_time}-${c?.user_name}`

const deleteEntry = () => {
    const isSeries = props.entry.kind !== 'single' && props.entry.seriesId
    let routeName
    let routeParam
    if (isSeries) {
        routeName = props.entry.type === 'available' ? 'delete.availability.series' : 'delete.vacation.series'
        routeParam = props.entry.seriesId
    } else {
        routeName = props.entry.type === 'available' ? 'delete.availability' : 'delete.vacation'
        routeParam = props.entry.id
    }

    router.delete(route(routeName, routeParam), {
        preserveScroll: true,
        preserveState: true,
        // Nur die Verfügbarkeits-Props neu laden – der Einsatzplan bleibt unangetastet
        only: [
            'calendarData',
            'dateToShow',
            'vacations',
            'availabilities',
            'createShowDate',
            'user_to_edit_whole_week_date_period_vacations',
            'freelancer_to_edit_whole_week_date_period_vacations',
        ],
        onFinish: () => {
            showDeleteConfirmModal.value = false
        },
    })
}
</script>
