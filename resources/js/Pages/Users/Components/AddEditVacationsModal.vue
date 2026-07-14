<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <!-- Overlay -->
            <TransitionChild
                as="template"
                enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <!-- Panel -->
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            class="relative transform bg-white shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg rounded-2xl border border-zinc-200 text-left"
                        >
                            <!-- Header -->
                            <DialogTitle class="flex items-center justify-between px-6 py-4 border-b border-zinc-200">
                                <div class="text-base font-semibold text-zinc-900">
                                    {{ isEdit ? $t('Edit entry') : $t('Create entry') }}
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full p-1.5 text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100 transition"
                                    @click="closeModal"
                                >
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </DialogTitle>

                            <!-- Body -->
                            <div class="px-6 py-5">
                                <!-- Typ-Auswahl -->
                                <div class="grid grid-cols-2 rounded-xl border border-zinc-300 overflow-hidden mb-5 text-sm font-medium">
                                    <button
                                        type="button"
                                        class="px-3 py-2.5 transition"
                                        :class="entry.type === 'available'
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-white text-zinc-600 hover:bg-zinc-50'"
                                        @click="entry.type = 'available'"
                                    >
                                        {{ $t('Available') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="px-3 py-2.5 transition border-l border-zinc-300"
                                        :class="entry.type === 'vacation'
                                            ? 'bg-rose-600 text-white'
                                            : 'bg-white text-zinc-600 hover:bg-zinc-50'"
                                        @click="entry.type = 'vacation'"
                                    >
                                        {{ $t('Absent') }}
                                    </button>
                                </div>

                                <!-- Zeitraum -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-1">
                                    <div>
                                        <BaseInput id="entry_start_date" type="date" v-model="entry.start_date" :label="$t('From')" />
                                        <div v-show="errors.start_date" class="mt-1 text-red-600 text-xs">{{ errors.start_date }}</div>
                                    </div>
                                    <div>
                                        <BaseInput id="entry_end_date" type="date" v-model="entry.end_date" :label="$t('To')" />
                                        <div v-show="errors.end_date" class="mt-1 text-red-600 text-xs">{{ errors.end_date }}</div>
                                    </div>
                                </div>
                                <p v-if="isRange" class="mb-4 text-xs text-zinc-500">
                                    {{ $t('One entry is created for each day in the period.') }}
                                </p>
                                <div v-else class="mb-4"></div>

                                <!-- Hinweis: Abwesenheit löst bestehende Projektwünsche auf -->
                                <div
                                    v-if="entry.type === 'vacation' && conflictingWishDates.length"
                                    class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
                                >
                                    {{ $t('There are project wishes on {0}. They will be dissolved by this absence.', [conflictingWishDates.join(', ')]) }}
                                </div>

                                <!-- Ganztägig -->
                                <div class="mb-4 flex items-center gap-3">
                                    <input
                                        id="entry_full_day"
                                        v-model="entry.full_day"
                                        type="checkbox"
                                        class="h-5 w-5 border-blue-300 text-blue-600 focus:ring-0 ring-0"
                                    />
                                    <label for="entry_full_day" class="text-sm font-medium text-zinc-900">{{ $t('All day') }}</label>
                                </div>

                                <!-- Zeiten -->
                                <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4" v-if="!entry.full_day">
                                    <div>
                                        <BaseInput id="entry_start_time" type="time" v-model="entry.start_time" :label="$t('Start time')" />
                                        <div v-show="errors.start_time" class="mt-1 text-red-600 text-xs">{{ errors.start_time }}</div>
                                    </div>
                                    <div>
                                        <BaseInput id="entry_end_time" type="time" v-model="entry.end_time" :label="$t('End time')" />
                                        <div v-show="errors.end_time" class="mt-1 text-red-600 text-xs">{{ errors.end_time }}</div>
                                    </div>
                                </div>

                                <!-- Wöchentliche Wiederholung (nur bei Einzeltag sinnvoll) -->
                                <div class="mb-4" v-if="!isRange">
                                    <div class="flex items-center gap-3">
                                        <input
                                            id="entry_repeat"
                                            v-model="entry.repeat_weekly"
                                            type="checkbox"
                                            class="h-5 w-5 border-blue-300 text-blue-600 focus:ring-0 ring-0"
                                        />
                                        <label for="entry_repeat" class="text-sm font-medium text-zinc-900 inline-flex items-center gap-1.5">
                                            <IconRepeat class="h-4 w-4 text-zinc-600" />
                                            {{ $t('Repeat weekly') }}
                                        </label>
                                    </div>
                                    <div v-if="entry.repeat_weekly" class="mt-3">
                                        <BaseInput id="entry_repeat_until" type="date" v-model="entry.repeat_until" :label="$t('Ends on')" />
                                        <div v-show="errors.repeat_until" class="mt-1 text-red-600 text-xs">{{ errors.repeat_until }}</div>
                                    </div>
                                </div>

                                <!-- Notiz -->
                                <div>
                                    <BaseInput
                                        id="entry_comment"
                                        type="text"
                                        v-model="entry.comment"
                                        :label="$t('Note (optional)')"
                                    />
                                    <div v-show="errors.comment" class="mt-1 text-red-600 text-xs">{{ errors.comment }}</div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between px-6 py-4 border-t border-zinc-200">
                                <button
                                    v-if="!isEdit"
                                    class="text-blue-700 text-xs underline underline-offset-2 disabled:opacity-50"
                                    :disabled="submitting"
                                    @click="save(true)"
                                >
                                    {{ $t('Save & make further entries') }}
                                </button>
                                <div v-else></div>

                                <div class="flex items-center gap-2">
                                    <BaseButton
                                        :text="$t('Cancel')"
                                        background-color="bg-zinc-200 hover:bg-zinc-300 !text-zinc-800"
                                        @click="closeModal"
                                    />
                                    <BaseButton
                                        :text="$t('Save')"
                                        :disabled="submitting"
                                        @click="save(false)"
                                    />
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { computed, reactive, ref, watch, getCurrentInstance } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { XIcon } from '@heroicons/vue/solid'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { IconRepeat } from '@tabler/icons-vue'
import BaseButton from '@/Layouts/Components/General/Buttons/BaseButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'

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

const emit = defineEmits(['closed'])
const { proxy } = getCurrentInstance()

const open = ref(true)
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

const isRange = computed(() => !!entry.start_date && !!entry.end_date && entry.end_date > entry.start_date)

/** Wunsch-Tage im gewählten Zeitraum (Abwesenheit würde sie auflösen) — DD.MM.YYYY */
const conflictingWishDates = computed(() => {
    if (!entry.start_date) return []
    const rangeEnd = entry.end_date || entry.start_date
    return (props.projectWishes ?? [])
        .filter(wish => wish.date >= entry.start_date && wish.date <= rangeEnd)
        .map(wish => wish.date.split('-').reverse().join('.'))
        .filter((value, index, arr) => arr.indexOf(value) === index)
        .sort()
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
    'user_to_edit_whole_week_date_period_vacations',
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
    open.value = false
    emit('closed', saved === true)
}
</script>
