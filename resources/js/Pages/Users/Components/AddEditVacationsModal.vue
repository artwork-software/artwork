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
                            class="relative transform bg-white shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl rounded-2xl border border-zinc-200"
                        >
                            <!-- Header -->
                            <DialogTitle class="flex items-center justify-between px-6 py-4 border-b border-zinc-200">
                                <div class="text-base font-semibold text-zinc-900">
                                    {{ $t('Availability & absence') }}
                                    <span v-if="vacation.id"> {{ $t('edit') }}</span>
                                    <span v-else> {{ $t('enter') }}</span>
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
                                <!-- Mini-Calendar Head -->
                                <div class="flex items-center gap-2 mb-3">
                                    <button
                                        class="inline-flex items-center justify-center rounded-xl border border-zinc-200 px-2.5 py-2 hover:bg-zinc-50 transition"
                                        @click="previousMonth"
                                        aria-label="Previous month"
                                    >
                                        <ChevronLeftIcon class="h-5 w-5 text-blue-600" />
                                    </button>
                                    <CalendarIcon class="h-5 w-5 text-blue-600" />
                                    <button
                                        class="inline-flex items-center justify-center rounded-xl border border-zinc-200 px-2.5 py-2 hover:bg-zinc-50 transition"
                                        @click="nextMonth"
                                        aria-label="Next month"
                                    >
                                        <ChevronRightIcon class="h-5 w-5 text-blue-600" />
                                    </button>

                                    <div class="ml-3">
                                        <h2 class="text-sm font-medium text-zinc-700">
                                            {{ createShowDate[0] }}
                                        </h2>
                                    </div>
                                </div>

                                <!-- Mini-Calendar Grid -->
                                <table class="w-full border-separate font-light">
                                    <thead>
                                    <tr class="text-zinc-500 text-center text-xs">
                                        <th class="p-3 font-normal">{{ $t('Mon') }}</th>
                                        <th class="p-3 font-normal">{{ $t('Tue') }}</th>
                                        <th class="p-3 font-normal">{{ $t('Wed') }}</th>
                                        <th class="p-3 font-normal">{{ $t('Thu') }}</th>
                                        <th class="p-3 font-normal">{{ $t('Fri') }}</th>
                                        <th class="p-3 font-normal">{{ $t('Sat') }}</th>
                                        <th class="p-3 font-normal">{{ $t('Sun') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr class="grid-cols-7 text-center" v-for="(week, index) in vacationSelectCalendar" :key="index">
                                        <td class="col-span-1" v-for="day in week" :key="day?.date">
                                            <div
                                                class="p-3 font-medium flex justify-center hover:cursor-pointer rounded-xl transition"
                                                :class="{
                            'text-blue-600': day.isToday,
                            'text-zinc-400': !day.inMonth,
                            'bg-blue-600 text-white': vacation.date === day.date,
                            'hover:bg-zinc-50': vacation.date !== day.date
                          }"
                                                @click="selectDate(day.date)"
                                            >
                                                {{ day.day }}
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                                <div class="px-1 mt-3">
                                    <div v-show="helpText.date" class="text-red-600 text-xs">
                                        {{ helpText.date }}
                                    </div>

                                    <!-- Type selection -->
                                    <div class="mt-4 mb-4">
                                        <fieldset class="mt-2">
                                            <div class="space-y-4 sm:flex sm:items-center sm:space-x-8 sm:space-y-0">
                                                <div v-for="t in availableTypes" :key="t.id" class="flex items-center">
                                                    <input
                                                        :id="t.id"
                                                        v-model="vacation.type"
                                                        name="notification-method"
                                                        :value="t.id"
                                                        type="radio"
                                                        :checked="vacation.type === t.id"
                                                        class="h-5 w-5 border-2 border-blue-300 text-blue-600 focus:border-blue-600 focus:ring-0 ring-0"
                                                    />
                                                    <label :for="t.id" class="ml-3 block text-sm font-medium text-zinc-900">{{ t.name }}</label>
                                                </div>
                                                <div v-show="helpText.type" class="mt-1 text-red-600 text-xs">
                                                    {{ helpText.type }}
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <!-- Full day -->
                                    <div class="mb-4">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input
                                                    id="full_day"
                                                    v-model="vacation.full_day"
                                                    :checked="vacation.full_day"
                                                    aria-describedby="full_day-description"
                                                    name="full_day"
                                                    type="checkbox"
                                                    class="h-5 w-5 border-blue-300 text-blue-600 focus:ring-0 ring-0"
                                                />
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label for="full_day" class="font-medium text-zinc-900">{{ $t('All day') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Time selection -->
                                    <div class="mb-4" v-if="!vacation.full_day">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <BaseInput id="start_time" :type="'time'" v-model="vacation.start_time" :label="$t('Start time')" />
                                                <div v-show="helpText.start_time" class="mt-1 text-red-600 text-xs">
                                                    {{ helpText.start_time }}
                                                </div>
                                            </div>
                                            <div>
                                                <BaseInput id="end_time" :type="'time'" v-model="vacation.end_time" :label="$t('End time')" />
                                                <div v-show="helpText.end_time" class="mt-1 text-red-600 text-xs">
                                                    {{ helpText.end_time }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Repeat -->
                                    <div class="mb-4">
                                        <div class="relative flex items-start">
                                            <div class="flex h-6 items-center">
                                                <input
                                                    id="is_serie"
                                                    v-model="vacation.is_series"
                                                    :checked="vacation.is_series"
                                                    aria-describedby="repeat-description"
                                                    name="repeat"
                                                    type="checkbox"
                                                    class="h-5 w-5 border-blue-300 text-blue-600 focus:ring-0 ring-0"
                                                    :class="{ '!text-zinc-500': !!vacation?.id }"
                                                    :disabled="!!vacation?.id"
                                                />
                                            </div>
                                            <div class="ml-3 text-sm leading-6">
                                                <label for="repeat" class="font-medium text-zinc-900 inline-flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18.212" height="12.893" viewBox="0 0 18.212 12.893" class="text-zinc-700">
                                                        <g transform="translate(-4.5 -8.439)">
                                                            <path d="M17.429,10.217,15.835,8.632a.664.664,0,0,0-.645-.171.567.567,0,0,0-.166.071.662.662,0,0,0-.323.621.7.7,0,0,0,.2.432l.759.754H8.2a3.708,3.708,0,0,0-3.7,3.695v.759a.666.666,0,0,0,.664.664h0a.666.666,0,0,0,.664-.664v-.759A2.38,2.38,0,0,1,8.2,11.663h7.37l-.759.754a.673.673,0,0,0-.2.413.669.669,0,0,0,.664.73.656.656,0,0,0,.47-.194l1.688-1.679a1.026,1.026,0,0,0,.308-.735A1.057,1.057,0,0,0,17.429,10.217Z" fill="currentColor"/>
                                                            <path d="M24.449,17.156h0a.666.666,0,0,0-.664.664v.759a2.38,2.38,0,0,1-2.371,2.371h-7.37L14.8,20.2a.673.673,0,0,0,.2-.417.669.669,0,0,0-.664-.73.656.656,0,0,0-.47.194l-1.688,1.679a1.031,1.031,0,0,0,0,1.47l1.594,1.584a.664.664,0,0,0,.645.171.567.567,0,0,0,.166-.071.662.662,0,0,0,.323-.621.7.7,0,0,0-.2-.432l-.759-.754h7.465a3.7,3.7,0,0,0,3.7-3.7v-.759A.664.664,0,0,0,24.449,17.156Z" transform="translate(-2.401 -2.837)" fill="currentColor"/>
                                                        </g>
                                                    </svg>
                                                    {{ $t('Repeat event') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Repeat details -->
                                    <div class="mb-4" v-if="vacation.is_series">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                                            <div>

                                                <!-- formatter translate key -->
                                                <ArtworkBaseListbox
                                                    v-model="vacation.series_repeat"
                                                    :items="[
                                                      { id: 'weekly', name: $t('Weekly') },
                                                      { id: 'daily', name: $t('Daily') },
                                                    ]"
                                                    use-translations
                                                />
                                                <!--<label class="block text-xs font-medium text-zinc-600">{{ $t('Repetition') }}</label>
                                                <select
                                                    v-model="vacation.series_repeat"
                                                    class="mt-1 block w-full border-0 py-1.5 text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm"
                                                    :class="{ '!bg-zinc-100': !!vacation?.id }"
                                                    :disabled="!!vacation?.id"
                                                >
                                                    <option value="weekly" selected>{{ $t('Weekly') }}</option>
                                                    <option value="daily">{{ $t('Daily') }}</option>
                                                </select>-->
                                            </div>
                                            <div>
                                                <BaseInput id="series_repeat_until" label="Ends" type="date" v-model="vacation.series_repeat_until" :disabled="!!vacation?.id" :class="{ '!bg-zinc-100': !!vacation?.id }" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Comment -->
                                    <div>
                                        <div class="mt-1">
                                            <BaseInput
                                                id="comment"
                                                :label="$t('Comment')"
                                                v-model="vacation.comment"
                                                type="text"
                                            />
                                            <div v-show="helpText.comment" class="mt-1 text-red-600 text-xs">
                                                {{ helpText.comment }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between px-6 py-4 border-t border-zinc-200">
                                <button class="text-blue-700 text-xs underline underline-offset-2" @click="saveOrUpdateVacation(true)">
                                    {{ $t('Save & make further entries') }}
                                </button>

                                <div class="flex items-center gap-2">
                                    <BaseButton v-if="vacation.isDirty && !vacation.id" @click="saveOrUpdateVacation(false)" :text="$t('Save')" />
                                    <BaseButton v-if="vacation.isDirty && vacation.id" @click="saveOrUpdateVacation(false)" :text="$t('Bearbeiten')" />
                                    <BaseButton v-if="!vacation.isDirty && vacation.id" @click="checkVacationType" background-color="bg-red-600 hover:bg-red-700" :text="$t('Delete')" />
                                    <BaseButton v-if="!vacation.isDirty && !vacation.id" @click="checkVacationType" background-color="bg-red-600 hover:bg-red-700" :text="$t('Cancel')" />
                                </div>
                            </div>

                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- Serien-Löschen -->
    <ConfirmDeleteModal
        v-if="showDeleteConfirmModal"
        :title="$t('Delete serial entry')"
        :button="$t('Delete individual entry')"
        :description="$t('Would you like to delete just this entry or the whole series?')"
        :is-series-delete="true"
        :is_budget="false"
        @closed="showDeleteConfirmModal = false"
        @complete_delete="deleteCompleteSeries"
        @delete="deleteAvailabilityOrVacation"
    />
</template>

<script setup>
import { ref, reactive, getCurrentInstance, computed } from 'vue'
import dayjs from 'dayjs'
import { useForm, router } from '@inertiajs/vue3'
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    XIcon,
    CalendarIcon
} from '@heroicons/vue/solid'
import {
    Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot
} from '@headlessui/vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import BaseButton from '@/Layouts/Components/General/Buttons/BaseButton.vue'
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";

// Props & Emits (identisch)
const props = defineProps({
    editVacation: { type: Object, default: null },
    user: { type: Object, required: true },
    type: { type: String, default: '' },
    vacationSelectCalendar: { type: Array, default: () => [] },
    dateToShow: { type: Array, default: () => [] },
    createShowDate: { type: Array, default: () => [] },
    selectedDate: { type: String, default: '' },
})
const emit = defineEmits(['closed'])
const { proxy } = getCurrentInstance()
// State
const open = ref(true)

const vacation = useForm({
    id: props.editVacation ? props.editVacation.id : null,
    start_time: props.editVacation ? props.editVacation.start_time : null,
    end_time: props.editVacation ? props.editVacation.end_time : null,
    date: props.editVacation ? props.editVacation.date : (props.selectedDate || null),
    type: props.editVacation ? props.editVacation.type : 'available',
    full_day: props.editVacation ? props.editVacation.full_day : false,
    is_series: props.editVacation ? props.editVacation.is_series : false,
    series_repeat: props.editVacation?.series
        ? { id: props.editVacation?.series.frequency, name: props.editVacation?.series.frequency === 'daily' ? proxy.$t('Daily') : proxy.$t('Weekly') }
        : { id: 'weekly', name: proxy.$t('Weekly') },
    series_repeat_until: props.editVacation?.series ? props.editVacation?.series.end_date : null,
    comment: props.editVacation ? props.editVacation.comment : null,
    type_before_update: props.editVacation ? props.editVacation.type : null,
})

const helpText = reactive({
    date: '',
    type: '',
    start_time: '',
    end_time: '',
    series_repeat_until: '',
    comment: '',
})

const showDeleteConfirmModal = ref(false)

// i18n helper


const availableTypes = computed(() => [
    { id: 'available', name: proxy.$t('Availability') },
    { id: 'vacation', name: proxy.$t('Absence') },
])

// Methods (1:1 Logik)
const closeModal = (bool) => {
    vacation.reset()
    emit('closed', bool)
}

const selectDate = (date) => {
    vacation.date = date
}

const checkVacationType = () => {
    if (props.editVacation?.is_series === true) {
        showDeleteConfirmModal.value = true
    } else {
        deleteAvailabilityOrVacation()
    }
}

const saveOrUpdateVacation = (withNewModal = false) => {
    // Validierungen
    if (!vacation.date) {
        helpText.date = proxy.$t('Please select a date.')
        return
    } else helpText.date = ''

    if (!vacation.type) {
        helpText.type = proxy.$t('Please select a type.')
        return
    } else helpText.type = ''

    if (!vacation.full_day) {
        if (!vacation.start_time) {
            helpText.start_time = proxy.$t('Please select a start time.')
            return
        } else helpText.start_time = ''

        if (!vacation.end_time) {
            helpText.end_time = proxy.$t('Please select an end time.')
            return
        } else helpText.end_time = ''

        if (vacation.start_time > vacation.end_time) {
            helpText.start_time = proxy.$t('The start time must be before the end time.')
            return
        } else helpText.start_time = ''

        if (vacation.start_time === vacation.end_time) {
            helpText.start_time = proxy.$t('The start time and the end time must not be the same.')
            return
        } else helpText.start_time = ''
    }

    if (vacation.is_series) {
        if (!vacation.series_repeat_until) {
            helpText.series_repeat_until = proxy.$t('Please select an end date.')
            return
        } else helpText.series_repeat_until = ''
    }

    if (vacation.comment && vacation.comment.length > 20) {
        helpText.comment = proxy.$t('The comment must not be longer than 20 characters.')
        return
    } else helpText.comment = ''

    // Create or Update
    const applyTransform = () => {
        vacation.transform((data) => ({
            ...data,
            // Ensure backend receives primitives
            series_repeat: data.is_series
                ? (typeof data.series_repeat === 'string' ? data.series_repeat : data.series_repeat?.id ?? null)
                : null,
            series_repeat_until: data.is_series ? data.series_repeat_until : null,
            start_time: data.full_day ? null : data.start_time,
            end_time: data.full_day ? null : data.end_time,
        }))
    }

    if (vacation.id === null) {
        if (props.type === 'freelancer') {
            applyTransform()
            vacation.post(route('freelancer.vacation.add', props.user.id), {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => { withNewModal ? resetForm() : closeModal(true) }
            })
        } else {
            applyTransform()
            vacation.post(route('user.vacation.add', props.user.id), {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => { withNewModal ? resetForm() : closeModal(true) }
            })
        }
    } else {
        if (vacation.type_before_update === 'available') {
            applyTransform()
            vacation.patch(route('update.availability', vacation.id), {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => { withNewModal ? resetForm() : closeModal(true) }
            })
        }
        if (vacation.type_before_update === 'vacation') {
            applyTransform()
            vacation.patch(route('update.vacation', vacation.id), {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => { withNewModal ? resetForm() : closeModal(true) }
            })
        }
    }
}

const resetForm = () => {
    vacation.id = null
    vacation.start_time = null
    vacation.end_time = null
    vacation.date = props.selectedDate || null
    vacation.type = 'available'
    vacation.full_day = false
    vacation.is_series = false
    vacation.series_repeat = {id: 'weekly', name: proxy.$t('Weekly')}
    vacation.series_repeat_until = null
    vacation.comment = null
    vacation.type_before_update = null
}

const deleteAvailabilityOrVacation = () => {
    if (vacation.type_before_update === 'available') {
        vacation.delete(route('delete.availability', vacation.id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => closeModal(true)
        })
    }
    if (vacation.type_before_update === 'vacation') {
        vacation.delete(route('delete.vacation', vacation.id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => closeModal(true)
        })
    }
}

const deleteCompleteSeries = () => {
    if (vacation.type_before_update === 'available') {
        vacation.delete(route('delete.availability.series', props.editVacation.series_id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => closeModal(true)
        })
    }
    if (vacation.type_before_update === 'vacation') {
        vacation.delete(route('delete.vacation.series', props.editVacation.series_id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => closeModal(true)
        })
    }
}

const previousMonth = () => {
    const currentMonth = new Date(props.createShowDate[1].date)
    router.reload({
        data: { vacationMonth: subtractOneMonth(currentMonth) }
    })
}

const nextMonth = () => {
    const currentMonth = new Date(props.createShowDate[1].date)
    router.reload({
        data: { vacationMonth: addOneMonth(currentMonth) }
    })
}

const addOneMonth = (dateObj) => dayjs(dateObj).add(1, 'month').format('YYYY-MM-DD')
const subtractOneMonth = (dateObj) => dayjs(dateObj).subtract(1, 'month').format('YYYY-MM-DD')

</script>
