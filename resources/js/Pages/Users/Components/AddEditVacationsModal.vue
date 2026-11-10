<template>
    <ArtworkBaseModal
        :title="isEdit ? 'Edit entry' : 'Create entry'"
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <form id="addEditVacationForm" class="space-y-4" @submit.prevent="save(false)">
            <!-- Typ-Auswahl -->
            <div class="grid grid-cols-2 gap-1 rounded-lg bg-surface-sunken p-1 text-sm font-medium" role="radiogroup" :aria-label="$t('Type')">
                <button
                    v-for="option in typeOptions"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="entry.type === option.value"
                    class="inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 transition-colors duration-150 motion-reduce:transition-none"
                    :class="entry.type === option.value ? 'bg-surface text-text shadow-raised' : 'text-text-muted hover:text-text'"
                    @click="entry.type = option.value"
                >
                    <span class="size-2 rounded-full" :class="option.dotClass" aria-hidden="true" />
                    {{ $t(option.label) }}
                </button>
            </div>

            <!-- Zeitraum -->
            <div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <BaseInput id="entry_start_date" v-model="entry.start_date" type="date" label="From" required :error="errors.start_date" />
                    <BaseInput id="entry_end_date" v-model="entry.end_date" type="date" label="To" required :error="errors.end_date" />
                </div>
                <p v-if="isRange" class="mt-1 text-xs text-text-muted">
                    {{ $t('One entry is created for each day in the period.') }}
                </p>
            </div>

            <!-- Hinweis: Abwesenheit löst bestehende Projektwünsche auf -->
            <BaseAlertComponent
                v-if="entry.type === 'vacation' && conflictingWishDates.length"
                type="warning"
                :message="$t('There are project wishes on {0}. They will be dissolved by this absence.', [conflictingWishDates.join(', ')])"
            />

            <!-- Ganztägig -->
            <BaseCheckbox id="entry_full_day" v-model="entry.full_day" :label="$t('All day')" />

            <!-- Zeiten -->
            <div v-if="!entry.full_day" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <BaseInput id="entry_start_time" v-model="entry.start_time" type="time" label="Start time" required :error="errors.start_time" />
                <BaseInput id="entry_end_time" v-model="entry.end_time" type="time" label="End time" required :error="errors.end_time" />
            </div>

            <!-- Wöchentliche Wiederholung (nur bei Einzeltag sinnvoll) -->
            <div v-if="!isRange" class="space-y-3">
                <BaseCheckbox id="entry_repeat" v-model="entry.repeat_weekly" :label="$t('Repeat weekly')" />
                <BaseInput
                    v-if="entry.repeat_weekly"
                    id="entry_repeat_until"
                    v-model="entry.repeat_until"
                    type="date"
                    label="Ends on"
                    required
                    :min="entry.start_date || null"
                    :error="errors.repeat_until"
                />
            </div>

            <!-- Notiz -->
            <BaseInput
                id="entry_comment"
                v-model="entry.comment"
                label="Note (optional)"
                maxlength="100"
                :error="errors.comment"
            />
        </form>

        <template #footer>
            <BaseUIButton
                v-if="!isEdit"
                class="mr-auto"
                label="Save & make further entries"
                variant="ghost"
                hide-icon
                :disabled="submitting"
                @click="save(true)"
            />
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="submitting"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="addEditVacationForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="submitting"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch, getCurrentInstance } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import { followStartDate } from '@/Helper/followStartDate.js'

const props = defineProps({
    user: { type: Object, required: true },
    type: { type: String, default: '' }, // 'user' | 'freelancer'
    // Gruppierter Eintrag aus UserVacations (kind single|range|weekly) – null bei Neuanlage
    editEntry: { type: Object, default: null },
    // Vorbelegung bei Neuanlage (Klick/Drag im Kalender)
    initialStart: { type: String, default: '' },
    initialEnd: { type: String, default: '' },
    // Projektwünsche des Monats – für den Hinweis, dass eine Abwesenheit sie auflöst
    projectWishes: { type: Array, default: () => [] },
})

defineOptions({ name: 'AddEditVacationsModal' })

const emit = defineEmits(['closed'])
const { proxy } = getCurrentInstance()

const isEdit = computed(() => !!props.editEntry)

const entry = reactive({
    type: props.editEntry?.type === 'available' ? 'available' : (props.editEntry ? 'vacation' : 'available'),
    start_date: props.editEntry?.startDate ?? props.initialStart ?? '',
    end_date: props.editEntry?.kind === 'weekly'
        ? (props.editEntry?.startDate ?? '')
        : (props.editEntry?.endDate ?? (props.initialEnd || props.initialStart || '')),
    full_day: props.editEntry ? !!props.editEntry.fullDay : true,
    start_time: props.editEntry?.startTime ?? null,
    end_time: props.editEntry?.endTime ?? null,
    repeat_weekly: props.editEntry?.kind === 'weekly',
    repeat_until: props.editEntry?.kind === 'weekly' ? (props.editEntry?.seriesEndDate ?? null) : null,
    comment: props.editEntry?.comment ?? null,
})

const typeOptions = [
    { value: 'available', label: 'Available', dotClass: 'bg-success' },
    { value: 'vacation', label: 'Absent', dotClass: 'bg-danger' },
]

