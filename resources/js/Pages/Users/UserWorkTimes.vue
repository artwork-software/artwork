<template>
    <UserEditHeader :user_to_edit="userToEdit">

        <!-- Zeiteingabe und Balkenanzeige -->
        <div class="flex items-center justify-between mb-4">
            <div class="">
                <h2 class="text-lg font-semibold mb-2">{{ $t('Work Times') }}</h2>
                <p class="text-sm text-gray-600">{{ $t('Overview of work times for the user') }}</p>
            </div>

            <div>
                <GlassyIconButton text="Arbeitszeit Buchen" icon="IconAlarmPlus" @click="showWorkingTimePostEntryModal = true" />
            </div>
        </div>
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-x-2">
                <BaseInput
                    id="work_time_start_date"
                    v-model="dateRangeCopy.start"
                    type="date"
                    :label="$t('Start date')"
                    @change="updateWorkTimeDateRange"
                />
                <BaseInput
                    id="work_time_end_date"
                    v-model="dateRangeCopy.end"
                    type="date"
                    :label="$t('End date')"
                    @change="updateWorkTimeDateRange"
                />
            </div>

            <WorkTimeTimerComponent :totals="totals" />
        </div>

        <div class="space-y-12">


            <!-- Kalenderwochen -->
            <div v-for="(week, weekKey) in workTimes" :key="weekKey">
                <h2 class="text-lg font-semibold text-gray-800 mb-3 border-b border-gray-300 border-dashed pb-1 font-lexend">{{ weekKey }}</h2>

                <div class="divide-y divide-gray-200">
                    <div
                        v-for="entry in Object.values(week)"
                        :key="entry.date"
                        class="py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <!-- Linke Spalte -->
                        <div>
                            <div class="text-sm font-lexend font-medium text-gray-900">{{ entry.formatted_date }}</div>


                            <div class="font-lexend my-3" v-for="comment in entry.comments" :key="comment.id">
                                <div class="flex items-center gap-2 mb-1">
                                    <UserPopoverTooltip :user="comment.user" class="text-xs text-gray-500" width="9" height="9" />
                                    <div>
                                        <div class="text-xs text-gray-900">{{ comment.text }}</div>
                                        <div class="text-xs text-gray-500">{{ comment.work_time_change }} Std.</div>
                                        <div class="text-[9px] text-gray-500">{{ comment.date }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="entry.is_special_day" class="text-xs text-amber-600 bg-amber-100 px-2 py-0.5 rounded inline-block mt-2">
                                {{ $t('Special Day') }}
                            </div>
                        </div>

                        <!-- Rechte Spalte -->
                        <div class="w-full md:w-2/3">
                            <div class="relative h-4 bg-gray-100 rounded overflow-hidden mb-1">
                                <div
                                    v-if="entry.planned_minutes"
                                    class="absolute top-0 left-0 h-full bg-green-300"
                                    style="width: 100%"
                                ></div>
                                <div
                                    v-if="entry.worked_hours"
                                    class="absolute top-0 left-0 h-full bg-blue-400"
                                    :style="{ width: `${Math.min((entry.worked_hours / entry.planned_minutes) * 100, 100)}%` }"
                                ></div>
                                <div
                                    v-if="entry.nightly_working_hours"
                                    class="absolute top-0 left-0 h-full bg-purple-500 opacity-40"
                                    :style="{ width: `${Math.min((entry.nightly_working_hours / entry.planned_minutes) * 100, 100)}%` }"
                                ></div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-xs text-gray-700 mt-1">
                                <div><strong>{{ $t('Planned') }}:</strong> {{ entry.planned_hours }}h</div>
                                <div v-if="entry.worked_hours"><strong>{{ $t('Worked') }}:</strong> {{ entry.worked_hours_formatted }}</div>
                                <div v-if="entry.nightly_working_hours"><strong>{{ $t('Night') }}:</strong> {{ entry.nightly_working_hours_formatted }}</div>
                                <div v-if="entry.work_time_balance_change"><strong>{{ $t('Balance') }}:</strong> {{ entry.work_time_balance_change_formatted }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <WorkingTimePostEntryModal
            v-if="showWorkingTimePostEntryModal"
            :user="userToEdit"
            @close="showWorkingTimePostEntryModal = false"
        />

    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import WorkingTimePostEntryModal from "@/Pages/Users/Components/WorkingTimePostEntryModal.vue";
import {ref} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {router} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import WorkTimeTimerComponent from "@/Pages/Users/Components/WorkTimeTimerComponent.vue";

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    workTimes: {
        type: Object,
        required: true
    },
    dateRange: {
        type: Object,
        required: true
    },
    totals: {
        type: Object,
        required: true
    }
})

const dateRangeCopy = ref({
    start: props.dateRange.start,
    end: props.dateRange.end
})

const showWorkingTimePostEntryModal = ref(false)


const updateWorkTimeDateRange = () => {

    // Ensure the date range is valid
    if (new Date(dateRangeCopy.value.start) > new Date(dateRangeCopy.value.end)) {
        return;
    }

    // Update the router with the new date range
    if (dateRangeCopy.value.start === props.dateRange.start && dateRangeCopy.value.end === props.dateRange.end) {
        return; // No change, do nothing
    }

    // Use the router to reload the page with the new date range
    router.reload({
        data: {
            start: dateRangeCopy.value.start,
            end: dateRangeCopy.value.end
        }
    })
}
</script>

<style scoped>

</style>
