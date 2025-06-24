<template>
    <UserEditHeader :current-tab="currentTab" :user_to_edit="userToEdit">

        <!-- Zeiteingabe und Balkenanzeige -->
        <div class="mb-4">
            <h2 class="text-lg font-semibold mb-2">{{ $t('Work Times') }}</h2>
            <p class="text-sm text-gray-600">{{ $t('Overview of work times for the user') }}</p>
        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="entry in Object.values(workTimes)"
                :key="entry.date"
                class="px-4 pt-1 pb-4">
                <!-- Datum & Tag -->
                <div class="flex justify-between items-center mb-2">
                    <div class="font-medium text-gray-800">{{ entry.formatted_date }}</div>
                    <div v-if="entry.is_special_day" class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">
                        {{ $t('Special Day') }}
                    </div>
                </div>

                <!-- Balkenanzeige -->
                <div v-if="entry.planned_minutes > 0" class="relative h-6 w-full bg-gray-100 rounded-lg overflow-hidden mb-2">
                    <!-- Geplante Zeit (grün) -->
                    <div
                        class="absolute top-0 left-0 h-full bg-green-300"
                        style="width: 100%"
                    ></div>

                    <!-- Gearbeitete Zeit (blau) -->
                    <div v-if="entry.worked_hours"
                        class="absolute top-0 left-0 h-full bg-blue-300"
                        :style="{ width: `${Math.min((entry.worked_hours / entry.planned_minutes) * 100, 100)}%` }"
                    ></div>

                    <!-- Nachtstunden (lila halbtransparent) -->
                    <div
                        v-if="entry.nightly_working_hours"
                        class="absolute top-0 left-0 z-10 h-full bg-purple-600 opacity-50"
                        :style="{ width: `${Math.min((entry.nightly_working_hours / entry.planned_minutes) * 100, 100)}%` }"
                    ></div>
                </div>

                <!-- Balkenanzeige Tage ohne arbeitzeit -->
                <div v-else class="h-6 w-full bg-gray-200 rounded-lg mb-2">
                    <div class="h-full bg-gray-400 rounded-lg" style="width: 100%"></div>
                </div>

                <!-- Werteanzeige -->
                <div class="text-xs text-gray-700 grid grid-cols-2 md:grid-cols-3 gap-2">
                    <div class="flex items-center gap-x-1">
                        <span class="block size-2 bg-green-300 rounded-full"></span>
                        <strong>{{ $t('Planned')}}:</strong>
                        {{ entry.planned_hours }}h
                    </div>
                    <div v-if="entry.worked_hours" class="flex items-center gap-x-1">
                        <span class="block size-2 bg-blue-300 rounded-full"></span>
                        <strong>{{ $t('Worked') }}:</strong>
                        {{ entry.worked_hours_formatted }}
                    </div>
                    <div v-if="entry.nightly_working_hours" class="flex items-center gap-x-1">
                        <span class="block size-2 bg-purple-600 opacity-50 rounded-full"></span>
                        <strong>{{ $t('Night')}}:</strong>
                        {{ entry.nightly_working_hours_formatted }}
                    </div>
                    <div v-if="entry.work_time_balance_change">
                        <strong>{{ $t('Balance')}}:</strong>
                        {{ entry.work_time_balance_change_formatted }}
                    </div>
                    <div v-if="entry.comment">
                        <strong>{{ $t('Note') }}:</strong>
                        {{ entry.comment }}
                    </div>
                </div>
            </div>
        </div>

    </UserEditHeader>
</template>

<script setup>

import UserEditHeader from "@/Pages/Users/Components/UserEditHeader.vue";

const props = defineProps({
    userToEdit: {
        type: Object,
        required: true
    },
    workTimes: {
        type: Array,
        required: true
    },
})

</script>

<style scoped>

</style>