const isRange = computed(() => !!entry.start_date && !!entry.end_date && entry.end_date > entry.start_date)

/** Wunsch-Tage im gewählten Zeitraum (Abwesenheit würde sie auflösen) — DD.MM.YYYY */
const conflictingWishDates = computed(() => {
    if (!entry.start_date) return []
    const rangeEnd = entry.end_date || entry.start_date
    return (props.projectWishes ?? [])
        .filter(wish => wish.date >= entry.start_date && wish.date <= rangeEnd)
        .map(wish => wish.date)
        .filter((value, index, arr) => arr.indexOf(value) === index)
        // erst auf ISO sortieren, dann formatieren — DD.MM.YYYY sortiert über
        // Monatsgrenzen sonst falsch
        .sort()
        .map(date => date.split('-').reverse().join('.'))
})

// „Bis" folgt „Von", solange kein eigener Zeitraum gewählt ist
watch(() => entry.start_date, (newStart, oldStart) => {
    entry.end_date = followStartDate(newStart, oldStart, entry.end_date)
})

// Zeitraum und wöchentliche Wiederholung schließen sich aus
watch(isRange, (value) => {
    if (value) {
        entry.repeat_weekly = false
        entry.repeat_until = null
    }
})

const errors = reactive({
    start_date: '',
    end_date: '',
    start_time: '',
    end_time: '',
    repeat_until: '',
    comment: '',
})

const validate = () => {
    errors.start_date = !entry.start_date ? proxy.$t('Please select a date.') : ''
    errors.end_date = !entry.end_date
        ? proxy.$t('Please select a date.')
        : (entry.end_date < entry.start_date ? proxy.$t('The end date must not be before the start date.') : '')

    errors.start_time = ''
    errors.end_time = ''
    if (!entry.full_day) {
        if (!entry.start_time) {
            errors.start_time = proxy.$t('Please select a start time.')
        }
        if (!entry.end_time) {
            errors.end_time = proxy.$t('Please select an end time.')
        }
        if (entry.start_time && entry.end_time) {
            if (entry.start_time > entry.end_time) {
                errors.start_time = proxy.$t('The start time must be before the end time.')
            } else if (entry.start_time === entry.end_time) {
                errors.start_time = proxy.$t('The start time and the end time must not be the same.')
            }
        }
    }

    errors.repeat_until = entry.repeat_weekly && !isRange.value && !entry.repeat_until
        ? proxy.$t('Please select an end date.')
        : ''
    if (!errors.repeat_until && entry.repeat_weekly && entry.repeat_until && entry.repeat_until <= entry.start_date) {
        errors.repeat_until = proxy.$t('The end date must be after the start date.')
    }

    errors.comment = entry.comment && entry.comment.length > 100
        ? proxy.$t('The comment must not be longer than 100 characters.')
        : ''

    return !Object.values(errors).some(Boolean)
}

const submitting = ref(false)

// Nach dem Speichern nur die Verfügbarkeits-Props neu laden – der (große) Einsatzplan
// bleibt unangetastet, das Backend überspringt dessen Berechnung (lazy Props)
const AVAILABILITY_PROPS = [
    'calendarData',
    'dateToShow',
    'vacations',
    'availabilities',
    'createShowDate',
    'freelancer_to_edit_whole_week_date_period_vacations',
]

const buildPayload = () => {
    const range = isRange.value
    return {
        date: entry.start_date,
        type: entry.type,
        full_day: entry.full_day,
        start_time: entry.full_day ? null : entry.start_time,
        end_time: entry.full_day ? null : entry.end_time,
        comment: entry.comment,
        // Zeitraum = tägliche Serie bis zum Enddatum; Einzeltag optional als wöchentliche Serie
        is_series: range || entry.repeat_weekly,
        series_repeat: range ? 'daily' : (entry.repeat_weekly ? 'weekly' : null),
        series_repeat_until: range ? entry.end_date : (entry.repeat_weekly ? entry.repeat_until : null),
    }
}

const save = (keepOpen) => {
    if (!validate() || submitting.value) return

    submitting.value = true
    const form = useForm(buildPayload())

    const options = {
        preserveScroll: true,
        preserveState: true,
        only: AVAILABILITY_PROPS,
        onFinish: () => {
            submitting.value = false
        },
        onSuccess: () => {
            keepOpen ? resetForm() : closeModal(true)
        },
    }

    if (!isEdit.value) {
        const routeName = props.type === 'freelancer' ? 'freelancer.vacation.add' : 'user.vacation.add'
        form.post(route(routeName, props.user.id), options)
        return
    }

    const routeName = props.editEntry.type === 'available' ? 'update.availability.entry' : 'update.vacation.entry'
    form.patch(route(routeName, props.editEntry.id), options)
}

const resetForm = () => {
    entry.type = 'available'
    entry.start_date = props.initialStart || ''
    entry.end_date = props.initialEnd || props.initialStart || ''
    entry.full_day = true
    entry.start_time = null
    entry.end_time = null
    entry.repeat_weekly = false
    entry.repeat_until = null
    entry.comment = null
}

const closeModal = (saved = false) => {
    emit('closed', saved === true)
}
</script>
