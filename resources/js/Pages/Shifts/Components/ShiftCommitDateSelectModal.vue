<template>
    <ArtworkBaseModal
        title="Select Commit Date"
        description="Select the date for the commit."
        @close="$emit('close')"
    >
        <!-- Hinweis-Box -->
        <div class="mb-4" v-if="isShiftCommitWorkflowEnabled">
            <div class="bg-yellow-50/80 border border-yellow-200 text-yellow-800 p-4 rounded-xl flex gap-3 items-start">
                <div class="mt-0.5 h-6 w-6 min-w-6 min-h-6 rounded-full bg-yellow-100 flex items-center justify-center text-xs font-semibold">
                    !
                </div>
                <p class="text-xs font-lexend leading-relaxed">
                    {{ $t('Direct approval is currently not possible as the approval workflow is active. Please send a release request to the responsible users.') }}
                </p>
            </div>
        </div>

        <!-- Eingaben -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInput
                v-model="newShiftCommitForm.week_number"
                type="number"
                min="1"
                max="53"
                label="Calendar Week"
                id="commit_week"
            />

            <BaseInput
                v-model="newShiftCommitForm.year"
                :min="currentYear"
                type="number"
                label="Year"
                id="commit_year"
            />

            <div class="col-span-full">
                <ArtworkBaseListbox
                    v-model="selectedCraft"
                    :items="crafts"
                    by="id"
                    option-label="name"
                    option-key="id"
                    label="Craft"
                    :use-translations="false"
                    :show-color-indicator="true"
                    color-property="color"
                />
            </div>
        </div>

        <!-- Date Range Anzeige -->
        <div class="mt-5">
            <!-- Fester Container, damit nichts springt -->
            <div
                class="rounded-2xl border border-gray-100 bg-gray-50/80 px-4 py-3 shadow-sm flex gap-3 items-start min-h-[3.25rem]"
            >
                <!-- kleines Icon / Badge -->
                <div class="mt-0.5 h-7 w-7 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-semibold text-gray-500">
                    KW
                </div>

                <div class="flex-1 flex flex-col justify-center">
                    <!-- Loading State mit Animation -->
                    <transition name="fade" mode="out-in">
                        <div
                            v-if="isLoadingDateRange"
                            key="loading"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-gray-500 font-lexend">
                                {{ $t('Loading date range for the selected calendar week...') }}
                            </p>

                            <!-- animierte Punkte -->
                            <div class="flex items-center gap-1 mt-1">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400 animate-bounce"></span>
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400 animate-bounce [animation-delay:0.12s]"></span>
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400 animate-bounce [animation-delay:0.24s]"></span>
                            </div>
                        </div>

                        <!-- Erfolgreicher DateRange -->
                        <div
                            v-else-if="dateRange.start_date && dateRange.end_date && !dateRangeError"
                            key="range"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-gray-500 font-lexend">
                                {{ $t('This calendar week covers the following period:') }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 mt-0.5">
                                {{ dateRange.start_date }} – {{ dateRange.end_date }}
                            </p>
                        </div>

                        <!-- Fehlerzustand -->
                        <div
                            v-else-if="dateRangeError"
                            key="error"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-red-600 font-lexend">
                                {{ $t(dateRangeError) }}
                            </p>
                        </div>

                        <!-- Fallback / initialer Zustand -->
                        <div
                            v-else
                            key="empty"
                            class="flex flex-col gap-1"
                        >
                            <p class="text-xs text-gray-500 font-lexend">
                                {{ $t('Select a calendar week and year to see the corresponding date range.') }}
                            </p>
                        </div>
                    </transition>
                </div>
            </div>
        </div>


        <!-- Aktionen -->
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <BaseUIButton
                is-cancel-button
                @click="$emit('close')"
                :label="$t('Cancel')"
            />

            <div class="flex flex-col xs:flex-row gap-2">
                <BaseUIButton
                    v-if="isShiftCommitWorkflowEnabled"
                    :label="$t('Request a firm commitment')"
                    is-add-button
                    @click="submit"
                />
                <BaseUIButton
                    v-else
                    :label="$t('Lock all shifts')"
                    is-add-button
                    @click="submitWithoutWorkflow"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import axios from 'axios'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";

const emit = defineEmits(['close'])

const props = defineProps({
    dateArray: Array,
    crafts: Array,
});

/**
 * Hilfsfunktion für ISO-Kalenderwoche
 */
const getIsoWeek = (date) => {
    const tmp = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
    const dayNum = tmp.getUTCDay() || 7
    tmp.setUTCDate(tmp.getUTCDate() + 4 - dayNum)
    const yearStart = new Date(Date.UTC(tmp.getUTCFullYear(), 0, 1))
    const weekNo = Math.ceil(((tmp - yearStart) / 86400000 + 1) / 7)
    return weekNo
}

const today = new Date()
const currentYear = today.getFullYear()
const currentWeek = getIsoWeek(today)

const newShiftCommitForm = useForm({
    week_number: currentWeek,
    year: currentYear,
    craft_id: null,
})

const dateRange = ref({
    start_date: null,
    end_date: null,
})
const isLoadingDateRange = ref(false)
const dateRangeError = ref(null)
const isShiftCommitWorkflowEnabled = ref(usePage().props.shiftCommitWorkflow)
const crafts = ref(props.crafts || [])
const selectedCraft = ref(null)

const getDateRangeByCalendarWeekAndYear = async (week, year) => {
    if (!week || !year) return

    isLoadingDateRange.value = true
    dateRangeError.value = null

    try {
        const response = await axios.get(route('api.helper.calendar-week'), {
            params: { week_number: week, year },
        })

        dateRange.value = response.data || { start_date: null, end_date: null }
    } catch (error) {
        console.error(error)
        dateRange.value = { start_date: null, end_date: null }
        dateRangeError.value =
            'Unable to load date range for the selected calendar week. Please try again.'
    } finally {
        isLoadingDateRange.value = false
    }
}

// Watch auf KW & Jahr – triggert API Call
watch(
    [() => newShiftCommitForm.week_number, () => newShiftCommitForm.year],
    ([newWeek, newYear]) => {
        if (newWeek && newYear) {
            getDateRangeByCalendarWeekAndYear(newWeek, newYear)
        }
    },
    { immediate: true } // direkt beim Öffnen mit aktueller KW laden
)

// optional: beim Mount einmal initial laden (falls sich das Formular später noch ändert)
onMounted(() => {
    getDateRangeByCalendarWeekAndYear(newShiftCommitForm.week_number, newShiftCommitForm.year)
})

const submit = () => {

    // add checks
    if (!selectedCraft.value) {
        alert('Please select a craft before submitting.');
        return;
    }

    newShiftCommitForm.craft_id = selectedCraft.value.id;

    newShiftCommitForm.post(route('commit-shift-workflow-request.store'), {
        onSuccess: () => {
            emit('close')
        },
        onError: (errors) => {
            console.error('Error submitting shift commit form:', errors)
        },
    })
}

const submitWithoutWorkflow = () => {

    // add checks
    if (!selectedCraft.value) {
        alert('Please select a craft before submitting.');
        return;
    }

    newShiftCommitForm.craft_id = selectedCraft.value.id;

    newShiftCommitForm.post(route('shifts.commit'), {
        onSuccess: () => {
            emit('close')
        },
        onError: (errors) => {
            console.error('Error submitting shift commit form:', errors)
        },
    })
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease-out;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
