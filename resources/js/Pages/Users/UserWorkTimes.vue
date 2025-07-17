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
                                <!-- Worked hours (blue) - up to daily target -->
                                <div
                                    v-if="entry.worked_hours"
                                    class="absolute top-0 left-0 h-full bg-blue-500"
                                    :style="{ width: `${entry.worked_hours > Math.abs(entry.daily_target_minutes) ?
                                        (Math.abs(entry.daily_target_minutes) / entry.worked_hours) * 100 :
                                        (entry.worked_hours / Math.abs(entry.daily_target_minutes)) * 100}%` }"
                                ></div>
                                <!-- Planned hours (gray) - up to daily target when no worked hours -->
                                <div
                                    v-if="entry.planned_minutes > 0 && !entry.worked_hours"
                                    class="absolute top-0 left-0 h-full bg-gray-300"
                                    :style="{
                                        width: `${Math.min((Math.abs(entry.daily_target_minutes) / Math.abs(entry.daily_target_minutes)) * 100, 100)}%`
                                    }"
                                ></div>
                                <!-- Missing hours (red-gray striped) - when planned < required and no worked hours -->
                                <div
                                    v-if="entry.planned_minutes < Math.abs(entry.daily_target_minutes) && !entry.worked_hours"
                                    class="absolute top-0 left-0 h-full bg-striped-red-gray"
                                    :style="{
                                        left: `${(entry.planned_minutes / Math.abs(entry.daily_target_minutes)) * 100}%`,
                                        width: `${((Math.abs(entry.daily_target_minutes) - entry.planned_minutes) / Math.abs(entry.daily_target_minutes)) * 100}%`
                                    }"
                                ></div>
                                <!-- Overplanned hours (green-gray striped) - when planned > required and no worked hours -->
                                <div
                                    v-if="entry.planned_minutes > Math.abs(entry.daily_target_minutes) && !entry.worked_hours"
                                    class="absolute top-0 h-full bg-striped-green-gray"
                                    :style="{
                                        left: `${(Math.abs(entry.daily_target_minutes) / entry.planned_minutes) * 100}%`,
                                        width: `${((entry.planned_minutes - Math.abs(entry.daily_target_minutes)) / Math.abs(entry.daily_target_minutes)) * 100}%`
                                    }"
                                ></div>

                                <!-- Overtime (dark green) - when worked > required -->
                                <div
                                    v-if="entry.worked_hours > Math.abs(entry.daily_target_minutes)"
                                    class="absolute top-0 h-full bg-green-700"
                                    :style="{
                                        left: `${Math.min((Math.abs(entry.daily_target_minutes) / entry.worked_hours) * 100, 100)}%`,
                                        width: `${((entry.worked_hours - Math.abs(entry.daily_target_minutes)) / entry.worked_hours) * 100}%`
                                    }"
                                ></div>

                                <!-- Undertime (red) - when worked < required -->
                                <div
                                    v-if="entry.worked_hours && entry.worked_hours < Math.abs(entry.daily_target_minutes)"
                                    class="absolute top-0 left-0 h-full bg-red-500"
                                    :style="{
                                        left: `${(entry.worked_hours / Math.abs(entry.daily_target_minutes)) * 100}%`,
                                        width: `${((Math.abs(entry.daily_target_minutes) - entry.worked_hours) / Math.abs(entry.daily_target_minutes)) * 100}%`
                                    }"
                                ></div>
                            </div>
                            <div class="flex flex-wrap gap-3 text-xs text-gray-700 mt-1">
                                <div><strong>{{ $t('Daily target') }}: </strong>{{ entry.daily_target_hours }}h</div>
                                <div><strong>{{ $t('Planned') }}: </strong>
                                    <span v-if="!entry.worked_hours">{{ entry.planned_hours }}h</span>
                                    <span v-else>{{ entry.worked_hours_formatted }}h</span>
                                </div>
                                <div v-if="entry.worked_hours"><strong>{{ $t('Worked') }}: </strong>{{ entry.worked_hours_formatted }}</div>
                                <div v-if="entry.nightly_working_hours"><strong>{{ $t('Night') }}: </strong>{{ entry.nightly_working_hours_formatted }}</div>
                                <div><strong>{{ $t('Balance') }}: </strong>
                                    <span :class="[ entry.work_time_balance_change > 0 ? 'text-green-500' : entry.work_time_balance_change < 0 ? 'text-red-500' : 'text-gray-500']">
                                        {{ entry.work_time_balance_change_formatted }}
                                    </span>
                                </div>
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
.bg-striped-red-gray {
    background-image: repeating-linear-gradient(
        45deg,
        rgb(239, 68, 68), /* red-500 */
        rgb(239, 68, 68) 10px,
        rgb(209, 213, 219) /* gray-300 */ 10px,
        rgb(209, 213, 219) 20px
    );
}

.bg-striped-green-gray {
    background-image: repeating-linear-gradient(
        45deg,
        rgb(22, 163, 74), /* green-700 */
        rgb(34, 197, 94) 10px,
        rgb(209, 213, 219) /* gray-300 */ 10px,
        rgb(209, 213, 219) 20px
    );
}
</style>
